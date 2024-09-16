<?php
/**
 * @package     local_forummoderation
 * @copyright   2024 Khairu Aqsara<khairu@teruselearning.co.uk.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/local/forummoderation/classes/string.php';

/**
 * @feature phase 1
 * Extends Moodle's navigation bar based on specific context and configuration.
 * @param global_navigation $nav
 * @return void
 */
function local_forummoderation_extend_navigation(global_navigation $nav)
{
    global $USER, $PAGE, $SESSION, $CFG;
    $config = (int) get_config("local_forummoderation", "moderations");
    $selectedrole = (int) get_config("local_forummoderation", "selectedrole");
    if ($PAGE->context->contextlevel == CONTEXT_MODULE) {

        $PAGE->requires->jquery();
        $PAGE->requires->css(new moodle_url("/local/forummoderation/style.css"));

        if ($config == 1) {
            if ($cm = get_coursemodule_from_id(false, $PAGE->context->instanceid, 0, false) and $cm->modname == 'forum') {
                $PAGE->requires->js_call_amd('local_forummoderation/apps', 'init', ["userid" => $USER->id]);
            }
        }
    }
    if ($PAGE->context->contextlevel == CONTEXT_USER && strpos($PAGE->url, '/message/notificationpreferences.php?userid=' . $USER->id) !== false) {
        $checkrole = local_forummoderation_check_role($selectedrole, $USER->id);
        if ($selectedrole == 0 || !$checkrole) {
            $PAGE->requires->js_call_amd('local_forummoderation/preferences', 'init');
        }
    }
}

/**
 * @feature phase 1
 * Prepares a simple response for forum moderation actions.
 * @param string $message The message to include in the response.
 * @param bool $success Indicates whether the action was successful.
 * @return array An associative array containing the message and success status.
 */
function local_forummoderation_response($message, $success)
{
    return [
        "message" => $message,
        "success" => $success,
    ];
}

/**
 * @feature phase 1
 * Prepares a response with additional data for forum moderation actions.
 * @param string $message The message to include in the response.
 * @param bool $success Indicates whether the action was successful.
 * @param mixed $data Additional data to be included in the response.
 * @return array An associative array containing the message, success status, and data.
 */
function local_forummoderation_response_record($message, $success, $data)
{
    return [
        "message" => $message,
        "success" => $success,
        "data" => $data,
    ];
}

/**
 * @feature phase 2
 * Records the approval status of a forum post.
 * Updates a forum post's approval status in the database, along with the
 * approving user and timestamp.
 *
 * @param int $postid The ID of the forum post.
 * @param int $approved The approval status (1 for approved, 0 for not approved).
 * @param int $userid The ID of the user who approved (or unapproved) the post.
 * @return bool True if the record was successfully inserted, false otherwise.
 */
function local_forummoderation_send_approved($postid, $approved, $userid)
{
    global $DB;

    $forumposts = new stdClass();
    $forumposts->id = $postid;
    $forumposts->approved = $approved;
    $forumposts->approved_by = $userid;
    $forumposts->approved_at = time();
    return $DB->insert_record("local_forummoderation", $forumposts);
}

/**
 * @feature phase 2
 * Checks the approval status of a forum post.
 *
 * @param int $postid The ID of the forum post to check.
 * @return object|false The forum post record if found, false otherwise.
 */
function local_forummoderationi_check_approved($postid)
{
    global $DB;
    $data = $DB->get_record("forum_posts", ["id" => $postid]);
    return $data;
}

/**
 * @feature phase 1
 * Checks if a user is assigned a specific role.
 * @param int $role The ID of the role to check.
 * @param int $userid The ID of the user to check.
 * @return object|false Returns the role assignment record if found, or false if not.
 */
function local_forummoderation_check_role($role, $userid)
{
    global $DB;
    $sql = "
        SELECT role.id,role.name,role.shortname,rs.contextid,rs.contextid FROM {role} as role
        INNER JOIN {role_assignments} as rs ON role.id=rs.roleid
        WHERE role.id=:id and rs.userid=:userid;
    ";
    $checkrole = $DB->get_record_sql($sql, ["id" => $role, "userid" => $userid]);
    return $checkrole;
}

/**
 * @feature phase 1
 * Retrieves users assigned the specified moderation role within the global context.
 * @return array
 */
function local_forummoderation_get_user_role_moderation()
{
    global $DB;
    $selectedroles = get_config("local_forummoderation", "selectedrole");
    $sql = "
        SELECT u.id,u.firstname,u.lastname,r.id as roleid,
        r.shortname,rs.contextid from {user} as u
        INNER JOIN {role_assignments} as rs
        on rs.userid=u.id
        INNER JOIN {role} as r
        ON r.id=rs.roleid
        WHERE r.id=:id and rs.contextid=:contextid";
    $moderation = $DB->get_records_sql($sql, ["id" => $selectedroles, "contextid" => 1]);

    return array_values($moderation);
}

