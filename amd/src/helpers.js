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

class Helpers {
    createSpinner(classname) {
        const spinner = document.createElement("div");
        spinner.className = classname;
        return spinner;
    }
    createLinkReportModerator() {
        let postActionDivs = document.querySelectorAll('div.post-actions');
        postActionDivs.forEach((postActionDiv) => {
            let allLinks = postActionDiv.querySelectorAll('a');
            var linksWithDataPostId = Array.from(allLinks).filter(function (link) {
                return link.hasAttribute('data-post-id');
            });

            if (linksWithDataPostId.length > 0) {
                var postId = linksWithDataPostId[0].getAttribute('data-post-id');
                var newLink = document.createElement('a');
                newLink.href = '#';
                newLink.classList.add('btn', 'btn-link');
                newLink.textContent = 'Report to moderator';
                newLink.setAttribute("data-action", "forummoderation-reportpost");
                newLink.setAttribute('data-post-id', postId);
                postActionDiv.insertBefore(newLink, postActionDiv.firstChild);
            }
        });
    }
}
export default Helpers;