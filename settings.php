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
 * Plugin administration pages are defined here.
 *
 * @package     local_forummoderation
 * @category    admin
 * @copyright   2024 Khairu Aqsara<khairu@teruselearning.co.uk.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once $CFG->dirroot . '/local/forummoderation/lib.php';
require_once $CFG->dirroot . '/local/forummoderation/classes/string.php';

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_forummoderation_settings', new lang_string('pluginname', 'local_forummoderation'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {

        $choose = [
            "1" => "Yes",
            "0" => "No",
        ];
        $settings->add(new admin_setting_configselect(
            'local_forummoderation/moderations',
            local_forummoderation_string::get('forummoderation:reportenabled'),
            local_forummoderation_string::get('forummoderation:reportenabled_desc'),
            0, $choose
        ));

        $roles = get_all_roles();

        $customrole = [];

        $customrole[0] = "Set Role";
        foreach ($roles as $key => $role) {
            if ($role->name === '') {
                $customrole[$role->id] = $role->shortname;
            } else {
                $customrole[$role->id] = $role->name;
            }
        }
        // Initialize an array to hold role options
        $rolesoptions = array();

        $settings->add(
            new admin_setting_configselect(
                'local_forummoderation/selectedrole',
                local_forummoderation_string::get('forummoderation:selectedrole'),
                local_forummoderation_string::get('forummoderation:selectedrole_desc'),
                0, // Default.
                $customrole
            )
        );

    }
    $ADMIN->add('localplugins', $settings);
}
