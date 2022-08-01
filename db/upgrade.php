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
 * Upgrade hyperplanningsync
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade tool_hyperplanningsync
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_tool_hyperplanningsync_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020052505) {

        // Groups is a reserved word in MySQL 8
        // See MDL-60793 core_ddl: Added the new MySQL 8 reserved words.

        // Rename field groups on table tool_hyperplanningsync_log to groupscsv.
        $table = new xmldb_table('tool_hyperplanningsync_log');
        $field = new xmldb_field('groups', XMLDB_TYPE_TEXT, null, null, null, null, null, 'cohortid');

        // Launch rename field groups.
        $dbman->rename_field($table, $field, 'groupscsv');

        // Hyperplanningsync savepoint reached.
        upgrade_plugin_savepoint(true, 2020052505, 'tool', 'hyperplanningsync');
    }

    if ($oldversion < 2020052506) {

        // Define field pending to be added to tool_hyperplanningsync_log.
        $table = new xmldb_table('tool_hyperplanningsync_log');
        $field = new xmldb_field('pending', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'skipped');

        // Conditionally launch add field pending.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hyperplanningsync savepoint reached.
        upgrade_plugin_savepoint(true, 2020052506, 'tool', 'hyperplanningsync');
    }

    if ($oldversion < 2020052508) {

        // Define table tool_hyperplanningsync_group to be created.
        $table = new xmldb_table('tool_hyperplanningsync_group');

        // Adding fields to table tool_hyperplanningsync_group.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('newgroupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table tool_hyperplanningsync_group.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('course_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('group_fk', XMLDB_KEY_FOREIGN, ['newgroupid'], 'group', ['id']);
        $table->add_key('logid', XMLDB_KEY_FOREIGN, ['logid'], 'tool_hyperplanningsync_log', ['id']);

        // Adding indexes to table tool_hyperplanningsync_group.
        $table->add_index('user_course_group_ix', XMLDB_INDEX_UNIQUE, ['userid', 'courseid', 'newgroupid']);

        // Conditionally launch create table for tool_hyperplanningsync_group.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hyperplanningsync savepoint reached.
        upgrade_plugin_savepoint(true, 2020052508, 'tool', 'hyperplanningsync');
    }
    if ($oldversion < 2022080200) {

        // Rename field statustext on table tool_hyperplanningsync_log to NEWNAMEGOESHERE.
        $table = new xmldb_table('tool_hyperplanningsync_log');
        $field = new xmldb_field('status', XMLDB_TYPE_TEXT, null, null, null, null, null, 'groupscsv');

        // Launch rename field statustext.
        $dbman->rename_field($table, $field, 'statustext');

        // Define field status to be added to tool_hyperplanningsync_log.
        $table = new xmldb_table('tool_hyperplanningsync_log');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'statustext');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pending');

        // Conditionally launch drop field pending.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('processed');

        // Conditionally launch drop field pending.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('skipped');

        // Conditionally launch drop field pending.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Hyperplanningsync savepoint reached.
        upgrade_plugin_savepoint(true, 2022080200, 'tool', 'hyperplanningsync');
    }

    return true;
}
