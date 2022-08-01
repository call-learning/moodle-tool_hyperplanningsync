<?php
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
 * Process form for hyperplanningsync admin tool
 *
 * @package    tool_hyperplanningsync
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_hyperplanningsync;

use advanced_testcase;
use tool_hyperplanningsync\task\hyperplanning_sync_task;

/**
 * Unit tests for the custom file types.
 *
 * @package tool_hyperplanningsync
 * @copyright 2020 CALL Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \tool_hyperplanningsync\hyperplanningsync
 */
class tool_hyperplanningsync_test extends advanced_testcase {
    /**
     * @var array $cohorts
     */
    protected $cohorts = [];
    /**
     * @var object $user
     */
    protected $user = null;
    /**
     * @var object $hyperplanninglog
     */
    protected $hyperplanninglog = null;
    /**
     * Pattern
     */
    const GROUP_PATTERN_SAMPLE = '/(A[0-9]+)\s*gr([0-9]\.[0-9])/i';
    /**
     * Replacement
     */
    const GROUP_REPLACE_SAMPLE = '\1\3Gp\2';

    /**
     * User email
     */
    const USER_EMAIL = 'etudiant1.etudiant1@email.com';

    /**
     * Setup
     *
     */
    public function setUp() {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $this->user = $this->getDataGenerator()->create_user(['email' => self::USER_EMAIL]);
        $this->cohorts[] = $this->getDataGenerator()->create_cohort(['name' => 'A1', 'idnumber' => 'A1']);
        $this->cohorts[] = $this->getDataGenerator()->create_cohort(['name' => 'A2', 'idnumber' => 'A2']);
        $hyperplanninglog = array(
            'importid' => 1591286339,
            'idfield' => 'email',
            'lineid' => 2,
            'status' => hyperplanningsync::STATUS_INITED,
            'createdbyid' => 1,
            'timecreated' => time(),
            'idnumber' => '',
            'username' => $this->user->username,
            'cohort' => 'A1',
            'maingroup' => '< A1 > gr8.1',
            'othergroups' => '[ A1 gr4.1] ,  [ A1 sans gr8.2] ,  [ A1 sans gr8.3] ,  [ A1 sans gr8.4] ,  [ A1 sans gr8.5] ,'
                . '  [ A1 sans gr8.6] ,  [ A1 sans gr8.7] ,  [ A1 sans gr8.8]',
            'email' => self::USER_EMAIL,
            'userid' => $this->user->id,
            'cohortid' => $this->cohorts[0]->id,
            'statustext' => '',
        );
        $id = $DB->insert_record('tool_hyperplanningsync_log', $hyperplanninglog);
        $this->hyperplanninglog = $DB->get_record('tool_hyperplanningsync_log', array('id' => $id));
    }

    /**
     * Tests test_group_transform() function.
     * @covers \tool_hyperplanningsync\hyperplanningsync::clean_groups
     */
    public function test_group_transform() {
        $this->resetAfterTest();

        $simpletransform = hyperplanningsync::clean_groups((array) $this->hyperplanninglog, '', '');
        $this->assertEquals(array(
            0 => 'A1 gr8.1',
            1 => 'A1 gr4.1',
            2 => 'A1 sans gr8.2',
            3 => 'A1 sans gr8.3',
            4 => 'A1 sans gr8.4',
            5 => 'A1 sans gr8.5',
            6 => 'A1 sans gr8.6',
            7 => 'A1 sans gr8.7',
            8 => 'A1 sans gr8.8',
        ), $simpletransform);
        $patterntransforms =
            hyperplanningsync::clean_groups((array) $this->hyperplanninglog, self::GROUP_PATTERN_SAMPLE,
                self::GROUP_REPLACE_SAMPLE);
        $this->assertEquals(array(
            0 => 'A1Gp8.1',
            1 => 'A1Gp4.1',
            2 => 'A1 sans gr8.2',
            3 => 'A1 sans gr8.3',
            4 => 'A1 sans gr8.4',
            5 => 'A1 sans gr8.5',
            6 => 'A1 sans gr8.6',
            7 => 'A1 sans gr8.7',
            8 => 'A1 sans gr8.8',
        ), $patterntransforms);
    }

    /**
     * Tests test_assign_cohort_simple() function.
     * @covers \tool_hyperplanningsync\hyperplanningsync::assign_cohort
     */
    public function test_assign_cohort_simple() {
        $this->resetAfterTest();
        hyperplanningsync::assign_cohort($this->hyperplanninglog, false);
        $this->runAdhocTasks(hyperplanning_sync_task::class);
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
    }

