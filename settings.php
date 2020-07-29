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
 * Settings for hyperplanningsync
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/locallib.php');

if ($hassiteconfig) {
    $ADMIN->add(
            'accounts',
            new admin_category(
                'hyperplanningsync_menu',
                new lang_string('hyperplanningsync:menu', 'tool_hyperplanningsync')));

    $hyperplanningsyncimport = new admin_externalpage(
        'tool_hyperplanningsync_import',
        get_string('hyperplanningsync:import', 'tool_hyperplanningsync'),
        new moodle_url("/$CFG->admin/tool/hyperplanningsync/index.php"),
        'tool/hyperplanningsync:manage'
    );
    $ADMIN->add('hyperplanningsync_menu', $hyperplanningsyncimport);

    $hyperplanningsynclog = new admin_externalpage(
        'tool_hyperplanningsync_viewlog',
        get_string('hyperplanningsync:viewlog', 'tool_hyperplanningsync'),
        new moodle_url("/$CFG->admin/tool/hyperplanningsync/viewlog.php"),
        'tool/hyperplanningsync:manage'
    );
    $ADMIN->add('hyperplanningsync_menu', $hyperplanningsynclog);

    $hyperplanningsyncsettings = new admin_settingpage(
            'tool_hyperplanningsync_settings',
            get_string('hyperplanningsync:settings', 'tool_hyperplanningsync'),
            'tool/hyperplanningsync:manage');

    $options = array(
        'email' => get_string('email'),
        'idnumber' => get_string('idnumber'),
        'username' => get_string('username'),
    );

    $hyperplanningsyncsettings->add(new admin_setting_configselect(
            'tool_hyperplanningsync/moodle_idfield', // Config name.
            get_string('settings:moodle_idfield', 'tool_hyperplanningsync'), // Label.
            get_string('settings:moodle_idfield_config', 'tool_hyperplanningsync'), // Help.
            'email', // Default.
            $options));

    // Replacement and patterns for group name (see preg_replace).
    $hyperplanningsyncsettings->add(new admin_setting_configtext(
        'tool_hyperplanningsync/group_transform_pattern', // Group name transformation as a regexp
        get_string('settings:group_transform_pattern', 'tool_hyperplanningsync'), // Label.
        get_string('settings:group_transform_pattern', 'tool_hyperplanningsync'), // Help.
        '/(A[0-9]+)\s*gr([0-9]\.[0-9])/i'
    ));

    $hyperplanningsyncsettings->add(new admin_setting_configtext(
        'tool_hyperplanningsync/group_transform_replacement', // Group name replacement (as a regexp).
        get_string('settings:group_transform_replacement', 'tool_hyperplanningsync'), // Label.
        get_string('settings:group_transform_replacement', 'tool_hyperplanningsync'), // Help.
        '\1\3Gr\2'
    ));

    $fields = tool_hyperplanningsync_get_fields();

    $fields = tool_hyperplanningsync_get_fields();
    foreach ($fields as $fieldname => $default) {
        $hyperplanningsyncsettings->add(new admin_setting_configtext(
                'tool_hyperplanningsync/field_' . $fieldname, // Config name.
                get_string('settings:field_' . $fieldname, 'tool_hyperplanningsync'), // Label.
                get_string('settings:field_' . $fieldname . '_config', 'tool_hyperplanningsync'), // Help.
                $default, // Default.
                PARAM_RAW)); // Param type.
    }

    $ADMIN->add('hyperplanningsync_menu', $hyperplanningsyncsettings);

}
