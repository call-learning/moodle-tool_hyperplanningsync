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
 * Renderer for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_hyperplanningsync\output;

use html_writer;
use moodle_url;
use plugin_renderer_base;
use tool_hyperplanningsync\log_table;

defined('MOODLE_INTERNAL') || die;

/**
 * hyperplanningsync renderer
 *
 * @copyright  2020 CALL Learning
 * @author     Russell England <Russell.England@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Display the log
     *
     * @param array $pageparams url parameters and filters
     * @param moodle_url $url
     * @return string - html to output
     */
    public function display_log($pageparams, $url) {
        $table = new log_table(html_writer::random_id('hyperplanning-log'), $pageparams, $url);
        ob_start();
        $table->out($table->pagesize, true);
        $o = ob_get_contents();
        ob_end_clean();
        return $o;
    }

}
