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
 * Defines the capabilities used by the hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('TOOL_HYPERPLANNINGSYNC_PERPAGE', 20);

require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Get the field names
 *
 * @param stdClass $formdata
 * @return array
 */
function tool_hyperplanningsync_get_fields($formdata = null) {
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
 * Import the CSV file into the log table.
 *
 * @global moodle_database $DB
 * @global stdClass $USER
 * @param string $content CSV file content
 * @param stdClass $formdata
 * @param \moodle_url $returnurl
 * @return int importid
 */
function tool_hyperplanningsync_import($content, $formdata, \moodle_url $returnurl) {
    global $DB, $USER, $CFG;
    require_once($CFG->libdir.'/csvlib.class.php');

    // Import id.
    $importid = csv_import_reader::get_new_iid('hyperplanningsync');

    // CSV reader.
    $csvreader = new csv_import_reader($importid, 'hyperplanningsync');

    // Open the file.
    $readcount = $csvreader->load_csv_content($content, $formdata->upload_encoding, $formdata->upload_delimiter);
    $csvloaderror = $csvreader->get_error();
    unset($content);

    if (!is_null($csvloaderror)) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }

    // Get the columns.
    $columns = $csvreader->get_columns();

    if (empty($columns)) {
        // Oops.
        $csvreader->close();
        $csvreader->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }

    $importfields = tool_hyperplanningsync_get_fields($formdata);

    // Convert to lower case.
    $importfields = array_map('strtolower', $importfields);

    // Swap to the moodle id field.
    $moodle_idfield = get_config('tool_hyperplanningsync', 'moodle_idfield');
    $importfields[$moodle_idfield] = $importfields['idfield'];
    unset($importfields['idfield']);

    $fields = array();

    // Check the fields in the csv are valid.
    foreach ($columns as $column) {
        // Check field name is valid.
        $checkfield = clean_param($column, PARAM_TEXT);
        if ($checkfield !== $column) {
            $csvreader->close();
            $csvreader->cleanup();
            print_error('error:invalidfieldname', 'tool_hyperplanningsync', $returnurl, $column);
        }

        // Convert all fields to lower case.
        $field = core_text::strtolower($column);

        if (in_array($field, $fields)) {
            // Duplicate.
            $csvreader->close();
            $csvreader->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $field);
        }

        $fields[] = $field;

    }

    // Check for required fields.
    foreach ($importfields as $fieldname) {
        if (!in_array($fieldname, $fields)) {
            $csvreader->close();
            $csvreader->cleanup();
            print_error('error:missingfield', 'tool_hyperplanningsync', $returnurl, $fieldname);
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
        $newrow = array (
            'importid' => $importid,
            'lineid' => $linenum,
            'processed' => false,
            'skipped' => false,
            'status' => '',
            'createdbyid' => $USER->id,
            'timecreated' => time(),
            'idfield' => $moodle_idfield,
            'userid' => '',
            'email' => '',
            'cohort' => '',
            'maingroup' => '',
            'othergroups' => '',
            'cohortid' => '',
            'groups' => '',
        );

        foreach ($fields as $key => $csvfield) {
            if (!empty($importfields[$csvfield])) {
                // Its a required field.
                $dbfield = $importfields[$csvfield];
                // Clean all input from users.
                $newrow[$dbfield] = clean_param($row[$key], PARAM_TEXT);
            }
        }

        if (!$userid = $DB->get_field('user', 'id', array($moodle_idfield => $newrow[$moodle_idfield]))) {
            $newrow['status'] .= get_string('error:nouser', 'tool_hyperplanningsync') . PHP_EOL;
            $newrow['skipped'] = true;
        } else {
            $newrow['userid'] = $userid;
        }

        // Check the idnumber, if that doesn't exist then try the name.
        $sql = "SELECT c.id
                FROM {cohort} c
                WHERE CASE WHEN c.idnumber > '' THEN c.idnumber ELSE c.name END = :idnumber";

        if (!$cohortid = $DB->get_field_sql($sql, array('idnumber' => $newrow['cohort']))) {
            $newrow['status'] .= get_string('error:nocohort', 'tool_hyperplanningsync') . PHP_EOL;
            $newrow['skipped'] = true;
        } else {
            $newrow['cohortid'] = $cohortid;
        }

        $groups = tool_hyperplanningsync_clean_groups($newrow,
            $formdata->group_transform_pattern,
            $formdata->group_transform_replacement);

        // Use the group idnumber if available, otherwise use the group name.
        $sql = "SELECT g.id
                FROM {enrol} e
                JOIN {groups} g ON g.courseid = e.courseid
                WHERE e.customint1 = :cohortid
                AND e.enrol = :enroletype
                AND e.status = " . ENROL_INSTANCE_ENABLED . "
                AND CASE WHEN g.idnumber > '' THEN g.idnumber ELSE g.name END = :groupidnumber";

        foreach ($groups as $group) {
            $sql = "SELECT g.id
                    FROM {groups} g
                    WHERE CASE WHEN g.idnumber > '' THEN g.idnumber ELSE g.name END = :groupidnumber";

            if (!$DB->record_exists_sql($sql, array('groupidnumber' => $group))) {
                $newrow['status'] .= get_string('error:nogroup', 'tool_hyperplanningsync', $group) . PHP_EOL;
                if (empty($formdata->ignoregroups)) {
                    $newrow['skipped'] = true;
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
                    $newrow['status'] .= get_string('error:nogroupincourse', 'tool_hyperplanningsync', $group) . PHP_EOL;
                    if (empty($formdata->ignoregroups)) {
                        $newrow['skipped'] = true;
                    }
                }
            }

        }

        $newrow['groups'] = implode(',', $groups);

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
 * Combines the main group and other groups and strips them using the tab format.
 *
 * @param array $row
 * @param string $pattern
 * @param string $replacement
 * @return array of cleaned group.
 * @throws coding_exception
 */
function tool_hyperplanningsync_clean_groups($row, $pattern, $replacement) {
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
        $cleangroup =  clean_param($value, PARAM_TAG); // Trim any double spaces.
        if ($pattern) {
            $cleangroup = preg_replace($pattern, $replacement, $cleangroup);
        }
        $groups[$key] =  $cleangroup;

    }

    return $groups;
}

/**
 * Returns a list of records in the log
 *
 * @global moodle_database $DB
 * @param array $filters
 * @return array of record objects
 */
function tool_hyperplanningsync_get_log($filters) {
    global $DB;

    $params = array();
    $wheres = array();
    $where = '';

    $idvaluefield = "
        CASE
            WHEN l.idfield = 'email' THEN l.email
            WHEN l.idfield = 'idnumber' THEN l.idnumber
            WHEN l.idfield = 'username' THEN l.username
        END";

    if (isset($filters['importid']) && $filters['importid']) {
        $params['importid'] = $filters['importid'];
        $wheres[] = 'l.importid = :importid';
    }

    if (isset($filters['idvalue']) && $filters['idvalue']) {
        $params['idvalue'] = '%' . $DB->sql_like_escape($filters['idvalue']) . '%';
        $wheres[] = $DB->sql_like($idvaluefield, ':idvalue', false);
    }

    if (isset($filters['cohort']) && $filters['cohort']) {
        $params['cohort'] = '%' . $DB->sql_like_escape($filters['cohort']) . '%';
        $wheres[] = $DB->sql_like('l.cohort', ':cohort', false);
    }

    if (!empty($wheres)) {
        $where = 'WHERE ' . implode(' AND ', $wheres);
    }

    $sqlbody = "FROM {tool_hyperplanningsync_log} l
                LEFT JOIN {user} u ON u.id = l.userid
                LEFT JOIN {user} createdby ON createdby.id = l.createdbyid
                LEFT JOIN {cohort} c ON c.id = l.cohortid
                {$where}";

    $usernamefields = get_all_user_name_fields(true, 'u');
    $createdusernamefields = get_all_user_name_fields(true, 'createdby', null, 'createdby');
    $sql = "SELECT l.*,
                {$usernamefields},
                {$createdusernamefields},
                {$idvaluefield} AS idvalue,
                c.name AS cohortname
            {$sqlbody}
            ORDER BY l.importid, l.lineid";

    $sqlcount = "SELECT COUNT(*) " . $sqlbody;
    $totalcount = $DB->count_records_sql($sqlcount, $params);

    $offset = $filters['pagenum'] * TOOL_HYPERPLANNINGSYNC_PERPAGE;

    $rows = $DB->get_records_sql($sql, $params, $offset, TOOL_HYPERPLANNINGSYNC_PERPAGE);

    return array($rows, $totalcount);
}

/**
 * Process the data.
 *
 * @global moodle_database $DB
 * @param stdClass $formdata
 */
function tool_hyperplanningsync_process($formdata) {
    global $DB;

    $removecohorts = (int)$formdata->removecohorts;
    $removegroups = (int)$formdata->removegroups;

    $params = array(
        'importid' => $formdata->importid,
        'skipped' => false,
        'processed' => false,
    );

    // Get rows for this importid that haven't got errors and haven't been processed.
    $rows = $DB->get_recordset('tool_hyperplanningsync_log', $params);

    if (!$rows->valid()) {
        // Nothing to do.
        $rows->close();
        return;
    }

    foreach ($rows as $row) {

        // Update status.
        $newstatus = get_string('process:started', 'tool_hyperplanningsync');
        tool_hyperplanningsync_update_status($row->id, $newstatus);

        // Remove existing cohorts.
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
                    tool_hyperplanningsync_update_status($row->id, $newstatus);
                }
            }
        }

        // Add to cohort - This will trigger an event to enrol the user too.
        cohort_add_member($row->cohortid, $row->userid);

        // Update status.
        $newcohort = $DB->get_record('cohort', array('id' => $row->cohortid), 'id, name, idnumber');
        $newstatus = get_string('process:addedcohort', 'tool_hyperplanningsync', $newcohort);
        tool_hyperplanningsync_update_status($row->id, $newstatus);

        // Get the groupid numbers we want to sign up to.
        $groupidnumbers = explode(',', $row->groups);

        list($groupswhere, $params) = $DB->get_in_or_equal($groupidnumbers, SQL_PARAMS_NAMED, 'groupidnumber');

        $params['enroltype'] = 'cohort';
        $params['cohortid'] = $row->cohortid;

        // This will look for courses associated with the cohort via cohort sync.
        // And any matching group idnumbers for those courses.
        // Missing groups will be automagically ignored.
        $sql = "SELECT g.id AS groupid, g.name AS groupname, c.id AS courseid, c.fullname AS coursename
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
                    tool_hyperplanningsync_update_status($row->id, $newstatus);
                }
            }
        }

        // Add to the new groups.
        foreach ($newgroups as $newgroup) {

            if (!is_enrolled(context_course::instance($newgroup->courseid), $row->userid)) {
                $newstatus = get_string('process:notenrolled', 'tool_hyperplanningsync', $newgroup);
                tool_hyperplanningsync_update_status($row->id, $newstatus);
                continue;
            }

            groups_add_member($newgroup->groupid, $row->userid);

            // Update status.
            $newstatus = get_string('process:addedgroup', 'tool_hyperplanningsync', $newgroup);
            tool_hyperplanningsync_update_status($row->id, $newstatus);
        }

        // Done.
        $DB->set_field('tool_hyperplanningsync_log', 'processed', true, array('id' => $row->id));

        // Update status.
        $newstatus = get_string('process:done', 'tool_hyperplanningsync');
        tool_hyperplanningsync_update_status($row->id, $newstatus);

    }

    // Close the record set.
    $rows->close();

}

/**
 * Update the status.
 *
 * @global moodle_database $DB
 * @param int $logid
 * @param string $newstatus
 */
function tool_hyperplanningsync_update_status($logid, $newstatus) {
    global $DB;

    $status = $DB->get_field('tool_hyperplanningsync_log', 'status', array('id' => $logid));

    if ($status === false) {
        debugging('Missing record for log id ' . $logid, DEBUG_DEVELOPER);
        return false;
    }

    if (empty($status)) {
        $status = '';
    }

    $status .= $newstatus . PHP_EOL;

    $DB->set_field('tool_hyperplanningsync_log', 'status', $status, array('id' => $logid));
}

