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

namespace tool_hyperplanningsync\task;

use coding_exception;
use core\task\adhoc_task;
use dml_exception;
use moodle_exception;
use tool_hyperplanningsync\hyperplanningsync;

/**
 * Import process for new users
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class process_import_for_new_user extends adhoc_task {
    /**
     * Process row for this user
     *
     * @return bool|void
     * @throws dml_exception
     */
    public function execute() {
        global $DB;
        $data = $this->get_custom_data();
        // Look for any pending records for this user that haven't been skipped.
        $sql = "SELECT l.*
                FROM {tool_hyperplanningsync_log} l
                JOIN {user} u ON u.id = :relateduserid
                    AND ((l.idfield = :email AND l.email = u.email)
                        OR (l.idfield = :idnumber AND l.idnumber = u.idnumber)
                        OR (l.idfield = :username AND l.username = u.username)
                    )
                WHERE l.status = :statuspending";

        $params = [
            'email' => 'email',
            'idnumber' => 'idnumber',
            'username' => 'username',
            'relateduserid' => $data->relateduserid,
            'statuspending' => hyperplanningsync::STATUS_PENDING,
        ];

        if (!$imports = $DB->get_records_sql($sql, $params)) {
            return true;
        }

        try {
            foreach ($imports as $import) {
                hyperplanningsync::process_new_user_import($import, $data->relateduserid);
            }
        } catch (moodle_exception $e) {
            // We should just fail but not prevent other events from being processed, so we catch any exception.
            debugging('user_created_process_import:user_created:' . $e->getMessage() . '-' . $e->getTraceAsString());
        }
    }
}
