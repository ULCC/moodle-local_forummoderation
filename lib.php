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
function local_forummoderation_extend_navigation(global_navigation $nav)
{
    global $USER, $PAGE, $SESSION, $CFG;
    $CFG->cachejs = false;
    $config = (int) get_config("local_forummoderation", "moderations");
    $selectedrole = get_config("local_forummoderation", "selectedrole");

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
        if (!has_capability('moodle/user:viewdetails', context_system::instance())) {
            $PAGE->requires->js_call_amd('local_forummoderation/preferences', 'init');
        }

    }

}

function local_forummoderation_response($message, $success)
{
    return [
        "message" => $message,
        "success" => $success,
    ];
}
function local_forummoderation_response_record($message, $success, $data)
{
    return [
        "message" => $message,
        "success" => $success,
        "data" => $data,
    ];
}

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
function local_forummoderationi_check_approved($postid)
{
    global $DB;
    $data = $DB->get_record("forum_posts", ["id" => $postid]);
    return $data;
}
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

function local_forummoderation_get_user_role_moderation()
{
    global $DB;
    $selectedroles = get_config("local_forummoderation", "selectedrole");
    $sql = "
        SELECT u.id,u.firstname,u.lastname,r.id as roleid,r.shortname from mdl_user as u
        INNER JOIN {role_assignments} as rs
        on rs.userid=u.id
        INNER JOIN {role} as r
        ON r.id=rs.roleid
        WHERE r.id=:id";
    $moderation = $DB->get_records_sql($sql, ["id" => $selectedroles]);

    return array_values($moderation);
}
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
    $subject = 'New Forum post has been flagged! by' . $fullname . ' on the ' . $course->fullname . '';

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

function local_forummoderation_check_user_comment_post($postid)
{
    global $DB, $USER;
    $sql = "
    SELECT fp.id,lf.id as idlocalforum,lf.reported,
    fp.discussion,fp.parent,fp.userid as useridpost,
    lf.message,lf.approved,lf.approved_by,
    lf.approved_at,
    lf.reported,lf.reported_by,lf.reported_at
    FROM mdl_forum_posts as fp
    LEFT JOIN mdl_local_forummoderation as lf ON
    fp.id=lf.forumid where fp.id=:id
    ";

    $data = $DB->get_record_sql($sql, ["id" => $postid]);
    return $data;
}

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
function local_forummoderation_post_forum($postid)
{
    global $DB;
    $sql = "
    SELECT fm.id,fm.discussion,fm.subject,f.course,f.name,fm.message,fd.name as postname FROM mdl_forum_posts as fm
    INNER JOIN mdl_forum_discussions as fd ON fd.id=fm.discussion
    INNER JOIN mdl_forum as f ON f.id=fd.forum WHERE fm.id=:postid;
    ";
    $postforum = $DB->get_record_sql($sql, ["postid" => $postid]);
    return $postforum;
}

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
function local_forummoderation_via_notifcation($users, $subject, $course, $postid)
{
    global $CFG;
    $from_user = core_user::get_noreply_user();
    $postforum = local_forummoderation_post_forum($postid);
    $d = $postforum->discussion;

    $filterusers = array_filter($users, function ($user) {
        unset($user->email);
        return $user->popupnotif === true;
    });
    foreach ($filterusers as $key => $user) {
        $email_user = $user;
        $html_email_template = '
                 Dear {FULLNAME},
                <br><br>
                This post was made by the user: {POSTFORUM},
                Click the link below to review this post. Please note that this may have already been deleted or modified by another forum moderator.
                If you see nothing wrong with this post, or encounter an error, this notification can be ignored. {REPORT-LINK}';

        $report_link = $CFG->wwwroot . '/local/forummoderation/viewreport.php?id=3';
        $report_link = "<a href='$report_link'>$report_link</a>";
        $html_email_template = str_replace('{FULLNAME}', "$email_user->firstname $email_user->lastname", $html_email_template);
        $html_email_template = str_replace('{REPORT-LINK}', $report_link, $html_email_template);
        $html_email_template = str_replace('{POSTFORUM}', $postforum->postname, $html_email_template);
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
