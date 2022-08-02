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

use core\task\adhoc_task;
use tool_hyperplanningsync\hyperplanningsync;

/**
 * Adhoc task to process import
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class hyperplanning_sync_task extends adhoc_task {
    /**
     * Run task for syncing cohort enrolments.
     */
    public function execute() {
        global $DB;

        $cdata = $this->get_custom_data();
        // Now assign this user to the given cohort.
        hyperplanningsync::assign_cohort($cdata->row, $cdata->removecohorts);

        // And the given group.
        hyperplanningsync::assign_group($cdata->row, $cdata->removegroups);
        // Update status.
        $newstatus = get_string('process:done', 'tool_hyperplanningsync');
        hyperplanningsync::set_status_done($cdata->row->id);
        hyperplanningsync::update_status_text($cdata->row->id, $newstatus);
    }
}
