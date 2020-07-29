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
define('NO_OUTPUT_BUFFERING', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/preview_form.php');

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
    $PAGE->set_cacheable(false);    // Progress bar is used here.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('preview:heading:process', 'tool_hyperplanningsync'));
    // Just in case.
    require_sesskey();
    $progressbar = new progress_bar();
    $progressbar->create();

    // Lets do this.
    tool_hyperplanningsync_process($formdata, $progressbar);

    $viewlogurl = new moodle_url('/admin/tool/hyperplanningsync/viewlog.php', $pageparams);
    echo $OUTPUT->continue_button($viewlogurl, get_string('continue'), 'get');
    echo $output->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('preview:heading', 'tool_hyperplanningsync'));

$mform->set_data($pageparams);
$mform->display();

list($rows, $totalcount) = tool_hyperplanningsync_get_log($pageparams);

echo $OUTPUT->heading(get_string('preview:results', 'tool_hyperplanningsync', $totalcount), 3);

$renderer = $PAGE->get_renderer('tool_hyperplanningsync');

echo $renderer->display_log($rows, $pageparams, $totalcount, $thisurl);
echo $OUTPUT->footer();
