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

use context_system;
use core_user;
use html_writer;
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

        $columns = [
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
        ];

        $headers = [];
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
        $this->downloadable = true;
        $this->is_downloading(optional_param('download', 0, PARAM_ALPHA), 'viewlog');
    }

    /**
     * Setup sql query for this table.
     *
     * @param array $filters
     */
    protected function set_sql_from_filters(array $filters): void {
        global $DB;
        $params = [];
        $wheres = [];

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

        if (isset($filters['status']) && $filters['status'] != -1) {
            $params['status'] = $filters['status'];
            $wheres[] = 'l.status = :status';
        }
        $where = '1=1';
        if (!empty($wheres)) {
            $where = $where . ' AND ' . implode(' AND ', $wheres);
        }

        $userfields = \core_user\fields::for_identity(context_system::instance())->with_name()->excluding('id');
        $usernamefields = $userfields->get_sql('u');
        $createdusernamefields = $userfields->get_sql('createdby');
        $fields = "l.*
                {$usernamefields->selects}
                {$createdusernamefields->selects},
                {$idvaluefield} AS idvalue,
                u.id as userid,
                u.firstname AS firstname,
                u.lastname AS lastname,
                c.name AS cohortname";
        $from = "{tool_hyperplanningsync_log} l
                LEFT JOIN {user} u ON u.id = l.userid
                {$usernamefields->joins}
                LEFT JOIN {user} createdby ON createdby.id = l.createdbyid
                {$createdusernamefields->joins}
                LEFT JOIN {cohort} c ON c.id = l.cohortid";
        $this->set_sql($fields, $from, $where, array_merge($params, $usernamefields->params, $createdusernamefields->params));
    }

    /**
     * Override of the base function.
     *
     * @return array
     */
    public function get_sql_where(): array {
        global $DB;

        $conditions = [];
        $params = [];

        if (isset($this->columns['fullname'])) {
            static $i = 0;
            $i++;

            if (!empty($this->get_initial_first())) {
                $conditions[] = $DB->sql_like('u.firstname', ':ifirstc' . $i, false, false);
                $params['ifirstc' . $i] = $this->get_initial_first() . '%';
            }
            if (!empty($this->get_initial_last())) {
                $conditions[] = $DB->sql_like('u.lastname', ':ilastc' . $i, false, false);
                $params['ilastc' . $i] = $this->get_initial_last() . '%';
            }
        }

        return [implode(" AND ", $conditions), $params];
    }

    /**
     * Fullname or email
     *
     * @param object $row
     */
    public function col_fullname($row) {
        if (empty($row->userid)) {
            return $row->email;
        }
        return parent::col_fullname($row);
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
        if ($this->is_downloading()) {
            return join("\n", $allmessages);
        }
        return \html_writer::alist($allmessages);
    }

    /**
     * Other groups
     *
     * @param object $row
     * @return string
     */
    public function col_othergroups($row) {
        $groups = explode(',', $row->othergroups);
        if ($this->is_downloading()) {
            return join("\n", $groups);
        }
        return \html_writer::alist($groups);
    }

    /**
     * Other groups
     *
     * @param object $row
     * @return string
     */
    public function col_groupscsv($row) {
        $groups = explode(',', $row->groupscsv);
        if ($this->is_downloading()) {
            return join("\n", $groups);
        }
        return \html_writer::alist($groups);
    }

    /**
     * Time created
     *
     * @param object $row
     * @return string
     */
    public function col_timecreated($row) {
        if ($this->is_downloading()) {
            return userdate($row->timecreated);
        }
        return userdate_htmltime($row->timecreated);
    }

    /**
     * Time created
     *
     * @param object $row
     * @return string
     */
    public function col_timemodified($row) {
        if ($this->is_downloading()) {
            return userdate($row->timemodified);
        }
        return userdate_htmltime($row->timemodified);
    }

    /**
     * Time created
     *
     * @param object $row
     * @return string
     */
    public function col_createdbyid($row) {
        static $users = [];

        global $COURSE;
        $userid = $row->createdbyid;
        if (empty($users[$userid])) {
            $users[$userid] = core_user::get_user($userid);
        }
        $name = fullname($users[$userid], has_capability('moodle/site:viewfullnames', $this->get_context()));
        if ($this->download) {
            return $name;
        }
        if ($COURSE->id == SITEID) {
            $profileurl = new moodle_url('/user/profile.php', ['id' => $userid]);
        } else {
            $profileurl = new moodle_url('/user/view.php',
                ['id' => $userid, 'course' => $COURSE->id]);
        }
        if ($this->is_downloading()) {
            return $name;
        } else {
            return html_writer::link($profileurl, $name);
        }
    }

    /**
     * Download csv
     *
     * @return void
     */
    public function download() {
        $exportclass = $this->export_class_instance();
        $this->setup();
        $this->pagesize = 0;
        $this->query_db(0);
        $this->build_table();
        $exportclass->finish_table();
        $exportclass->finish_document();
    }
}
