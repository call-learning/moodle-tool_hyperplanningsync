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
 * Filter form for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Class filter_form
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_form extends moodleform {
    /**
     * Form definition
     *
     * @throws coding_exception
     */
    public function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'form_heading', get_string('filter:heading', 'tool_hyperplanningsync'));

        $mform->addElement('text', 'importid', get_string('report:importid', 'tool_hyperplanningsync'));
        $mform->setType('importid', PARAM_INT);

        $mform->addElement('text', 'idvalue', get_string('report:idvalue', 'tool_hyperplanningsync'));
        $mform->setType('idvalue', PARAM_TEXT);

        $mform->addElement('text', 'cohort', get_string('report:cohort', 'tool_hyperplanningsync'));
        $mform->setType('cohort', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('filter:btn', 'tool_hyperplanningsync'));
    }

}