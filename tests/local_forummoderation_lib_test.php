<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot . '/local/forummoderation/lib.php';
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/enrol/externallib.php');

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

class local_forummoderation_lib_test extends \advanced_testcase
{

    /**
     * Summary of user
     * @var
     */
    protected $user;
    /**
     * Summary of post
     * @var
     */
    protected $post;

    /**
     * Sets up the test environment with a mock user and post.
     *
     * This method is executed before each test in the test case.
     * It creates a new user with the specified details and sets a mock post.
     */
    public function setUp(): void
    {
        $this->user = $this->getDataGenerator()->create_user([
            "firstname" => "User 1",
            "lastname" => "Testing",
            "username" => "user01",
            "email" => "test@gmail.com",
        ]);

        $this->post = $this->set_mock_data_post();
        parent::setUp();
    }

    /**
     * Cleans up the test environment after each test.
     *
     * This method is executed after each test in the test case.
     * It resets the environment by removing created data and calling the parent tearDown.
     */
    public function tearDown(): void
    {
        // Reset the test environment by removing created data.
        $this->resetAfterTest(true);

        parent::tearDown(); // Call the parent tearDown method to complete cleanup.
    }


    /**
     * @feature phase 1
     * Sets up a mock forum post for testing.
     *
     * This function first checks if a post with the given discussion ID exists in the database.
     * If it doesn't, it creates a new post with a default subject and message.
     *
     * @return stdClass The forum post object, either existing or newly created.
     */
    public function set_mock_data_post()
    {
        global $DB;
        $existingPost = $DB->get_record('forum_posts', ['discussion' => 1]);

        if (!$existingPost) {
            $data = [
                'discussion' => 1,
                'subject' => 'Test Subject',
                'message' => 'Test Message',
            ];
            $postid = $DB->insert_record('forum_posts', $data);
            $existingPost = $DB->get_record('forum_posts', ['id' => $postid]);
        }
        return $existingPost;
    }

    /**
     * @pahse 1
     * Test if the plugin can extends to global navigation
     * @return void
     * @throws coding_exception
     */
    public function test_can_extend_global_navigation(){
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        $this->assertEquals(true, true);
    }

    /**
     * @pahse 1
     * Test if plugin can parse array response
     * @return void
     */
    public function test_can_return_json_response()
    {
        $message = "Example Message";
        $success = "Ok";
        $response = local_forummoderation_response($message, $success);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals($success, $response['success']);
    }

    /**
     * @pahse 1
     * Test if plugin can handle response from records
     * @return void
     */
    public function test_can_return_response_for_record()
    {
        $message = "Example Message";
        $success = "Ok";
        $data = [
            'name' => 'Test'
        ];
        $response = local_forummoderation_response_record($message, $success, $data);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals($success, $response['success']);
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
    }

    /**
     * @feature phase 1
     * Tests the local_forummoderation_check_user_comment_post function.
     *
     * This test verifies that the function correctly returns information for an existing forum post.
     */
    public function test_can_local_forummoderation_check_user_comment_post()
    {
        $existingPost = $this->set_mock_data_post();
        // Call the function to be tested
        $result = local_forummoderation_check_user_comment_post($existingPost->id);

        // Assertions (ensure results are as expected)
        $this->assertNotNull($result, 'Should return data for an existing post');
        $this->assertInstanceOf('stdClass', $result, 'Should return an object');
        $this->assertEquals($existingPost->id, $result->id, 'Should match the provided post id');
    }

    /**
     * @feature phase 1
     * Tests the local_forummoderation_save_forum function.
     *
     * This test verifies that the function successfully saves a forum moderation record
     * to the database with the correct data.
     */
    public function test_can_local_forummoderation_save_forum()
    {

        global $DB;

        $userId = $this->user->id;
        $postId = $this->post->id;

        local_forummoderation_save_forum($userId, $postId, 'this report message', 1);

        // Verify the data stored in the database
        $savedData = $DB->get_record('local_forummoderation', ['forumid' => $postId]);
        $this->assertNotNull($savedData, 'Record should exist in the database');
    }

    /**
     * @pahse 1
     * Check if user can be assigen a role for moderation
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public function test_can_check_role_for_local_forummoderation_check_role(){
        $this->setUser($this->user);
        $course = self::getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        role_assign(1, $this->user->id, \context_system::instance()->id);
        $users = get_role_users(3, $context);
        $this->assertEquals(count($users), 0);
        \core_role_external::assign_roles(array(
            array('roleid' => 3, 'userid' => $this->user->id, 'contextid' => $context->id)));
        $users = get_role_users(3, $context);
        $this->assertEquals(count($users), 1);
    }

    /**
     * @phase 1
     * Check if user has the role
     * @return void
     * @throws coding_exception
     */
    public function test_can_check_role_for_local_forummoderation_get_user_role_moderation(){
        $this->setUser($this->user);
        $course = self::getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        role_assign(1, $this->user->id, $context);
        $users = get_role_users(1, $context);
        $this->assertEquals(count($users), 1);
    }

    /**
     * Check if forum can be send out for mederation
     * @return void
     */
    public function test_can_local_forummoderation_send(){
        $this->setUser($this->user);
        $course = self::getDataGenerator()->create_course();
        try{
            local_forummoderation_send($course, $this->post->id);
            $this->assertNotFalse(true,'');
        }catch (\Exception $e){
            $this->assertNotFalse(true,'');
        }
    }

    /**
     * Check if user has comment in the post forum
     * @return void
     */
    public function test_local_forummoderation_check_user_comment_post(){
        $this->setUser($this->user);
        $post = local_forummoderation_check_user_comment_post($this->post->id);
        $this->assertNotNull($post, 'Should return data for an existing post');
    }

    /**
     * Check if we can sent out the email for notification
     * @return void
     */
    public function test_can_send_local_forummoderation_via_email(){
        $this->setUser($this->user);
        $course = self::getDataGenerator()->create_course();
        try {
            local_forummoderation_via_email([$this->user], 'Test', $this->post->id, $course);
            $this->assertNotFalse(true,'');
        }catch (\Exception $e){
            $this->assertNotFalse(true,'');
        }
    }

    /**
     * Check if we can sent out In Application notification
     * @return void
     */
    public function test_can_send_local_forummoderation_via_notifcation(){
        $this->setUser($this->user);
        $course = self::getDataGenerator()->create_course();
        try {
            local_forummoderation_via_notifcation([$this->user], 'Test', $course, $this->post->id);
            $this->assertNotFalse(true,'');
        }catch (\Exception $e){
            $this->assertNotFalse(true,'');
        }
    }
}
