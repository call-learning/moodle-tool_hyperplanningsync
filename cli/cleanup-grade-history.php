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
 * Remove older grades history in order to accelerate the cohort migration process.
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David (laurent@call-learning.fr)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
global $CFG;

require_once($CFG->libdir . '/clilib.php');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

list($options, $unrecognised) = cli_get_params(
    [
        'gradehistoryyears' => 5,
        'help' => false,
    ],
    [
        'h' => 'help',
        'g' => 'gradehistoryyears',
    ]
);

// Checking run.php CLI script usage.
$help = "
CLI command to remove grade history from more than 5 years ago.

Usage:
  php cleanup-grade-history.php  [--gradehistoryyears=5] [--help]

Options:
--gradehistorylifetime grade history lifetime
-h, --help         Print out this help

Example from Moodle root directory:
\$ php admin/tool/hyperplanning/cli/import.php --runimportid=1324232
";

if (!empty($options['help'])) {
    echo $help;
    exit(0);
}
$now = time();
$gradehistoryyears = $options['gradehistoryyears'] ?? 7;
$histlifetime = $now - ($gradehistoryyears * YEARSECS);
$tables = [
    'grade_outcomes_history',
    'grade_categories_history',
    'grade_items_history',
    'grade_grades_history',
    'scale_history',
];
global $DB;
foreach ($tables as $table) {
    $beforecount = $DB->count_records($table);
    cli_writeln("{$table}  - Number of records before : " . $beforecount);
    if ($DB->delete_records_select($table, "timemodified < ?", [$histlifetime])) {
        cli_writeln("    Deleted old grade history records from '$table'");
    }
    $aftercount = $DB->count_records($table);
    $difference = $beforecount - $aftercount;
    cli_writeln("{$table}  - Number of records before: {$aftercount} - Difference : {$difference}" );
}
cli_heading(get_string('success'));
exit(0);

