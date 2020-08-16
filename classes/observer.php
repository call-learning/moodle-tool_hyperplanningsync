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
 * Observers
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/admin/tool/hyperplanningsync/locallib.php');

/**
 * Event observer.
 */
class tool_hyperplanningsync_observer {

    /**
     * Triggered via user_created event.
     *
     * @global \moodle_database $DB
     * @param \core\event\user_created $event
     * @return bool true on success.
     */
    public static function user_created(\core\event\user_created $event) {
        global $DB;

        // Look for any pending records for this user that haven't been skipped.
        $sql = "SELECT l.*
                FROM {tool_hyperplanningsync_log} l
                JOIN {user} u ON u.id = :relateduserid
                    AND ((l.idfield = :email AND l.email = u.email)
                        OR (l.idfield = :idnumber AND l.idnumber = u.idnumber)
                        OR (l.idfield = :username AND l.username = u.username)
                    )
                WHERE l.pending = 1
                AND l.skipped = 0";

        $params = array(
            'email' => 'email',
            'idnumber' => 'idnumber',
            'username' => 'username',
            'relateduserid' => $event->relateduserid,
        );

        if (!$imports = $DB->get_records_sql($sql, $params)) {
            return true;
        }

        $formdata = new stdClass();
        // New users won't exist in cohprts or course groups so its okay for these to be false.
        $formdata->removecohorts = false;
        $formdata->removegroups = false;

        foreach ($imports as $import) {
            // Set pending to false and update userid and update status.
            $import->userid = $event->relateduserid;
            $import->pending = false;
            $import->status .= get_string('process:usercreated', 'tool_hyperplanningsync') . PHP_EOL;
            $DB->update_record('tool_hyperplanningsync_log', $import);

            // Process the record.
            $formdata->importid = $import->importid;
            tool_hyperplanningsync_process($formdata, null, $import->id);

        }
    }
}
