<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_forummoderation
 * @category    string
 * @copyright   2024 Khairu Aqsara<khairu@teruselearning.co.uk.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Forum Moderation';
$string['reporttext'] = 'Report to moderator';

// set title forum moderation who email recipients
$string["forummoderation:reportenabled"] = "Reporting enabled";
$string['forummoderation:reportenabled_desc'] = 'If enabled users can report forum posts for moderation';
$string["forummoderation:gotopost"] = "Go to Forum Post";

$string["forummoderation:selectedrole"] = "Selectable roles";
$string["forummoderation:selectedrole_desc"] = "These roles will be available for selection to receive notifications";

// set notifcation preferences
$string['messageprovider:forummoderation'] = 'Set report forum moderation notifcation';

// setting page
$string['setting_page:category'] = 'Forum Moderation';
$string['setting_page:forum'] = 'Dashboard Moderator';

// table column
$string["table:column1"] = "Forum Name";
$string["table:column2"] = "Disscusion Name";
$string["table:column3"] = "Report By";
$string["table:column4"] = "Report At";
$string["table:column5"] = "Approved By";
$string["table:column6"] = "Approved At";
$string["table:column7"] = "Action";

// message string
$string["message:approved"] = "This report has been aprroved";
$string["message:deleted"] = "This report has been deleted";
$string["message:reported"] = "Please state your reasons for reporting this post";
$string["message:btnreported"] = "Submit Report";
$string["message:alertreported_title"] = "Thanks for reporting";
$string["message:alertreported_desc"] = "We'll review your report and take action if there is a violation of our Community Guidelines";

// string for template email
$string["message:modal_title"]="Post Flagged to Moderator";
$string["message:modal_body"]="This post has been flagged to Moderator";
