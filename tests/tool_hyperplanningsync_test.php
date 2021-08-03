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

use core\event\user_enrolment_created;
use tool_hyperplanningsync\hyperplanningsync;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the custom file types.
 *
 * @package tool_hyperplanningsync
 * @copyright 2020 CALL Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
     * @throws dml_exception
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
            'processed' => false,
            'skipped' => false,
            'pending' => false,
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
            'status' => '',
        );
        $id = $DB->insert_record('tool_hyperplanningsync_log', $hyperplanninglog);
        $this->hyperplanninglog = $DB->get_record('tool_hyperplanningsync_log', array('id' => $id));
    }

    /**
     * Tests test_group_transform() function.
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
     */
    public function test_assign_cohort_simple() {
        $this->resetAfterTest();
        hyperplanningsync::assign_cohort($this->hyperplanninglog, false);
        hyperplanningsync::force_run_cohort_sync();
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
    }

    /**
     * Tests test_assign_cohort_already_assigned() function.
     */
    public function test_assign_cohort_already_assigned() {
        $this->resetAfterTest();
        cohort_add_member($this->cohorts[1]->id, $this->user->id); // User is in A2.

        hyperplanningsync::assign_cohort($this->hyperplanninglog, true);
        hyperplanningsync::force_run_cohort_sync();
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
    }

    /**
     * Tests test_assign_cohort_with_enrolment() function.
     */
    public function test_assign_cohort_with_enrolment() {
        $this->resetAfterTest();
        cohort_add_member($this->cohorts[1]->id, $this->user->id); // User is in A2 and enrolled in another course.
        $oldcourse = $this->getDataGenerator()->create_course();
        $this->create_cohort_enrolment_and_enrol($oldcourse, $this->cohorts[1]->id, $this->user->id);

        $course = $this->getDataGenerator()->create_course();
        $this->create_cohort_enrolment_for_course($course, $this->cohorts[0]->id);

        $eventsink = $this->redirectEvents();
        hyperplanningsync::assign_cohort($this->hyperplanninglog, true);
        hyperplanningsync::force_run_cohort_sync();
        $this->assertTrue(cohort_is_member($this->cohorts[0]->id, $this->user->id));
        $this->assertFalse(cohort_is_member($this->cohorts[1]->id, $this->user->id));
        $this->assertCount(3, $eventsink->get_events());

        // The user has been enrolled.
        $events = $eventsink->get_events();
        $this->assertTrue(is_a($events[0], '\core\event\user_enrolment_created'));
        $this->assertEquals($course->id, $events[0]->courseid);
        $this->assertEquals($this->user->id, $events[0]->relateduserid);

        // We now check that the user has been unenrolled from oldcourse.
        $this->assertTrue(is_a($events[1], '\core\event\role_unassigned'));
        $this->assertEquals($oldcourse->id, $events[1]->courseid);
        $this->assertEquals($this->user->id, $events[1]->relateduserid);
        $this->assertEquals(5, $events[1]->objectid); // Student.

        $this->assertTrue(is_a($events[2], '\core\event\user_enrolment_deleted'));
        $this->assertEquals($oldcourse->id, $events[2]->courseid);
        $this->assertEquals($this->user->id, $events[2]->relateduserid);
    }

    /**
     * Tests test_assign_group_simple() function.
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
        // The user is registered in the course.
        $eventsink = $this->redirectEvents();
        hyperplanningsync::assign_group($this->hyperplanninglog, false);
        $this->assertTrue(groups_is_member($group1->id, $this->user->id));
        $this->assertTrue(groups_is_member($group2->id, $this->user->id));
        $this->assertCount(2, $eventsink->get_events());
        $events = $eventsink->get_events();
        $this->assertTrue(is_a($events[0], '\core\event\group_member_added'));
        $this->assertEquals($group1->id, $events[0]->objectid);
        $this->assertTrue(is_a($events[1], '\core\event\group_member_added'));
        $this->assertEquals($group2->id, $events[1]->objectid);
    }

    /**
     * Tests test_assign_group_simple() function.
     */
    public function test_assign_group_simple_no_instance() {
        global $CFG;
        $this->resetAfterTest();

        // User is enrolled in the course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $group1 = $generator->create_group(['courseid' => $course->id, 'idnumber' => 'A1Gp8.1']);
        $group2 = $generator->create_group(['courseid' => $course->id, 'idnumber' => 'A1Gp4.1']);
        $this->create_cohort_enrolment_for_course($course, $this->cohorts[0]->id);

        // Instance exists but user not enrolled.
        $this->hyperplanninglog->groupscsv = implode(',', ["A1Gp8.1", "A1Gp4.1"]);
        // The user is not yet enrolled in the course so nothing should have happened.
        $eventsink = $this->redirectEvents();
        hyperplanningsync::assign_group($this->hyperplanninglog, false);
        $this->assertCount(0, $eventsink->get_events());
        $this->assertFalse(groups_is_member($group1->id, $this->user->id));
        $this->assertFalse(groups_is_member($group2->id, $this->user->id));
        $eventsink->close(); // We don't cactch events at this point because it will
        // prevent the user_enrolment_created from being fired.

        // Now register user in A1 and enroll it in the course and run adhoc
        // tasks to catch up with group enrolment.
        $this->getDataGenerator()->enrol_user($this->user->id, $course->id, 'student', 'cohort');
        $this->runAdhocTasks();
        $this->assertTrue(groups_is_member($group1->id, $this->user->id));
        $this->assertTrue(groups_is_member($group2->id, $this->user->id));
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
