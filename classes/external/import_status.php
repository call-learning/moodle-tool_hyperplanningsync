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

namespace tool_hyperplanningsync\external;

use core_user\output\myprofile\renderer;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use tool_hyperplanningsync\hyperplanningsync;

/**
 * Renderer for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2022 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_status extends \external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'status' =>
                    new external_multiple_structure(
                        new external_single_structure([
                                'importid' => new external_value(PARAM_INT, 'id of the import'),
                                'importname' => new external_value(
                                    PARAM_TEXT, 'logical name of the import for display', VALUE_DEFAULT, ''),
                                'currentprogress' => new external_value(PARAM_INT, 'current progress'),
                                'countbystatus' => new external_multiple_structure(
                                    new external_single_structure([
                                            'statusname' => new external_value(PARAM_TEXT, 'status name'),
                                            'statusid' => new external_value(PARAM_INT, 'status id'),
                                            'count' => new external_value(PARAM_TEXT, 'record count by status'),
                                        ]
                                    )
                                ),
                                'lateststatus' =>
                                    new external_single_structure([
                                            'userfullname' => new external_value(PARAM_TEXT, 'Username for this status'),
                                            'status' =>
                                                new external_multiple_structure(
                                                    new external_single_structure([
                                                            'timestamp' => new external_value(PARAM_INT,
                                                                'time of the status update'),
                                                            'info' => new external_value(PARAM_TEXT, 'status text to display'),
                                                        ]
                                                    )
                                                )
                                        ]
                                    )
                            ]
                        )
                        , 'all status, can be omitted', VALUE_OPTIONAL)
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Execute task
     *
     * @return object
     */
    public static function execute(): object {
        global $DB;
        $allstats = $DB->get_records_sql("SELECT CONCAT(h.importid, h.status) AS id, h.importid,h.status,  COUNT(*) AS statuscount
                    FROM  {tool_hyperplanningsync_log} h GROUP BY h.importid, h.status");
        $lateststatustext = $DB->get_records_sql("
            SELECT h.importid, h.statustext,h.email
            FROM (SELECT importid, Max(timemodified) as maxtime FROM {tool_hyperplanningsync_log} GROUP BY importid) maxd
            INNER JOIN {tool_hyperplanningsync_log} h ON h.importid = maxd.importid AND h.timemodified = maxd.maxtime");
        $importidstats = [];
        foreach ($allstats as $stats) {
            $importid = $stats->importid;
            if (empty($importidstats[$importid])) {
                $importname = $DB->get_field('tool_hyperplanningsync_info', 'importname', ['importid' => $importid]);
                $fullstatus = (object) [
                    'importid' => $importid,
                    'importname' => $importname ? $importname : ''
                ];
            } else {
                $fullstatus = $importidstats[$importid];
            }

            if (empty($fullstatus->statuscounts)) {
                $fullstatus->statuscounts = [];
            }
            $fullstatus->statuscounts[$stats->status] = (object) [
                'statusid' => $stats->status,
                'statusname' => get_string("status:{$stats->status}", 'tool_hyperplanningsync'),
                'count' => $stats->statuscount
            ];
            $fullstatus->totalcount = array_reduce($fullstatus->statuscounts, function($acc, $status) {
                return $acc + $status->count;
            }, 0);
            $status = json_decode($lateststatustext[$importid]->statustext);
            $fullstatus->lateststatus = (object) [
                'userfullname' => $lateststatustext[$importid]->email,
                'status' => empty($status) ? [] : $status
                ,
            ];
            $importidstats[$importid] = $fullstatus;
        }
        // Map it to the return value.
        $importidstats = array_map(function($val) {
            $returnedval = new \stdClass();
            $returnedval->importid = $val->importid;
            $returnedval->importname = $val->importname;
            $returnedval->currentprogress = 0;
            if ($val->totalcount) {
                if (!empty($val->statuscounts[hyperplanningsync::STATUS_DONE]->count)) {
                    $returnedval->currentprogress = round($val->statuscounts[hyperplanningsync::STATUS_DONE]->count * 100
                        / $val->totalcount);
                }
            }
            $returnedval->countbystatus = empty($val->statuscounts) ? [] : array_values($val->statuscounts);
            $returnedval->lateststatus = $val->lateststatus;
            return $returnedval;
        }, $importidstats);
        if (empty($importidstats)) {
            return (object) []; // Empty object.
        }
        return (object) [
            'status' => array_values($importidstats)
        ];
    }
}
