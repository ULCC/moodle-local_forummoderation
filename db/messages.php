<?php
defined('MOODLE_INTERNAL') || die();

global $CFG;

$messageproviders = array(
    'forummoderation' => array(
        'defaults' => array(
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
        ),
    ),
);
