<?php
/**
 *
 * @package    local_forum_moderation
 * @copyright  2023 Cosector ULCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $DB, $USER;
require_once '../../config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/local/forummoderation/lib.php';

if (!isloggedin()) {
    echo '';
    exit;
}

$pageparams = [
    "action" => required_param("action", PARAM_TEXT),
];

// Authentication.
require_course_login($course, false);

if (confirm_sesskey()) {
    if ($pageparams["action"] === 'report-post') {
        $course = get_course(required_param("courseId", PARAM_INT));
        require_course_login($course, false);
        $postid = required_param("postId", PARAM_INT);
       
        $message = required_param("message", PARAM_TEXT);
        $result = local_forummoderation_save_forum($USER->id, $postid, $message,$course);
        if ($result) {
            echo json_encode(local_forummoderation_response("success", true));
        } else {
            echo json_encode(local_forummoderation_response("error", false));
        }
    } else if ($pageparams["action"] === 'check-forumpost-user') {
        $postid = required_param("postId", PARAM_INT);
        $data = local_forummoderation_check_user_comment_post($postid);
        echo json_encode(local_forummoderation_response_record("success", true, $data));
    } else if ($pageparams["action"] === 'check-approved') {
        $data = local_forummoderationi_check_approved($postid);
        echo json_encode($data);
    } else if ($pageparams["action"] === 'send-approved') {
        $approved = required_param("approved", PARAM_INT);
        $result = local_forummoderation_send_approved($postid, $approved, $USER->id);
        if ($result) {
            echo json_encode(local_forummoderation_response("success", true));
        } else {
            echo json_encode(local_forummoderation_response("false", false));
        }
    }
} else {
    echo "not have sesskey valid";
    exit;
}
