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
 * Upload form for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Class upload_form
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_form extends moodleform {

    /**
     * Form definition
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'form_heading', get_string('upload:heading:file', 'tool_hyperplanningsync'));

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'upload_delimiter', get_string('upload:delimiter', 'tool_hyperplanningsync'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('upload_delimiter', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('upload_delimiter', 'semicolon');
        } else {
            $mform->setDefault('upload_delimiter', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'upload_encoding', get_string('upload:encoding', 'tool_hyperplanningsync'), $choices);
        $mform->setDefault('upload_encoding', 'UTF-8');

        $mform->addElement('header', 'field_heading', get_string('upload:heading:field', 'tool_hyperplanningsync'));

        $options = array(
            'email' => get_string('email'),
            'idnumber' => get_string('idnumber'),
            'username' => get_string('username'),
        );
        $mform->addElement('select', 'moodle_idfield', get_string('upload:moodle_idfield', 'tool_hyperplanningsync'), $options);
        $mform->setType('moodle_idfield', PARAM_TEXT);
        $mform->setDefault('moodle_idfield', get_config('tool_hyperplanningsync', 'moodle_idfield'));
        $mform->addRule('moodle_idfield', get_string('required'), 'required', null, 'client');

        $fields = tool_hyperplanningsync_get_fields();

        foreach ($fields as $fieldname => $ignore) {
            $mform->addElement('text', 'field_' . $fieldname, get_string('upload:' . $fieldname, 'tool_hyperplanningsync'));
            $mform->setType('field_' . $fieldname, PARAM_TEXT);
            $mform->addRule('field_' . $fieldname, get_string('required'), 'required', null, 'client');
        }

        $mform->addElement('header', 'field_heading', get_string('upload:heading:settings', 'tool_hyperplanningsync'));

        $mform->addElement('advcheckbox', 'ignoregroups', get_string('upload:ignoregroups', 'tool_hyperplanningsync'));
        $mform->setDefault('ignoregroups', true);
        $mform->setType('ignoregroups', PARAM_BOOL);
        $mform->addHelpButton('ignoregroups', 'upload:ignoregroups', 'tool_hyperplanningsync');

        $mform->addElement('text', 'group_transform_pattern',
            get_string('upload:group_transform_pattern', 'tool_hyperplanningsync'));
        $mform->setType('group_transform_pattern', PARAM_TEXT);
        $mform->setDefault('group_transform_pattern', get_config('tool_hyperplanningsync', 'group_transform_pattern'));

        $mform->addElement('text', 'group_transform_replacement',
            get_string('upload:group_transform_replacement', 'tool_hyperplanningsync'));
        $mform->setType('group_transform_replacement', PARAM_TEXT);
        $mform->setDefault('group_transform_replacement', get_config('tool_hyperplanningsync', 'group_transform_replacement'));

        $this->add_action_buttons(false, get_string('upload:btn', 'tool_hyperplanningsync'));
    }
}

