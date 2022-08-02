<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hyperplanning sync class
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_hyperplanningsync;

use coding_exception;
use context_course;
use core\task\adhoc_task;
use core\task\manager;
use core_php_time_limit;
use core_text;
use csv_import_reader;
use dml_exception;
use moodle_database;
use moodle_exception;
use moodle_url;
use progress_bar;
use stdClass;
use text_progress_trace;
use tool_hyperplanningsync\task\hyperplanning_sync_task;

/**
 * Hyperplanning syn class
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hyperplanningsync {
    /**
     * Initial status
     */
    const STATUS_INITED = 0;
    /**
     * Record has been skipped
     */
    const STATUS_SKIPPED = 1;
    /**
     * Record is on hold and waiting for user to be created
     */
    const STATUS_PENDING = 2;
    /**
     * Record is being processed
     */
    const STATUS_PROCESSING = 10;
    /**
     * Record has been processed
     */
    const STATUS_DONE = 100;

    /**
     * Get a list of importid that have not been processed
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_unprocessed_importids(): array {
        global $DB;

        // Get unprocessed import ids.
        return $DB->get_records_sql('SELECT importid, timecreated
                FROM {tool_hyperplanningsync_log} WHERE status = :statusinited GROUP BY importid', [
            'statusinited' => self::STATUS_INITED
        ]);
    }

    /**
     * Process the data.
     *
     * @param int $importid
     * @param bool $removecohorts
     * @param bool $removegroups
     * @param object|null $progressbar
     * @param int|null $logid
     * @param bool|null $deferred
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function process(int $importid, ?bool $removecohorts = false, ?bool $removegroups = false,
        ?object $progressbar = null, ?int $logid = null, ?bool $deferred = true): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        // Raise time limit so we can process the full set.
        // TODO: Use a adhoc task.
        core_php_time_limit::raise(HOURSECS);
        raise_memory_limit(MEMORY_EXTRA);

        $params = array(
            'importid' => $importid,
            'status' => self::STATUS_INITED,
        );

        if (!empty($logid)) {
            // Process one record only - used by the observer.
            $params['id'] = $logid;
        }

        // Get rows for this importid that haven't got errors and haven't been processed.
        $rows = $DB->get_recordset('tool_hyperplanningsync_log', $params);

        if (!$rows->valid()) {
            // Nothing to do.
            $rows->close();
            return;
        }
        $rowcount = $DB->count_records('tool_hyperplanningsync_log', $params);
        $rowindex = 0;

        foreach ($rows as $row) {
            // If row does not contains userid we skip it.
            if (empty($row->userid)) {
                continue;
            }
            // Update status.
            $newstatus = get_string('process:started', 'tool_hyperplanningsync');
            self::update_status_text($row->id, $newstatus);
            if ($progressbar) {
                if ($progressbar instanceof progress_bar) {
                    $progressbar->update($rowindex, $rowcount, get_string('process:progress', 'tool_hyperplanningsync'));
                }
                if ($progressbar instanceof text_progress_trace) {
                    $progressbar->output("$rowindex/$rowcount");
                }
            }
            // We delegate this to an async task.
            $synctask = new hyperplanning_sync_task();
            $cdata = new stdClass();
            $cdata->removecohorts = $removecohorts;
            $cdata->removegroups = $removegroups;
            $cdata->row = $row;
            $synctask->set_custom_data($cdata);
            if ($deferred) {
                manager::queue_adhoc_task($synctask);
            } else {
                $synctask->execute();
            }
            $rowindex++;
        }

        // Close the record set.
        $rows->close();
    }

    /**
     * Update the status text.
     *
     * @param int $logid
     * @param string $newstatus
     * @return bool
     * @throws dml_exception
     */
    public static function update_status_text(int $logid, string $newstatus): void {
        global $DB;

        $statustext = $DB->get_field('tool_hyperplanningsync_log', 'statustext', array('id' => $logid));

        if ($statustext === false) {
            debugging('Missing record for log id ' . $logid, DEBUG_DEVELOPER);
            return;
        }

        if (empty($statustext)) {
            $statustext = '';
        }

        $statustext .= $newstatus . PHP_EOL;

        $DB->set_field('tool_hyperplanningsync_log', 'statustext', $statustext, array('id' => $logid));
    }

    /**
     * Update the status to done.
     *
     * @param int $logid
     * @return bool
     * @throws dml_exception
     */
    public static function set_status_done(int $logid): void {
        global $DB;
        $DB->set_field('tool_hyperplanningsync_log', 'status', self::STATUS_DONE, array('id' => $logid));
    }

    /**
     * Assign a cohort from given row data
     *
     * @param object $row
     * @param false $removecohorts
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function assign_cohort($row, $removecohorts = false): void {
        global $CFG;
        global $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');
        // Add to cohort - This will trigger an event to enrol the user too.
        cohort_add_member($row->cohortid, $row->userid);
        // Update status.
        $newcohort = $DB->get_record('cohort', array('id' => $row->cohortid), 'id, name, idnumber');
        $newstatus = get_string('process:addedcohort', 'tool_hyperplanningsync', $newcohort);
        self::update_status_text($row->id, $newstatus);
        // Remove existing cohorts assignments.
        if ($removecohorts) {
            $sql = "SELECT c.id, c.name, c.idnumber
                    FROM {cohort_members} cm
                    JOIN {cohort} c ON c.id = cm.cohortid
                    WHERE cm.userid = :userid
                    AND c.id <> :cohortid";
            $params = array(
                'userid' => $row->userid,
                'cohortid' => $row->cohortid
            );
            if ($cohorts = $DB->get_records_sql($sql, $params)) {
                foreach ($cohorts as $cohort) {
                    cohort_remove_member($cohort->id, $row->userid);
                    // Update status.
                    $newstatus = get_string('process:removedcohort', 'tool_hyperplanningsync', $cohort);
                    self::update_status_text($row->id, $newstatus);
                }
            }
        }
    }

    /**
     * Assign group from given row
     *
     * @param object $row
     * @param bool $removegroups
     */
    public static function assign_group(object $row, ?bool $removegroups = false): void {
        global $DB;
        // Get the groupid numbers we want to sign up to.
        $groupidnumbers = explode(',', $row->groupscsv);

        list($groupswhere, $params) = $DB->get_in_or_equal($groupidnumbers, SQL_PARAMS_NAMED, 'groupidnumber');

        $params['enroltype'] = 'cohort';
        $params['cohortid'] = $row->cohortid;

        // This will look for courses associated with the cohort via cohort sync.
        // And any matching group idnumbers for those courses.
        // Missing groups will be automagically ignored.
        $sql = "SELECT "
            . $DB->sql_concat("e.id", "'_'", "g.id", "'_'", "c.id")
            . " AS id, g.id AS groupid, g.name AS groupname, c.id AS courseid, c.fullname AS coursename
                FROM {enrol} e
                JOIN {course} c ON c.id = e.courseid
                JOIN {groups} g ON g.courseid = e.courseid
                WHERE e.customint1 = :cohortid
                AND e.enrol = :enroltype
                AND e.status = " . ENROL_INSTANCE_ENABLED . "
                AND CASE WHEN g.idnumber > '' THEN g.idnumber ELSE g.name END {$groupswhere}";

        $newgroups = $DB->get_records_sql($sql, $params);

        // Remove from existing groups.
        if ($removegroups && $newgroups) {
            // Ignore new groups.
            $newgroupids = array_keys($newgroups);
            list($groupswhere, $params) = $DB->get_in_or_equal($newgroupids, SQL_PARAMS_NAMED, 'groupid', false);

            $params['userid'] = $row->userid;

            $sql = "SELECT g.id AS groupid, g.name AS groupname, c.id AS courseid, c.fullname AS coursename
                    FROM {groups} g
                    JOIN {course} c ON c.id = g.courseid
                    JOIN {groups_members} gm ON gm.groupid = g.id
                    WHERE gm.userid = :userid
                    AND g.id {$groupswhere}";

            if ($oldgroups = $DB->get_records_sql($sql, $params)) {
                foreach ($oldgroups as $oldgroup) {
                    groups_remove_member($oldgroup->groupid, $row->userid);

                    // Update status.
                    $newstatus = get_string('process:removedgroup', 'tool_hyperplanningsync', $oldgroup);
                    self::update_status_text($row->id, $newstatus);
                }
            }
        }

        // Add to the new groups.
        foreach ($newgroups as $newgroup) {

            if (!is_enrolled(context_course::instance($newgroup->courseid), $row->userid)) {
                $newstatus = get_string('process:notenrolled', 'tool_hyperplanningsync', $newgroup);
                self::update_status_text($row->id, $newstatus);
                if (!$DB->record_exists('tool_hyperplanningsync_group',
                    array('userid' => $row->userid, 'courseid' => $newgroup->courseid, 'newgroupid' => $newgroup->groupid))) {
                    global $USER;
                    $dataobject = (object) [
                        'courseid' => $newgroup->courseid,
                        'userid' => $row->userid,
                        'newgroupid' => $newgroup->groupid,
                        'logid' => $row->id,
                        'usermodified' => $USER->id,
                        'timecreated' => time(),
                        'timemodified' => time()
                    ];
                    // This will be dealt later when user registers.
                    $DB->insert_record('tool_hyperplanningsync_group', $dataobject);
                }

                continue;
            }

            groups_add_member($newgroup->groupid, $row->userid);

            // Update status.
            $newstatus = get_string('process:addedgroup', 'tool_hyperplanningsync', $newgroup);
            self::update_status_text($row->id, $newstatus);
        }
    }

    /**
     * Import the CSV file into the log table.
     *
     * @param string $content CSV file content
     * @param stdClass $formdata
     * @param moodle_url $returnurl
     * @return int importid
     */
    public static function do_import(string $content, stdClass $formdata, moodle_url $returnurl): int {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        // Import id.
        $importid = csv_import_reader::get_new_iid('hyperplanningsync');

        // CSV reader.
        $csvreader = new csv_import_reader($importid, 'hyperplanningsync');

        // Open the file.
        $readcount = $csvreader->load_csv_content($content, $formdata->upload_encoding, $formdata->upload_delimiter);
        $csvloaderror = $csvreader->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            throw new moodle_exception('csvloaderror', '', $returnurl, $csvloaderror);
        }

        // Get the columns.
        $columns = $csvreader->get_columns();

        if (empty($columns)) {
            // Oops.
            $csvreader->close();
            $csvreader->cleanup();
            throw new moodle_exception('cannotreadtmpfile', 'error', $returnurl);
        }

        $importfields = self::get_fields($formdata);

        // Convert to lower case.
        $importfields = array_map('strtolower', $importfields);

        // Swap to the moodle id field.
        $moodleidfield = get_config('tool_hyperplanningsync', 'moodle_idfield');
        $importfields[$moodleidfield] = $importfields['idfield'];
        unset($importfields['idfield']);

        $fields = array();

        // Check the fields in the csv are valid.
        foreach ($columns as $column) {
            // Check field name is valid.
            $checkfield = clean_param($column, PARAM_TEXT);
            if ($checkfield !== $column) {
                $csvreader->close();
                $csvreader->cleanup();
                throw new moodle_exception('error:invalidfieldname', 'tool_hyperplanningsync', $returnurl, $column);
            }

            // Convert all fields to lower case.
            $field = core_text::strtolower($column);

            if (in_array($field, $fields)) {
                // Duplicate.
                $csvreader->close();
                $csvreader->cleanup();
                throw new moodle_exception('duplicatefieldname', 'error', $returnurl, $field);
            }

            $fields[] = $field;

        }

        // Check for required fields.
        foreach ($importfields as $fieldname) {
            if (!in_array($fieldname, $fields)) {
                $csvreader->close();
                $csvreader->cleanup();
                throw new moodle_exception('error:missingfield', 'tool_hyperplanningsync', $returnurl, $fieldname);
            }
        }

        // Start importing.

        // Switch the import fields around.
        $importfields = array_flip($importfields);

        // Import in batches.
        $newrows = array();

        // Column header is first line.
        $linenum = 1;

        $csvreader->init();
        while ($row = $csvreader->next()) {
            $linenum++;

            // We init here safe values as insert_records does not like to have missing columns.
            $newrow = array(
                'importid' => $importid,
                'lineid' => $linenum,
                'status' => 0,
                'statustext' => '',
                'createdbyid' => $USER->id,
                'timecreated' => time(),
                'idfield' => $moodleidfield,
                'userid' => '',
                'email' => '',
                'cohort' => '',
                'maingroup' => '',
                'othergroups' => '',
                'cohortid' => '',
                'groupscsv' => '',
            );

            foreach ($fields as $key => $csvfield) {
                if (!empty($importfields[$csvfield])) {
                    // Its a required field.
                    $dbfield = $importfields[$csvfield];
                    $value = trim($row[$key]);
                    if ($csvfield !== 'td') {
                        // Clean all input from users except for TD which contains <>.
                        $value = clean_param($value, PARAM_TEXT);
                    }
                    $newrow[$dbfield] = $value;

                }
            }

            if (!$userid = $DB->get_field('user', 'id', array($moodleidfield => $newrow[$moodleidfield]))) {
                $newrow['statustext'] .= get_string('error:nouser', 'tool_hyperplanningsync') . PHP_EOL;
                // Missing users are pending not skipped.
                // So if skipped = true, then this row has been skipped for another reason.
                $newrow['status'] = self::STATUS_PENDING;
            } else {
                $newrow['userid'] = $userid;
            }

            // Check the idnumber, if that doesn't exist then try the name.
            $sql = "SELECT c.id
                FROM {cohort} c
                WHERE CASE WHEN c.idnumber > '' THEN c.idnumber ELSE c.name END = :idnumber";

            if (!$cohortid = $DB->get_field_sql($sql, array('idnumber' => $newrow['cohort']))) {
                $newrow['statustext'] .= get_string('error:nocohort', 'tool_hyperplanningsync') . PHP_EOL;
                $newrow['status'] = self::STATUS_SKIPPED;
            } else {
                $newrow['cohortid'] = $cohortid;
            }

            $groups = self::clean_groups($newrow,
                $formdata->group_transform_pattern,
                $formdata->group_transform_replacement);

            // Use the group idnumber if available, otherwise use the group name.
            $sql = "SELECT g.id
                    FROM {groups} g
                    WHERE CASE WHEN g.idnumber > '' THEN g.idnumber ELSE g.name END = :groupidnumber";

            foreach ($groups as $group) {
                if (!$DB->record_exists_sql($sql, array('groupidnumber' => $group))) {
                    $newrow['statustext'] .= get_string('error:nogroup', 'tool_hyperplanningsync', $group) . PHP_EOL;
                    if (empty($formdata->ignoregroups)) {
                        $newrow['status'] = self::STATUS_SKIPPED;
                    }
                    continue;
                }

                if ($cohortid) {
                    $params = array(
                        'enroletype' => 'cohort',
                        'cohortid' => $cohortid,
                        'groupidnumber' => $group
                    );

                    // Check if there is a cohort + course + group combination.
                    if (!$DB->record_exists_sql($sql, $params)) {
                        $newrow['statustext'] .= get_string('error:nogroupincourse', 'tool_hyperplanningsync', $group) . PHP_EOL;
                        if (empty($formdata->ignoregroups)) {
                            $newrow['status'] = self::STATUS_SKIPPED;
                        }
                    }
                }

            }

            $newrow['groupscsv'] = implode(',', $groups);

            $newrows[] = $newrow;

            if (count($newrows) >= 250) { // BATCH_INSERT_MAX_ROW_COUNT.
                $DB->insert_records('tool_hyperplanningsync_log', $newrows);
                unset($newrows);
                $newrows = array();
            }

        }

        if (!empty($newrows)) {
            $DB->insert_records('tool_hyperplanningsync_log', $newrows);
        }

        $csvreader->close();
        $csvreader->cleanup();

        // Return the import id.
        return $importid;
    }

    /**
     * Get the field names
     *
     * @param stdClass $formdata
     * @return array
     * @throws dml_exception
     */
    public static function get_fields(stdClass $formdata = null): array {
        $config = get_config('tool_hyperplanningsync');

        // Use the config names if they exist.
        $fields = array(
            'idfield' => !empty($config->field_idfield) ? $config->field_idfield : 'e-mail',
            'cohort' => !empty($config->field_cohort) ? $config->field_cohort : 'Promotions',
            'maingroup' => !empty($config->field_maingroup) ? $config->field_maingroup : 'TD',
            'othergroups' => !empty($config->field_othergroups) ? $config->field_othergroups : 'Regroupements',
        );

        if (!empty($formdata)) {
            // Override with the form names.
            foreach ($fields as $fieldname => $value) {
                $formfield = 'field_' . $fieldname;
                if (!empty($formdata->$formfield)) {
                    $fields[$fieldname] = $formdata->$formfield;
                }
            }
        }

        return $fields;
    }

    /**
     * Combines the main group and other groups and strips them using the tab format.
     *
     * @param array $row
     * @param string $pattern
     * @param string $replacement
     * @return array of cleaned group.
     * @throws coding_exception
     */
    public static function clean_groups(array $row, string $pattern, string $replacement): array {
        $groups = array();

        if (!empty($row['maingroup'])) {
            $groups[] = $row['maingroup'];
        }

        if (!empty($row['othergroups'])) {
            $othergroups = explode(',', $row['othergroups']);
            $groups = array_merge($groups, $othergroups);
        }

        foreach ($groups as $key => $value) {
            $value = trim($value); // Trim spaces.
            $value = trim($value, '[]'); // Trim brackets.
            $cleangroup = clean_param($value, PARAM_TAG); // Trim any double spaces.
            if ($pattern) {
                $cleangroup = preg_replace($pattern, $replacement, $cleangroup);
            }
            $groups[$key] = $cleangroup;

        }

        return $groups;
    }

    /**
     * Force run scheduled task
     *
     * Used mostly for testing but this can be useful in other contexts
     */
    public static function force_run_cohort_sync() {
        $task = \core\task\manager::get_scheduled_task('\\enrol_cohort\\task\\enrol_cohort_sync');
        $task->execute();
    }

    /**
     * Remove cohort member and force trigger the event.
     *
     * @param int $cohortid
     * @param int $userid
     * @return void
     */
    protected static function trigger_cohort_remove_member(int $cohortid, int $userid): void {
        global $DB;
        $DB->delete_records('cohort_members', array('cohortid' => $cohortid, 'userid' => $userid));
    }

    /**
     * Add cohort member and trigger the event.
     *
     * @param int $cohortid
     * @param int $userid
     * @return void
     */
    protected static function trigger_cohort_add_member(int $cohortid, int $userid): void {
        global $DB;
        if ($DB->record_exists('cohort_members', array('cohortid' => $cohortid, 'userid' => $userid))) {
            // No duplicates!
            return;
        }
        $record = new stdClass();
        $record->cohortid = $cohortid;
        $record->userid = $userid;
        $record->timeadded = time();
        $DB->insert_record('cohort_members', $record);
    }
}
