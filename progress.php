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
 * Preview the import for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_hyperplanningsync\hyperplanningsync;
require_once('../../../config.php');
global $CFG;
global $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

$thisurl = new moodle_url('/admin/tool/hyperplanningsync/progress.php');

admin_externalpage_setup('tool_hyperplanningsync_progress', '', [], $thisurl);

$progresselementid = html_writer::random_id();
$PAGE->requires->js_call_amd('tool_hyperplanningsync/display_progress', 'init', [$progresselementid]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('hyperplanningsync:progress', 'tool_hyperplanningsync'));

echo html_writer::div('', '', ['id' => $progresselementid]);

echo $OUTPUT->footer();
