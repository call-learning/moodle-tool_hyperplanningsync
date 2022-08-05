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
 * Javascript to initialise the enva syllabus catalog page.
 *
 * @package    tool_hyperplanningsync
 * @copyright  2022 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {exception as displayException} from 'core/notification';
import Templates from "core/templates";
import Ajax from 'core/ajax';

const timeout = 10000; // 10 seconds.
/**
 * Initialise page
 * @param {string} tagId
 */
export const init = (tagId) => {
    refreshPage(tagId); // First refresh.
    setInterval(() => refreshPage(tagId), timeout);
};

/**
 * Refresh the page
 *
 * @param {string} tagId
 */
const refreshPage = (tagId) => {
    const element = document.querySelector('#' + tagId);
    getCurrentStatus().then(
        (status) =>
            Templates.render('tool_hyperplanningsync/import_status', status).then((html, js) => {
                Templates.replaceNodeContents(element, html, js);
            }).catch(displayException)).catch(displayException);
};

const getCurrentStatus = function () {
    let request = {
        methodname: 'tool_hyperplanningsync_import_status',
        args: {}
    };
    return Ajax.call([request])[0];
};
