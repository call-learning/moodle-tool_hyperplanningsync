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
 * Strings used by the hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cli:listofimports'] = 'List of imports';
$string['error:invalidfieldname'] = 'Invalid field name in CSV : {$a}';
$string['error:missingfield'] = 'Missing field in CSV : {$a}';
$string['error:nocohort'] = 'Cohort not found: {$a}';
$string['error:nogroup'] = 'Group not found for this id : {$a}';
$string['error:nogroupincourse'] = 'No group found in cohort sync for this idnumber : {$a}';
$string['error:nogroups'] = 'No groups found';
$string['error:nouser'] = 'User not found';
$string['filter:btn'] = 'Filter';
$string['filter:heading'] = 'Filter';
$string['hyperplanningsync:import'] = 'Import CSV';
$string['hyperplanningsync:manage'] = 'Allow access to Hyperplanning Sync Tool';
$string['hyperplanningsync:menu'] = 'Hyperplanning Sync';
$string['hyperplanningsync:progress'] = 'Hyperplanning Progress Status';
$string['hyperplanningsync:settings'] = 'Settings';
$string['hyperplanningsync:viewlog'] = 'View Import Log';
$string['hyperplanningsync:progress:single'] = 'Progress Status for {$a}';
$string['hyperplanningsync:lateststatus'] = 'Latest status';
$string['noimportations'] = 'No importations for now...';
$string['pluginname'] = 'Hyperplanning Import Tool';
$string['preview:heading'] = 'Hyperplanning : Preview CSV file';
$string['preview:heading:process'] = 'Hyperplanning : Process CSV file';
$string['preview:results'] = 'Preview results ({$a})';
$string['privacy:metadata'] =
    'TODO: The Hyperplanning Sync tool stores email or idnumber or username and the userid + userid of importer .';
$string['process:addedcohort'] = 'Added user to cohort "{$a->name}" ({$a->idnumber})';
$string['process:addedgroup'] =
    'Added user to group "{$a->groupname}" ({$a->groupid}) in course "{$a->coursename}" ({$a->courseid})';
$string['process:btn'] = 'Process';
$string['process:done'] = 'Processing done';
$string['process:heading'] = 'Process options';
$string['process:notenrolled'] =
    'User not enrolled on course "{$a->coursename}" ({$a->courseid}), unable to add user to group "{$a->groupname}" ({$a->groupid})';
$string['process:removecohorts'] = 'Remove from existing cohorts?';
$string['process:removedcohort'] = 'Removed user from cohort "{$a->name}" ({$a->idnumber})';
$string['process:removedgroup'] =
    'Removed user from group "{$a->groupname}" ({$a->groupid}) in course "{$a->coursename}" ({$a->courseid})';
$string['process:removegroups'] = 'Remove from existing groups?';
$string['process:started'] = 'Processing start';
$string['process:usercreated'] = 'Pending user created';
$string['process:progress'] = 'Processing import';
$string['report:cohort'] = 'Cohort ID Number';
$string['report:cohortid'] = 'Matching Cohort Name';
$string['report:createdbyid'] = 'Imported by';
$string['report:groupscsv'] = 'Cleaned Group ID numbers';
$string['report:fullname'] = 'ID field';
$string['report:idvalue'] = 'ID Number';
$string['report:importid'] = 'Import ID';
$string['report:lineid'] = 'Line number';
$string['report:maingroup'] = 'Main Group';
$string['report:othergroups'] = 'Other Groups';
$string['report:status'] = 'Status';
$string['report:timemodified'] = 'Modified';
$string['status:0'] = 'Not processed';
$string['status:1'] = 'Skipped';
$string['status:2'] = 'Pending User';
$string['status:10'] = 'Processing';
$string['status:100'] = 'Processed';
$string['report:statustext'] = 'Status (text)';
$string['report:timecreated'] = 'Time imported';
$string['report:userid'] = 'Matching User';
$string['settings:field_cohort'] = 'Default name for the cohort field';
$string['settings:field_cohort_config'] = 'This is the field name in the header of the csv';
$string['settings:field_idfield'] = 'Default name for the ID field';
$string['settings:field_idfield_config'] = 'This is the field name in the header of the csv';
$string['settings:field_maingroup'] = 'Default name for the main group field';
$string['settings:field_maingroup_config'] = 'This is the field name in the header of the csv';
$string['settings:field_othergroups'] = 'Default name for the other groups field';
$string['settings:field_othergroups_config'] = 'This is the field name in the header of the csv';
$string['settings:moodle_idfield'] = 'Moodle ID field';
$string['settings:moodle_idfield_config'] = 'Which Moodle ID field to map against?';
$string['settings:group_transform_pattern'] = 'Group name (pattern)';
$string['settings:group_transform_pattern_config'] = 'Regexp for group name (pattern) transformation,
 if any (leave it empty if you don\'t want any)';
$string['settings:group_transform_replacement'] = 'Regexp replacement for group name';
$string['settings:group_transform_replacement_config'] = 'Regexp replacement for group name,
 if any (leave it empty if you don\'t want any)';
$string['settings:sync_new_users_enabled'] = 'Synchronise new users at creation';
$string['upload:importnname'] = 'Import name';
$string['upload:btn'] = 'Upload';
$string['upload:cohort'] = 'Cohort field name';
$string['upload:delimiter'] = 'CSV delimiter';
$string['upload:encoding'] = 'Encoding';
$string['upload:heading'] = 'Hyperplanning : Upload CSV file';
$string['upload:heading:field'] = 'Field names';
$string['upload:heading:file'] = 'File details';
$string['upload:heading:settings'] = 'Additional settings';
$string['upload:ignoregroups'] = 'Ignore missing groups?';
$string['upload:ignoregroups_help'] = 'Unchecked : If any group is missing then the row will be skipped.

Checked: Missing groups will be ignored and the row will still be processed.';
$string['upload:idfield'] = 'ID field name';
$string['upload:maingroup'] = 'Main group field name';
$string['upload:moodle_idfield'] = 'Moodle ID field';
$string['upload:othergroups'] = 'Other groups field name';
$string['upload:group_transform_pattern'] = 'Regexp for group name (pattern)';
$string['upload:group_transform_replacement'] = 'Regexp replacement for group name';
$string['viewlog:deleteall'] = 'Clear all import logs';
$string['viewlog:deletepartial'] = 'Clear all except latest import log';
$string['viewlog:deleteconfirmall'] = 'Are you sure you want to clear all import logs?';
$string['viewlog:deleteconfirmpartial'] = 'Are you sure you want to clear all except latest import log?';
$string['viewlog:deletedall'] = 'Cleared all import logs';
$string['viewlog:deletedpartial'] = 'Cleared all except latest import log';
$string['viewlog:heading'] = 'Import log';
$string['viewlog:results'] = 'Search results';

$string['upload:info'] = 'Users may be uploaded via text file. The format of the file should be as follows:

* Each line of the file contains one record
* Each line contains data separated by commas (or other delimiters)
* The first line contains a list of fieldnames
* The fieldnames are {$a}';