    /**
     * Tests test_assign_cohort_already_assigned() function.
     * @covers \tool_hyperplanningsync\hyperplanningsync::assign_cohort
     */
    public function test_assign_cohort_already_assigned() {
        $this->resetAfterTest();
        cohort_add_member($this->cohorts[1]->id, $this->user->id); // User is in A2.

        hyperplanningsync::assign_cohort($this->hyperplanninglog, true);
        $this->runAdhocTasks(hyperplanning_sync_task::class);
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
    }

    /**
     * Tests test_assign_cohort_with_enrolment() function.
     * @covers \tool_hyperplanningsync\hyperplanningsync::assign_cohort
     */
    public function test_assign_cohort_with_enrolment() {
        $this->resetAfterTest();
        cohort_add_member($this->cohorts[1]->id, $this->user->id); // User is in A2 and enrolled in another course.
        $oldcourse = $this->getDataGenerator()->create_course();
        $this->create_cohort_enrolment_and_enrol($oldcourse, $this->cohorts[1]->id, $this->user->id);

        $course = $this->getDataGenerator()->create_course();
        $this->create_cohort_enrolment_for_course($course, $this->cohorts[0]->id);

        $this->assertFalse(is_enrolled(\context_course::instance($course->id), $this->user->id));
        $this->assertTrue(is_enrolled(\context_course::instance($oldcourse->id), $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertTrue(cohort_is_member($this->cohorts[1]->id, $this->user->id));

        hyperplanningsync::assign_cohort($this->hyperplanninglog, true);
        $this->runAdhocTasks(hyperplanning_sync_task::class);
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course->id), $this->user->id));
        $this->assertFalse(is_enrolled(\context_course::instance($oldcourse->id), $this->user->id));
    }

    /**
     * Tests test_assign_group_simple() function.
     * @covers \tool_hyperplanningsync\hyperplanningsync::assign_group
     */
    public function test_assign_group_simple() {
        global $CFG;
        $this->resetAfterTest();

        // User is enrolled in the course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group1 = $generator->create_group(['courseid' => $course->id, 'idnumber' => 'A1Gp8.1']);
        $group2 = $generator->create_group(['courseid' => $course->id, 'idnumber' => 'A1Gp4.1']);

        // User is enrolled via cohort already in A1.
        $this->create_cohort_enrolment_and_enrol($course, $this->cohorts[0]->id, $this->user->id);

        $this->hyperplanninglog->groupscsv = implode(',', ["A1Gp8.1", "A1Gp4.1"]);

        $this->assertFalse(groups_is_member($group1->id, $this->user->id));
        $this->assertFalse(groups_is_member($group2->id, $this->user->id));

        // The user is registered in the course.
        hyperplanningsync::assign_group($this->hyperplanninglog, false);
        $this->runAdhocTasks(hyperplanning_sync_task::class);
        $this->assertTrue(groups_is_member($group1->id, $this->user->id));
        $this->assertTrue(groups_is_member($group2->id, $this->user->id));
    }

    /**
     * Tests test_assign_group_simple() function.
     * @covers \tool_hyperplanningsync\hyperplanningsync::assign_group
     *
     */
    public function test_assign_group_simple_no_instance() {
        global $DB;
        $this->resetAfterTest();

        // User is enrolled in the course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group1 = $generator->create_group(['courseid' => $course->id, 'idnumber' => 'A1Gp8.1', 'name' => 'A1Gp8.1']);
        $group2 = $generator->create_group(['courseid' => $course->id, 'idnumber' => 'A1Gp4.1', 'name' => 'A1Gp4.1']);
        $this->create_cohort_enrolment_for_course($course, $this->cohorts[0]->id);

        // Instance exists but user not enrolled.
        $this->hyperplanninglog->groupscsv = implode(',', ["A1Gp8.1", "A1Gp4.1"]);
        // The user is not yet enrolled in the course so nothing should have happened.
        hyperplanningsync::assign_group($this->hyperplanninglog, false);
        $this->runAdhocTasks(hyperplanning_sync_task::class);
        $this->assertFalse(groups_is_member($group1->id, $this->user->id));
        $this->assertFalse(groups_is_member($group2->id, $this->user->id));
        $hyperplanninglog = $DB->get_record('tool_hyperplanningsync_log', array('id' => $this->hyperplanninglog->id));
        // We should have added the user enrolment into the log.
        $this->assertStringContainsString('unable to add user to group "A1Gp8.1"', $hyperplanninglog->statustext);
        $this->assertStringContainsString('unable to add user to group "A1Gp4.1"', $hyperplanninglog->statustext);
        $this->assertStringNotContainsString('Added user to group "A1Gp8.1"', $hyperplanninglog->statustext);
        $this->assertStringNotContainsString('Added user to group "A1Gp4.1"', $hyperplanninglog->statustext);
        // Prevent the user_enrolment_created from being fired.

        // Now register user in A1 and enroll it in the course and run adhoc
        // tasks to catch up with group enrolment.
        $this->getDataGenerator()->enrol_user($this->user->id, $course->id, 'student', 'cohort');
        $this->runAdhocTasks();
        $this->assertTrue(groups_is_member($group1->id, $this->user->id));
        $this->assertTrue(groups_is_member($group2->id, $this->user->id));
        $hyperplanninglog = $DB->get_record('tool_hyperplanningsync_log', array('id' => $this->hyperplanninglog->id));
        // We should have added the user enrolment into the log.
        $this->assertStringContainsString('Added user to group "A1Gp8.1"', $hyperplanninglog->statustext);
        $this->assertStringContainsString('Added user to group "A1Gp4.1"', $hyperplanninglog->statustext);
    }

    /**
     * Tests add user after cohort creation.
     * @covers \tool_hyperplanningsync\hyperplanningsync::process
     */
    public function test_insert_cohort_after_user_creation() {
        global $DB;
        $this->resetAfterTest();
        set_config('sync_new_users_enabled', true, 'tool_hyperplanningsync');
        $newusername = 'testuser2';
        $newuseremail = 'testemail@email.com';
        $hyperplanninglog = array(
            'importid' => 1591286339,
            'idfield' => 'email',
            'lineid' => 2,
            'status' => hyperplanningsync::STATUS_PENDING,
            'createdbyid' => 1,
            'timecreated' => time(),
            'idnumber' => '',
            'username' => $newusername,
            'cohort' => 'A1',
            'maingroup' => '< A1 > gr8.1',
            'othergroups' => '[ A1 gr4.1] ,  [ A1 sans gr8.2] ,  [ A1 sans gr8.3] ,  [ A1 sans gr8.4] ,  [ A1 sans gr8.5] ,'
                . '  [ A1 sans gr8.6] ,  [ A1 sans gr8.7] ,  [ A1 sans gr8.8]',
            'email' => $newuseremail,
            'userid' => '',
            'cohortid' => $this->cohorts[0]->id,
            'statustext' => '',
        );
        $hyperplanninglogid = $DB->insert_record('tool_hyperplanningsync_log', $hyperplanninglog);
        hyperplanningsync::process(1591286339);
        $this->runAdhocTasks(hyperplanning_sync_task::class);
        // The usual user is member of the cohort.
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
        // Now create a new user.
        $newuser = $this->getDataGenerator()->create_user(['username' => $newusername, 'email' => $newuseremail]);
        $this->assertFalse(cohort_is_member($this->cohorts[0]->id, $newuser->id));
        // Nothing yet in the log.
        $hyperplanninglog = $DB->get_record('tool_hyperplanningsync_log', array('id' => $hyperplanninglogid));
        $this->assertStringNotContainsString(get_string('process:usercreated', 'tool_hyperplanningsync'),
            $hyperplanninglog->statustext);
        $this->runAdhocTasks();
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $newuser->id));
        $hyperplanninglog = $DB->get_record('tool_hyperplanningsync_log', array('id' => $hyperplanninglogid));
        // We should have added the user enrolment into the log.
        $this->assertStringContainsString(get_string('process:usercreated', 'tool_hyperplanningsync'),
            $hyperplanninglog->statustext);
    }

    /**
     * Create cohort enrolment for given course
     *
     * @param object $course
     * @param int $cohortid
     * @param int $userid
     */
    protected function create_cohort_enrolment_and_enrol($course, $cohortid, $userid) {
        $pluginname = 'cohort';
        $this->create_cohort_enrolment_for_course($course, $cohortid);
        $this->getDataGenerator()->enrol_user($userid, $course->id, 'student', $pluginname);
    }

    /**
     * Create cohort enrolment for given course
     *
     * @param object $course
     * @param int $cohortid
     */
    protected function create_cohort_enrolment_for_course($course, $cohortid) {
        global $CFG;
        $pluginname = 'cohort';
        $CFG->enrol_plugins_enabled = $pluginname;
        // Get the enrol plugin.
        $plugin = enrol_get_plugin($pluginname);
        // Enable this enrol plugin for the course.
        $plugin->add_instance($course, ['customint1' => $cohortid]);
    }
}
