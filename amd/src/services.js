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
 * AMD module used when managing enterprise customfields.
 *
 * @module      local_forummoderation
 * @copyright   2024 Yahya (yahya@teruselearning.co.uk)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

class Services {
    constructor(jquery) {
        this.jquery = jquery;
    }

    reportPost(obj, callback) {
        this.jquery({
            url: M.cfg.wwwroot + "/local/forummoderation/ajax.php",
            type: 'POST',
            data: obj,
            processData: false,
            contentType: false,
            success: function (response) {
                callback(response);
            },
            error: function (xhr, status, error) {
                // Handle errors
                callback(error);
            }
        });
    }
    checkForumPost(id, callback) {
        this.jquery({
            url: M.cfg.wwwroot + "/local/forummoderation/ajax.php/"+id,
            type: 'GET',
            processData: false,
            contentType: false,
            success: function (response) {
                callback(response);
            },
            error: function (xhr, status, error) {
                // Handle errors
                callback(error);
            }
        });
    }
}

export default Services;