/**
 * @feature phase 1
 * Sends forum post flagging notifications to moderators.
 * Retrieves users with the designated moderation role and sends them notifications
 * (email and/or popup) about a flagged forum post.
 *
 * @param object $course The course object the forum post belongs to.
 * @param int $postid The ID of the flagged forum post.
 */
function local_forummoderation_send($course, $postid)
{
    global $CFG, $DB, $USER;
    require_once $CFG->dirroot . '/course/lib.php';

    $moderation = local_forummoderation_get_user_role_moderation();
    if (empty($moderation)) {
        return;
    }
    $user = $DB->get_record("user", ["id" => $USER->id]);
    $fullname = $user->firstname . " " . $user->lastname;
    $subject = 'New Forum post has been flagged! by ' . $fullname . ' on the ' . $course->fullname . '';

    $users = [];
    foreach ($moderation as $row) {
        $user = $DB->get_record("user", ["id" => $row->id]);
        $user->preference = get_user_preferences("message_provider_local_forummoderation_forummoderation_enabled", null, $user->id);
        $users[] = $user;
    }

    // get value default prefence plugin
    $preferenceplugin = $DB->get_record("config_plugins", ["plugin" => "message", "name" => "message_provider_local_forummoderation_forummoderation_enabled"]);
    foreach ($users as $user) {
        unset($user->password);
        $preference = $user->preference;
        $user->emailnotif = false;
        $user->popupnotif = false;
        // Set default preference to "email" if it's null or empty
        if (empty($preference)) {
            $user->preference = $preferenceplugin->value;
        }

        if (strpos($user->preference, "email") !== false) {
            $user->emailnotif = true;
        }
        if (strpos($user->preference, "popup") !== false) {
            $user->popupnotif = true;
        }
    }
    local_forummoderation_via_email($users, $subject, $postid, $course);
    local_forummoderation_via_notifcation($users, $subject, $course, $postid);
}

/**
 * @feature phase 1
 * Fetches a forum post and its associated moderation data.
 * Retrieves details of a forum post, including any moderation actions (like flagging or approval), from the database.
 *
 * @param int $postid The ID of the forum post.
 * @return object|false The post data including moderation details, or false if not found.
 */
function local_forummoderation_check_user_comment_post($postid)
{
    global $DB, $USER;
    $sql = "
    SELECT fp.id,lf.id as idlocalforum,lf.reported,
    fp.discussion,fp.parent,fp.userid as useridpost,
    lf.message,lf.approved,lf.approved_by,
    lf.approved_at,
    lf.reported,lf.reported_by,lf.reported_at
    FROM {forum_posts} as fp
    LEFT JOIN {local_forummoderation} as lf ON
    fp.id=lf.forumid where fp.id=:id
    ";

    $data = $DB->get_record_sql($sql, ["id" => $postid]);
    return $data;
}

/**
 * @feature phase 1
 * Flags a forum post and sends notifications to moderators (if not already flagged).
 *
 * @param int $userid The ID of the user flagging the post.
 * @param int $postid The ID of the forum post being flagged.
 * @param string $message The message associated with the flag.
 * @param object $course The course object the forum post belongs to.
 * @return bool True if the flag was successfully recorded, false otherwise.
 */
function local_forummoderation_save_forum($userid, $postid, $message, $course)
{
    global $DB;
    $checkexist = $DB->get_record("local_forummoderation", ["forumid" => $postid]);

    if (!$checkexist) {
        local_forummoderation_send($course, $postid);
    }
    $forumpost = new stdClass();
    $forumpost->forumid = $postid;
    $forumpost->userid = $userid;
    $forumpost->message = $message;
    $forumpost->reported = 1;
    $forumpost->reported_by = $userid;
    $forumpost->reported_at = time();
    $result = $DB->insert_record("local_forummoderation", $forumpost);
    return $result;
}

/**
 * @feature phase 1
 * Retrieves details of a forum post for moderation.
 *
 * @param int $postid The ID of the forum post.
 * @return object|false The forum post details (including course and discussion), or false if not found.
 */
function local_forummoderation_post_forum($postid)
{
    global $DB;
    $sql = "
    SELECT fm.id,fm.discussion,fm.subject,f.course,f.name,fm.message,fd.name as postname FROM {forum_posts} as fm
    INNER JOIN {forum_discussions} as fd ON fd.id=fm.discussion
    INNER JOIN {forum} as f ON f.id=fd.forum WHERE fm.id=:postid;
    ";
    $postforum = $DB->get_record_sql($sql, ["postid" => $postid]);
    return $postforum;
}

/**
 * @feature phase 1
 * Sends email notifications to moderators about a flagged forum post.
 *
 * @param array $users An array of user objects representing moderators.
 * @param string $subject The subject of the email notification.
 * @param int $postid The ID of the flagged forum post.
 * @param object $course The course object the forum post belongs to.
 */
