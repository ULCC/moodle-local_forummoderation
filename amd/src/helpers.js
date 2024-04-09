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

import GetString from "./strings";
import Services from "./services";
import $ from "jquery";
import ModalFactory from 'core/modal_factory';

/**
 * AMD module used when managing enterprise customfields.
 *
 * @module      local_forummoderation
 * @copyright   2024 Yahya (yahya@teruselearning.co.uk)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

class Helpers {
    createSpinner(classname) {
        const spinner = document.createElement("div");
        spinner.className = classname;
        return spinner;
    }

    async checkForumPostUser(postId) {
        return new Promise(resolve => {
            new Services($.ajax).checkForumPostUser(postId, res => {
                resolve(JSON.parse(res).data);
            });
        });
    }

    async modalNotification() {
        const strings = new GetString();
        const title = await strings.get("message:alertreported_title");
        const message = await strings.get("message:alertreported_desc");
        const alert = new Helpers().createAlert("", title, message);
        const titlemodal = await strings.get("message:modal_title");

        const modal = await ModalFactory.create({
            title: titlemodal,
            body: alert,
            footer: 'Footer',
        }, $('a.item-delete'));
        return modal;
    }
    async createLinkReportModerator(user) {
        let postActionDivs = document.querySelectorAll('div.post-actions');
        // let discussname = document.querySelector("h3.discussionname");
        // const strings = new GetString();
        // const title = await strings.get("message:alertreported_title");
        // const message = await strings.get("message:alertreported_desc");
        // const alert = new Helpers().createAlert("alert-info", title, message);
        // add element after discussname
        if (localStorage.getItem("success") !== null) {
            const modal = await this.modalNotification();
            modal.show();
        }
        localStorage.removeItem("success");
        postActionDivs.forEach(async (postActionDiv) => {
            let allLinks = postActionDiv.querySelectorAll('a');
            var linksWithDataPostId = Array.from(allLinks).filter(function (link) {
                return link.hasAttribute('data-post-id');
            });

            if (linksWithDataPostId.length > 0) {
                var postId = linksWithDataPostId[0].getAttribute('data-post-id');
                // hide report to moderator in own comment
                const forumPostUserData = await this.checkForumPostUser(postId);
                var newLink = document.createElement('a');
                newLink.href = '#';
                newLink.classList.add('btn', 'btn-link');
                newLink.textContent = 'Report to moderator';
                newLink.setAttribute("data-action", "forummoderation-reportpost");
                newLink.setAttribute('data-post-id', postId);
                const reported_by = (forumPostUserData.reported_by) ? forumPostUserData.reported_by : 0;
                newLink.setAttribute("data-reported", reported_by);
                const useridpost = parseInt(forumPostUserData.useridpost);
                const userid = parseInt(user);

                if (userid != useridpost) {
                    postActionDiv.insertBefore(newLink, postActionDiv.firstChild);
                }



            }
        });
    }

    createAlert(type, title, message) {
        // Create the alert div element
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert ' + type;
        alertDiv.setAttribute('role', 'alert');

        // Create the heading element
        var heading = document.createElement('h4');
        heading.className = 'alert-heading';

        heading.textContent = title;
        alertDiv.appendChild(heading);

        // Create the paragraph element for the main content
        var mainContent = document.createElement('p');
        mainContent.textContent = message;
        alertDiv.appendChild(mainContent);

        // Create the horizontal rule element
        alertDiv.appendChild(document.createElement('hr'));

        // create button okay
        var button = document.createElement("button");
        button.setAttribute("type", "button");
        button.classList.add("btn", "btn-ok-forummoderation");
        button.textContent = "Ok";
        button.setAttribute("data-action", "hide");
        alertDiv.appendChild(button);
        return alertDiv;
    }

}
export default Helpers;