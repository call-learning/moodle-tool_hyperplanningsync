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

$string['error:invalidfieldname'] = 'Invalid field name in CSV : {$a}';
$string['error:missingfield'] = 'Missing field in CSV : {$a}';
$string['error:nocohort'] = 'Cohort not found';
$string['error:nogroup'] = 'Group not found for this idnumber : {$a}';
$string['error:nogroupincourse'] = 'No group found in cohort sync for this idnumber : {$a}';
$string['error:nogroups'] = 'No groups found';
$string['error:nouser'] = 'User not found';
$string['filter:btn'] = 'Filter';
$string['filter:heading'] = 'Filter';
$string['hyperplanningsync:import'] = 'Import CSV';
$string['hyperplanningsync:manage'] = 'Allow access to Hyperplanning Sync Tool';
$string['hyperplanningsync:menu'] = 'Hyperplanning Sync';
$string['hyperplanningsync:settings'] = 'Settings';
$string['hyperplanningsync:viewlog'] = 'Import Log';
$string['pluginname'] = 'Hyperplanning Import Tool';
$string['preview:heading'] = 'Hyperplanning : Preview CSV file';
$string['preview:results'] = 'Preview results ({$a})';
$string['privacy:metadata'] = 'TODO: The Hyperplanning Sync tool stores email or idnumber or username and the userid + userid of importer .';
$string['process:addedcohort'] = 'Added user to cohort "{$a->name}" ({$a->idnumber})';
$string['process:addedgroup'] = 'Added user to group "{$a->groupname}" ({$a->groupid}) in course "{$a->coursename}" ({$a->courseid})';
$string['process:btn'] = 'Process';
$string['process:done'] = 'Processing done';
$string['process:heading'] = 'Process options';
$string['process:notenrolled'] = 'User not enrolled on course "{$a->coursename}" ({$a->courseid}), unable to add user to group "{$a->groupname}" ({$a->groupid})';
$string['process:removecohorts'] = 'Remove from existing cohorts?';
$string['process:removedcohort'] = 'Removed user from cohort "{$a->name}" ({$a->idnumber})';
$string['process:removedgroup'] = 'Removed user from group "{$a->groupname}" ({$a->groupid}) in course "{$a->coursename}" ({$a->courseid})';
$string['process:removegroups'] = 'Remove from existing groups?';
$string['process:started'] = 'Processing start';
$string['report:cohort'] = 'Cohort ID Number';
$string['report:cohortid'] = 'Matching Cohort Name';
$string['report:createdbyid'] = 'Imported by';
$string['report:groups'] = 'Cleaned Group ID numbers';
$string['report:idfield'] = 'ID field';
$string['report:idvalue'] = 'ID value';
$string['report:importid'] = 'Import ID';
$string['report:lineid'] = 'Line number';
$string['report:maingroup'] = 'Main Group';
$string['report:othergroups'] = 'Other Groups';
$string['report:skipped'] = 'Skipped?';
$string['report:status'] = 'Status';
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
$string['upload:btn'] = 'Upload';
$string['upload:cohort'] = 'Cohort field name';
$string['upload:delimiter'] = 'CSV delimiter';
$string['upload:encoding'] = 'Encoding';
$string['upload:heading'] = 'Hyperplanning : Upload CSV file';
$string['upload:heading:field'] = 'Field names';
$string['upload:heading:file'] = 'File details';
$string['upload:idfield'] = 'ID field name';
$string['upload:maingroup'] = 'Main group field name';
$string['upload:moodle_idfield'] = 'Moodle ID field';
$string['upload:othergroups'] = 'Other groups field name';
$string['viewlog:heading'] = 'Import log';
$string['viewlog:results'] = 'Search results ({$a})';


$string['upload:info'] = 'Users may be uploaded via text file. The format of the file should be as follows:

* Each line of the file contains one record
* Each line contains data separated by commas (or other delimiters)
* The first line contains a list of fieldnames
* The fieldnames are {$a}';
