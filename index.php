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
 * Upload csv for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_hyperplanningsync\hyperplanningsync;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once(dirname(__FILE__) . '/upload_form.php');

$pageoptions = array('pagelayout' => 'report');

$pageparams = array();

admin_externalpage_setup('tool_hyperplanningsync_import', '', $pageparams, '', $pageoptions);
require_capability('tool/hyperplanningsync:manage', context_system::instance());

$fields = hyperplanningsync::get_fields();

$mform = new upload_form();

if ($formdata = $mform->get_data()) {
    $returnurl = $PAGE->url;

    // Filename.
    $content = $mform->get_file_content('userfile');

    // Validate and import.
    if ($importid = hyperplanningsync::do_import($content, $formdata, $returnurl)) {

        // Continue to form2.
        $previewurl = new moodle_url('/admin/tool/hyperplanningsync/preview.php', array('importid' => $importid));

        redirect($previewurl);

    }

} else {
    $formdata = new stdClass();
    foreach ($fields as $fieldname => $default) {
        $formfield = 'field_' . $fieldname;
        $formdata->$formfield = $default;
    }
}

echo $OUTPUT->header();

$heading = get_string('upload:heading', 'tool_hyperplanningsync');

echo $OUTPUT->heading($heading);

$uploadfields = '"' . implode('","', $fields) . '"';

$info = get_string('upload:info', 'tool_hyperplanningsync', $uploadfields);

echo $OUTPUT->notification(format_text($info), 'notifysuccess');

$mform->set_data($formdata);
$mform->display();

echo $OUTPUT->footer();
