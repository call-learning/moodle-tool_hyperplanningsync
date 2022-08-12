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
use tool_hyperplanningsync\log_table;

define('NO_OUTPUT_BUFFERING', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
global $CFG, $PAGE, $OUTPUT, $DB;
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/preview_form.php');
$PAGE->set_cacheable(false);    // Progress bar is used here.

$pageparams = array();

$pageparams['importid'] = required_param('importid', PARAM_INT);
$pageparams['pagenum'] = optional_param('pagenum', 0, PARAM_INT);

$pageoptions = array('pagelayout' => 'report');

$thisurl = new moodle_url('/admin/tool/hyperplanningsync/preview.php');

admin_externalpage_setup('tool_hyperplanningsync_import', '', $pageparams, $thisurl, $pageoptions);
require_capability('tool/hyperplanningsync:manage', context_system::instance());

$mform = new preview_form();
$formdata = $mform->get_data();

if ($formdata = $mform->get_data()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('preview:heading:process', 'tool_hyperplanningsync'));
    // Just in case.
    require_sesskey();
    $progressbar = new progress_bar();
    $progressbar->create();

    // Let's do this.
    hyperplanningsync::process($formdata->importid, $formdata->removecohorts, $formdata->removegroups,
        $progressbar);

    $viewlogurl = new moodle_url('/admin/tool/hyperplanningsync/viewlog.php', $pageparams);
    echo $OUTPUT->continue_button($viewlogurl, get_string('continue'), 'get');
    echo $OUTPUT->footer();
    exit;
} else if ($mform->is_cancelled()) {
    // Cleanup.
    if (!empty($pageparams['importid'])) {
        global $DB;
        $DB->delete_records_select('tool_hyperplanningsync_log', 'importid = :importid',
            array('importid' => $pageparams['importid']));
    }
    $indexurl = new moodle_url('/admin/tool/hyperplanningsync/index.php');
    redirect($indexurl);
}

$table = new log_table('hyperplanning-log', $pageparams, $thisurl);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('preview:heading', 'tool_hyperplanningsync'));

$mform->set_data($pageparams);
echo $OUTPUT->box($mform->render());

$importname = $DB->get_field('tool_hyperplanningsync_info', 'importname', ['importid' => $pageparams['importid']]);

echo $OUTPUT->heading(get_string('preview:results', 'tool_hyperplanningsync', $importname ?? ''), 3);

$renderer = $PAGE->get_renderer('tool_hyperplanningsync');

echo $renderer->display_log($table);
echo $OUTPUT->footer();
