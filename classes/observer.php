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

namespace tool_hyperplanningsync;

use core\event\user_created;
use core\event\user_enrolment_created;
use core\task\manager;
use moodle_database;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer.
 */
class observer {

    /**
     * Triggered via user_created event.
     *
     * @param user_created $event
     * @return bool true on success.
     */
    public static function user_created(user_created $event) {
        $syncenabled = get_config('tool_hyperplanningsync', 'sync_new_users_enabled');
        if (!$syncenabled) {
            return true; // Sync is not enabled for new users.
        }
        $importprocesstask = new process_import_for_new_user();
        $importprocesstask->set_custom_data(['relateduserid' => $event->relateduserid]);
        manager::queue_adhoc_task($importprocesstask);
    }

    /**
     * When user is enrolled onto a course, trigger his/her addition to the relevant group
     *
     * @param user_enrolment_created $event
     */
    public static function user_enrolled(user_enrolment_created $event) {
        global $DB;
        try {
            $allgroups =
                $DB->get_records('tool_hyperplanningsync_group', array('userid' => $event->relateduserid, 'courseid' =>
                    $event->courseid));
            foreach ($allgroups as $groupdef) {
                // Update status.
                groups_add_member($groupdef->newgroupid, $groupdef->userid);
                // Update status for this import log.
                $newstatus = get_string('process:addedgroup', 'tool_hyperplanningsync', $groupdef->newgroupid);
                hyperplanningsync::update_status($groupdef->logid, $newstatus);
                $DB->delete_records('tool_hyperplanningsync_group', array('id' => $groupdef->id));
            }
        } catch (moodle_exception $e) {
            // We should just fail but not prevent other events from being processed, so we catch any exception.
            debugging('tool_hyperplanningsync_observer:user_enrolled:' . $e->getMessage() . '-' . $e->getTraceAsString());
        }
    }
}
