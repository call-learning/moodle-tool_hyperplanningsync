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
 * Renderer for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/tablelib.php');

require_once(dirname(__FILE__) . '/locallib.php');

/**
 * hyperplanningsync renderer
 *
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_hyperplanningsync_renderer extends plugin_renderer_base {

    /**
     * Display the log
     *
     * @param array $rows array of record objects
     * @param array $pageparams url parameters and filters
     * @param int $totalcount
     * @param moodle_url $url
     * @return string - html to output
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function display_log($rows, $pageparams, $totalcount, $url) {

        $dateformat = get_string('strftimedate', 'langconfig');

        $createdusernamefields = get_all_user_name_fields(false, 'createdby', 'createdby');

        $table = new flexible_table('tool-hyperplanningsync-log');

        $columns = array(
            'importid',
            'lineid',
            'idfield',
            'idvalue',
            'userid',
            'cohort',
            'cohortid',
            'maingroup',
            'othergroups',
            'groupscsv',
            'status',
            'skipped',
            'pending',
            'createdbyid',
            'timecreated'
        );

        $headers = array();
        foreach ($columns as $column) {
            $headers[] = get_string('report:' . $column, 'tool_hyperplanningsync');
        }

        $table->define_columns($columns);
        $table->define_headers($headers);

        foreach ($columns as $column) {
            $table->column_class($column, 'text-nowrap tool-hyperplanningsync-log-' . $column);
        }

        $baseurl = new moodle_url($url, $pageparams);
        $table->define_baseurl($baseurl);
        $table->sortable(false);
        $table->collapsible(false);

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'tool-hyperplanningsync-log');
        $table->set_attribute('class', 'generaltable');
        $table->set_attribute('width', '100%');
        $table->setup();

        $table->initialbars($totalcount > TOOL_HYPERPLANNINGSYNC_PERPAGE);
        // Something weird going on with pagesize() it prints blank rows up to the size of the perpage.
        $perpage = $totalcount > TOOL_HYPERPLANNINGSYNC_PERPAGE ? TOOL_HYPERPLANNINGSYNC_PERPAGE : $totalcount;
        $table->pagesize($perpage, $totalcount);

        if ($rows) {
            foreach ($rows as $row) {
                $tablerow = array();

                $tablerow[] = $row->importid;
                $tablerow[] = $row->lineid;
                $tablerow[] = format_string($row->idfield);
                $tablerow[] = format_string($row->idvalue);

                if (empty($row->userid)) {
                    $tablerow[] = get_string('error:nouser', 'tool_hyperplanningsync');
                } else {
                    $tablerow[] = fullname($row);
                }

                $tablerow[] = format_string($row->cohort);
                if (empty($row->cohortid)) {
                    $tablerow[] = get_string('error:nocohort', 'tool_hyperplanningsync');
                } else {
                    $tablerow[] = format_string($row->cohortname);
                }

                $tablerow[] = format_string($row->maingroup);
                $tablerow[] = format_string($row->othergroups);
                if (empty($row->groupscsv)) {
                    $groups = get_string('error:nogroups', 'tool_hyperplanningsync');
                } else {
                    $groups = implode(PHP_EOL, explode(',', $row->groupscsv));
                }
                $tablerow[] = format_text($groups);

                $tablerow[] = format_text($row->status);
                $tablerow[] = empty($row->skipped) ? get_string('no') : get_string('yes');
                $tablerow[] = empty($row->pending) ? get_string('no') : get_string('yes');

                $createdby = new stdClass();
                foreach ($createdusernamefields as $fieldname => $altname) {
                    $createdby->$fieldname = $row->$altname;
                }
                $tablerow[] = fullname($createdby);

                $tablerow[] = userdate($row->timecreated, $dateformat);

                $table->add_data($tablerow);
            }
        }

        $output = html_writer::div($table->print_html(), 'tool-hyperplanningsync-log');

        return $output;
    }

}