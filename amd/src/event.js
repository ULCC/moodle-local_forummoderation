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


import $ from "jquery";
import Selectors from "./selectors";
import Services from "./services";
import CustomFormData from "./customformdata";
import Helpers from "./helpers";
class Event {
    constructor() {
        this.reportPostSelector = Selectors.actions.reportPost;
        this.eventListeners = {};
    }

    init() {
        new Helpers().createLinkReportModerator();
    }

    addEvent(eventType, handler) {
        if (!this.eventListeners[eventType]) {
            this.eventListeners[eventType] = [];
            document.addEventListener(eventType, (evt) => {
                this.eventListeners[eventType].forEach((listener) => {
                    if (evt.target.closest(listener.selector)) {
                        listener.handler(evt);
                    }
                });
            });
        }
        this.eventListeners[eventType].push({ selector: this.reportPostSelector, handler: handler });
    }

    reportPostForum(evt) {
        evt.preventDefault();
        const spinner = new Helpers().createSpinner("spinner");
        const parentElement = evt.target;
        parentElement.insertAdjacentElement('afterend', spinner);
        $(evt.target).hide();
        $(spinner).show();

        const postId = parentElement.getAttribute("data-post-id");
        const message = "";
        const formData = this.prepareFormDataReport(postId, message);
        new Services($.ajax).reportPost(formData, res => {
            const { success } = JSON.parse(res);
            if (success) {
                window.location.reload();
            }
        });
    }

    prepareFormDataReport(postId, message) {
        return new CustomFormData({
            "postId": parseInt(postId),
            "courseId": M.cfg.courseId,
            "contextId": M.cfg.contextid,
            "courseContextId": M.cfg.courseContextId,
            "contextInstanceId": M.cfg.contextInstanceId,
            "action": "report-post",
            "message": message,
            "sesskey": M.cfg.sesskey
        }).toFormData();
    }
}

export default Event;