function local_forummoderation_via_email($users, $subject, $postid, $course)
{
    global $CFG, $DB, $USER;
    $postforum = local_forummoderation_post_forum($postid);
    $d = $postforum->discussion;
    $filterusers = array_filter($users, function ($user) {
        return $user->emailnotif === true;
    });

    foreach ($filterusers as $key => $user) {
        $from_user = core_user::get_noreply_user();
        $email_user = $user;
        $date = date('H:i d/m/Y', time());
        $linkuser = new moodle_url("/user/profile.php", ["id" => $USER->id]);
        $html_email_template = file_get_contents(new moodle_url('/local/forummoderation/mail.html'));
        $report_link = $CFG->wwwroot . '/mod/forum/discuss.php?d=' . $d . '#p' . $postid . '';
        $linkcourse = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $report_button = "<a href='$report_link' class='report-button' style='color: white;'>" . get_string("forummoderation:gotopost", "local_forummoderation") . "</a>";
        $link_user_herf = "<a href='$linkuser'>$linkuser</a>";
        $url_icon_warning = new moodle_url('/local/forummoderation/warning.png');
        $linkhrefcourse = "<a href='$linkcourse'>$course->fullname</a>";
        $html_email_template = str_replace('{FULLNAME}', "$email_user->firstname $email_user->lastname", $html_email_template);
        $html_email_template = str_replace('{REPORT-BUTTON}', $report_button, $html_email_template);
        $html_email_template = str_replace('{POSTFORUM}', $postforum->postname, $html_email_template);
        $html_email_template = str_replace('{TIMEREPORT}', $date, $html_email_template);
        $html_email_template = str_replace('{BODYEMAIL}', local_forummoderation_string::get("message:email_body"), $html_email_template);
        $html_email_template = str_replace('{LINKUSER}', $link_user_herf, $html_email_template);
        $html_email_template = str_replace('{LINKICON}', $url_icon_warning, $html_email_template);
        $html_email_template = str_replace('{COURSENAME}', $linkhrefcourse, $html_email_template);
        email_to_user($email_user, $from_user, $subject, '', $html_email_template);
    }

}

/**
 * @feature phase 1
 * Sends email notifications to moderators about a flagged forum post.
 *
 * @param array $users An array of user objects representing moderators.
 * @param string $subject The subject of the email notification.
 * @param int $postid The ID of the flagged forum post.
 * @param object $course The course object the forum post belongs to.
 */
function local_forummoderation_via_notifcation($users, $subject, $course, $postid)
{
    global $CFG, $USER;
    $from_user = core_user::get_noreply_user();
    $postforum = local_forummoderation_post_forum($postid);
    $d = $postforum->discussion;

    $filterusers = array_filter($users, function ($user) {
        unset($user->email);
        return $user->popupnotif === true;
    });

    foreach ($filterusers as $key => $user) {
        $email_user = $user;
        $date = date('H:i d/m/Y', time());
        $linkuser = new moodle_url("/user/profile.php", ["id" => $USER->id]);
        $html_email_template = file_get_contents(new moodle_url('/local/forummoderation/notification.html'));
        $report_link = $CFG->wwwroot . '/mod/forum/discuss.php?d=' . $d . '#p' . $postid . '';
        $linkcourse = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $report_button = "<a href='$report_link' class='report-button' style='color: white;'>" . get_string("forummoderation:gotopost", "local_forummoderation") . "</a>";
        $link_user_herf = "<a href='$linkuser'>$linkuser</a>";
        $url_icon_warning = new moodle_url('/local/forummoderation/warning.png');
        $linkhrefcourse = "<a href='$linkcourse'>$course->fullname</a>";
        $html_email_template = str_replace('{FULLNAME}', "$email_user->firstname $email_user->lastname", $html_email_template);
        $html_email_template = str_replace('{REPORT-BUTTON}', $report_button, $html_email_template);
        $html_email_template = str_replace('{POSTFORUM}', $postforum->postname, $html_email_template);
        $html_email_template = str_replace('{TIMEREPORT}', $date, $html_email_template);
        $html_email_template = str_replace('{BODYEMAIL}', local_forummoderation_string::get("message:email_body"), $html_email_template);
        $html_email_template = str_replace('{LINKUSER}', $link_user_herf, $html_email_template);
        $html_email_template = str_replace('{LINKICON}', $url_icon_warning, $html_email_template);
        $html_email_template = str_replace('{COURSENAME}', $linkhrefcourse, $html_email_template);
        // Prepare and send the message
        $eventdata = new \core\message\message();
        $eventdata->component = 'local_forummoderation';
        $eventdata->name = 'forummoderation';
        $eventdata->userfrom = $from_user;
        $eventdata->userto = $email_user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $html_email_template;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = $html_email_template;
        $eventdata->smallmessage = $html_email_template;
        $eventdata->notification = MESSAGE_PERMITTED;
        $eventdata->contexturl = (new moodle_url('/mod/forum/discuss.php', ['d' => $d]))->out() . '#p' . $postid;
        $eventdata->contexturlname = 'View Forum Post';
        $eventdata->courseid = $course->id;
        message_send($eventdata);
    }

}

