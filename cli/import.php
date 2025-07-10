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
 * Run the import through CLI
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
use tool_hyperplanningsync\hyperplanningsync;

global $CFG;

require_once($CFG->libdir . '/clilib.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

list($options, $unrecognised) = cli_get_params(
    [
        'getimportids' => false,
        'runimportid' => '',
        'removecohorts' => false,
        'removegroups' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
        'i' => 'getimportids',
        'r' => 'runimportid',
    ]
);

// Checking run.php CLI script usage.
$help = "
CLI Script to import an already parsed import from the CLI

Usage:
  php import.php  [--getimportids]  [--runimportid]  [--removecohorts]  [--removegroups] [--help]

Options:
--getimportids     Get a list of available import Id
--runimportid      Run a given import
--removecohorts     Remove user from cohort
--removegroups     Remove user from group
-h, --help         Print out this help

Example from Moodle root directory:
\$ php admin/tool/hyperplanning/cli/import.php --runimportid=1324232
";

if (!empty($options['help'])) {
    echo $help;
    exit(0);
}
$unprocessedimports = hyperplanningsync::get_unprocessed_importids();
$allimportids = array_map(function($imp) {
    return $imp->importid;
}, $unprocessedimports);

$targetimport = 0;
if ($options['getimportids']) {
    cli_heading(get_string('cli:listofimports', 'tool_hyperplanningsync'));
    foreach ($unprocessedimports as $imp) {
        echo $imp->importid . "\t" . userdate($imp->timecreated) . "\n";
    }
    exit(0);
} else if ($importid = $options['runimportid']) {

    if (in_array($targetimport, $allimportids)) {
        $allimportids = [$importid];
    }
}
$progressbar = new text_progress_trace();

foreach ($allimportids as $impid) {
    hyperplanningsync::process($impid, $options['removecohorts'], $options['removegroups'], $progressbar, null, false);
}
cli_heading(get_string('success'));
exit(0);

