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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
global $CFG, $OUTPUT, $DB, $PAGE;
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/filter_form.php');

$pageparams = array();

$pageparams['importid'] = optional_param('importid', '', PARAM_ALPHANUM);
$pageparams['idvalue'] = optional_param('idvalue', '', PARAM_TEXT);
$pageparams['cohort'] = optional_param('cohort', '', PARAM_TEXT);
$pageparams['pagenum'] = optional_param('pagenum', 0, PARAM_INT);
$pageparams['status'] = optional_param('status', -1, PARAM_INT);

$delete = optional_param('delete', '', PARAM_ALPHA);
$confirm = optional_param('confirm', false, PARAM_BOOL);

$pageoptions = array('pagelayout' => 'report');

$thisurl = new moodle_url('/admin/tool/hyperplanningsync/viewlog.php');

admin_externalpage_setup('tool_hyperplanningsync_viewlog', '', $pageparams, $thisurl, $pageoptions);

$progressbutton = new single_button(
    new moodle_url(new moodle_url('/admin/tool/hyperplanningsync/progress.php')),
    get_string('hyperplanningsync:progress', 'tool_hyperplanningsync'),
    true,
);
$viewprogress = $OUTPUT->render($progressbutton);

$deletelastbutton = new single_button(
    new moodle_url($thisurl, array('delete' => 'partial', 'sesskey' => sesskey())),
    get_string('viewlog:deletepartial', 'tool_hyperplanningsync'),
);
$deletelastbutton->class = $deletelastbutton->class . ' btn-danger';
$deletelast = $OUTPUT->render($deletelastbutton);


$deleteallbutton = new single_button(
    new moodle_url($thisurl, array('delete' => 'all', 'sesskey' => sesskey())),
    get_string('viewlog:deleteall', 'tool_hyperplanningsync'),
);
$deleteallbutton->class = $deleteallbutton->class . ' btn-danger';
$deleteallbutton = $OUTPUT->render($deleteallbutton);

$extrabuttons = $OUTPUT->box( $viewprogress . $deletelast . $deleteallbutton);

$PAGE->set_button($extrabuttons . $PAGE->button);
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('viewlog:heading', 'tool_hyperplanningsync'));

if (!empty($delete)) {
    if (!$confirm) {
        $confirmmsg = get_string('viewlog:deleteconfirm' . $delete, 'tool_hyperplanningsync');
        $continueurl = new moodle_url('/admin/tool/hyperplanningsync/viewlog.php',
            array('confirm' => true, 'delete' => $delete, 'sesskey' => sesskey()));
        $cancelurl = new moodle_url('/admin/tool/hyperplanningsync/viewlog.php');

        echo $OUTPUT->confirm($confirmmsg, $continueurl, $cancelurl);
        echo $OUTPUT->footer();
        die();

    } else {
        require_sesskey();

        switch ($delete) {
            case 'all':
                $DB->delete_records('tool_hyperplanningsync_log');

                echo $OUTPUT->notification(get_string('viewlog:deletedall', 'tool_hyperplanningsync'), 'notifysuccess');
                break;

            case 'partial':
                // Just delete all except the last log.
                $sql = "SELECT MAX(a.importid)
                        FROM {tool_hyperplanningsync_log} a";
                $importid = $DB->get_field_sql($sql);

                $DB->delete_records_select('tool_hyperplanningsync_log', 'importid <> :importid', array('importid' => $importid));

                echo $OUTPUT->notification(get_string('viewlog:deletedpartial', 'tool_hyperplanningsync'), 'notifysuccess');
                break;

        }

    }
}
$mform = new filter_form();
$mform->set_data($pageparams);
$mform->display();

echo $OUTPUT->heading(get_string('viewlog:results', 'tool_hyperplanningsync'), 3);

$renderer = $PAGE->get_renderer('tool_hyperplanningsync');

echo $renderer->display_log($pageparams, $thisurl);

echo $OUTPUT->footer();
