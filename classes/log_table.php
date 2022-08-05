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
namespace tool_hyperplanningsync;

use moodle_url;
use table_sql;

/**
 * Hyperplanning log table class
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_table extends table_sql {
    /**
     * @var int TOOL_HYPERPLANNINGSYNC_PERPAGE
     */
    const TOOL_HYPERPLANNINGSYNC_PERPAGE = 20;

    /**
     * Sets up the table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param array $filterparams
     * @param moodle_url $currenturl
     */
    public function __construct($uniqueid, $filterparams, $currenturl) {
        parent::__construct($uniqueid);

        $columns = array(
            'importid',
            'lineid',
            'fullname',
            'cohort',
            'maingroup',
            'othergroups',
            'groupscsv',
            'status',
            'statustext',
            'createdbyid',
            'timecreated',
            'timemodified',
        );

        $headers = array();
        foreach ($columns as $column) {
            $headers[] = get_string('report:' . $column, 'tool_hyperplanningsync');
        }
        $this->define_columns($columns);
        $this->define_headers($headers);
        $baseurl = new moodle_url($currenturl, $filterparams);
        $this->define_baseurl($baseurl);
        $this->sortable(true);
        $this->collapsible(true);
        $this->is_downloadable(true);
        $this->initialbars(true);
        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('id', 'tool-hyperplanningsync-log');
        $this->set_attribute('class', 'generaltable');
        $this->set_attribute('width', '100%');
        $this->pagesize = self::TOOL_HYPERPLANNINGSYNC_PERPAGE;
        $this->set_sql_from_filters($filterparams);
        $this->useridfield = 'userid';
    }

    /**
     * Setup sql query for this table.
     *
     * @param array $filters
     */
    protected function set_sql_from_filters(array $filters): void {
        global $DB;
        $params = array();
        $wheres = array();

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

        $where = '1=1';
        if (!empty($wheres)) {
            $where = $where . ' AND ' . implode(' AND ', $wheres);
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        $createdusernamefields = get_all_user_name_fields(true, 'createdby', null, 'createdby');
        $fields = "l.*,
                {$usernamefields},
                {$createdusernamefields},
                {$idvaluefield} AS idvalue,
                u.id as userid,
                u.firstname AS firstname,
                u.lastname AS lastname,
                c.name AS cohortname";
        $from = "{tool_hyperplanningsync_log} l
                LEFT JOIN {user} u ON u.id = l.userid
                LEFT JOIN {user} createdby ON createdby.id = l.createdbyid
                LEFT JOIN {cohort} c ON c.id = l.cohortid";

        // Check if any additional filtering is required.
        [$additionalwhere, $additionalparams] = $this->get_sql_where();

        $this->set_sql($fields, $from, $where . ' AND (' . $additionalwhere . ')', array_merge($params, $additionalparams));
    }

    /**
     * Override of the base function.
     *
     * @return array
     */
    public function get_sql_where(): array {
        global $DB;

        $conditions = array();
        $params = array();

        if (isset($this->columns['fullname'])) {
            static $i = 0;
            $i++;

            if (!$this->get_initial_first()) {
                $conditions[] = $DB->sql_like('u.firstname', ':ifirstc' . $i, false, false);
                $params['ifirstc' . $i] = $this->get_initial_first() . '%';
            }
            if (!$this->get_initial_last()) {
                $conditions[] = $DB->sql_like('u.lastname', ':ilastc' . $i, false, false);
                $params['ilastc' . $i] = $this->get_initial_last() . '%';
            }
        }

        return array(implode(" AND ", $conditions), $params);
    }

    /**
     * Status row
     *
     * @param object $row
     */
    public function col_status($row) {
        return get_string('status:' . $row->status, 'tool_hyperplanningsync');
    }

    /**
     * Status text
     *
     * @param object $row
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function col_statustext($row) {
        $allmessages = json_decode($row->statustext);
        if (empty($allmessages)) {
            $allmessages = [];
        }
        $allmessages = array_map(function($message) {
            return $message->info;
        }, $allmessages);
        return \html_writer::alist($allmessages);
    }

    /**
     * Other groups
     *
     * @param object $row
     * @return string
     */
    public function col_othergroups($row) {
        return \html_writer::alist(explode(',', $row->othergroups));
    }
    /**
     * Other groups
     *
     * @param object $row
     * @return string
     */
    public function col_groupscsv($row) {
        return \html_writer::alist(explode(',', $row->groupscsv));
    }

    /**
     * Time created
     * @param object $row
     * @return string
     */
    public function col_timecreated($row) {
        return userdate_htmltime($row->timecreated);
    }
}
