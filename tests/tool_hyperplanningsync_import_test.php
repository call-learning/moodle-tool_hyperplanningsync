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
use moodle_url;
use stdClass;

/**
 * Unit tests for the custom file types.
 *
 * @package tool_hyperplanningsync
 * @copyright 2020 CALL Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \tool_hyperplanningsync\hyperplanningsync
 */
class tool_hyperplanningsync_import_test extends advanced_testcase {
    /**
     * Maximum users
     */
    const MAX_USERS = 148;
    /**
     * Pattern
     */
    const GROUP_PATTERN_SAMPLE = '/(A[0-9]+)\s*gr([0-9]\.[0-9])/i';
    /**
     * Replacement
     */
    const GROUP_REPLACE_SAMPLE = '\1\3Gp\2';

    /**
     * SQL to get cohorts
     */
    const SQL_GET_COHORT = 'SELECT c.*
              FROM {cohort} c
              JOIN {cohort_members} cm ON c.id = cm.cohortid
             WHERE cm.userid = :userid AND c.visible = 1';

    /**
     * Sampple user list
     *
     * @var array
     */
    protected $users = [];

    /**
     * Setup
     *
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        // Setup custom profile fields.
        $dataset = $this->createCsvDataSet(array(
                'cohort' => __DIR__ . '/fixtures/cohort.csv',
                'course' => __DIR__ . '/fixtures/course.csv',
                'groups' => __DIR__ . '/fixtures/groups.csv',
                'course_categories' => __DIR__ . '/fixtures/course_categories.csv'
            )
        );
        $this->loadDataSet($dataset);

        for ($j = 1; $j <= self::MAX_USERS; $j++) {
            $user = $this->getDataGenerator()->create_user([
                'firstname' => 'etudiant' . $j,
                'surname' => 'etudiant' . $j,
                'username' => 'etudiant' . $j,
                'email' => "etudiant{$j}.etudiant{$j}@email.com"
            ]);
            $this->users[$j] = $user;
        }

    }

    /**
     * Tests importation preprocessing
     * @covers \tool_hyperplanningsync\hyperplanningsync::do_import
     */
    public function test_import_sample_prechecks() {
        global $DB;
        $this->resetAfterTest();

        $this->import_precheck('sample_export_hyperplanning_simple.csv');

        // Check that there was at least an error for the user with no cohort/groups.
        $this->assertContains('Cohort not found', $DB->get_field(
            'tool_hyperplanningsync_log', 'status', array(
                'email' => 'etudiantnonexisting.etudiant@email.com')
        ));
        // Check that there was at least an error for the user that does not exist.
        $this->assertContains('User not found', $DB->get_field(
            'tool_hyperplanningsync_log', 'status', array(
                'email' => 'etudiantnonexisting.etudiant148@email.com')
        ));
        // Check that there is an error for the user who has the wrong group ID.
        $this->assertContains('Group not found for this idnumber : A4 grHp 35', $DB->get_field(
            'tool_hyperplanningsync_log', 'status', array(
                'email' => 'etudiant117.etudiant117@email.com')
        ));
        $this->assertContains('Group not found for this idnumber : A2 sans gr8.1', $DB->get_field(
            'tool_hyperplanningsync_log', 'status', array(
                'email' => 'etudiant26.etudiant26@email.com')
        ));
    }

    /**
     * Import precheck fixture
     *
     * @param string $fixturefile
     * @return int
     */
    protected function import_precheck($fixturefile) {
        // Prepare.
        $filename = dirname(__FILE__) . '/fixtures/' . $fixturefile;
        $content = file_get_contents($filename);

        // Run the import precheck.
        $formdata = $this->get_default_upload_formdata();
        return hyperplanningsync::do_import($content, $formdata, new moodle_url('/'));
    }

    /**
     * Get default for upload form data
     *
     * @return stdClass
     */
    protected function get_default_upload_formdata() {
        $formdata = new stdClass();
        $formdata->upload_delimiter = 'comma';
        $formdata->upload_encoding = 'UTF-8';
        $formdata->moodle_idfield = get_config('tool_hyperplanningsync', 'moodle_idfield');
        $formdata->group_transform_pattern = self::GROUP_PATTERN_SAMPLE;
        $formdata->group_transform_replacement = self::GROUP_REPLACE_SAMPLE;
        $formdata->ignoregroups = true;
        $fields = hyperplanningsync::get_fields();

        foreach ($fields as $fieldname => $value) {
            $formdata->{'field' . $fieldname} = $value;
        }
        $formdata->field_idfield = 'Adresse e-mail';
        return $formdata;
    }

    /**
     * Tests importation preprocessing
     * @covers \tool_hyperplanningsync\hyperplanningsync::process
     */
    public function test_import_sample() {
        global $DB;
        $this->resetAfterTest();
        // Prepare.
        $a1cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'A1'));
        $a2cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'A2'));
        $a4cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'A4'));

        $student1 = $this->users[1];
        $student26 = $this->users[26];

        // Do the precheck import.
        $importid = $this->import_precheck('sample_export_hyperplanning_simple.csv');

        // Now do the processing.
        hyperplanningsync::process($importid, true, true);
        // And a couple of user to check if they belong to the right cohort.

        $student1cohort = $DB->get_record_sql(self::SQL_GET_COHORT, array('userid' => $student1->id));
        $student26cohort = $DB->get_record_sql(self::SQL_GET_COHORT, array('userid' => $student26->id));
        $this->assertEquals($a1cohortid, $student1cohort->id);
        $this->assertEquals($a2cohortid, $student26cohort->id);
        $this->assertCount(1, $DB->get_records_sql(self::SQL_GET_COHORT, array('userid' => $student1->id)));
        $this->assertCount(1, $DB->get_records_sql(self::SQL_GET_COHORT, array('userid' => $student26->id)));
    }

    /**
     * Tests importation preprocessing - user already assigned to a cohort
     * @covers \tool_hyperplanningsync\hyperplanningsync::process
     */
    public function test_import_sample_with_preassigned() {
        global $DB;
        $this->resetAfterTest();
        // Prepare.
        $a1cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'A1'));
        $a2cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'A2'));
        $a4cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'A4'));

        $student1 = $this->users[1];
        $student26 = $this->users[26];
        cohort_add_member($a4cohortid, $student1->id); // Set User 1 to belong to cohort A4.

        // Do the precheck import.
        $importid = $this->import_precheck('sample_export_hyperplanning_simple.csv');

        // Now do the processing but specify we don't remove student from cohort or group.
        hyperplanningsync::process($importid, false, false);
        // And a couple of user to check if they belong to the right cohort.

        $student1cohort = $DB->get_records_sql(self::SQL_GET_COHORT, array('userid' => $student1->id));
        $student26cohort = $DB->get_record_sql(self::SQL_GET_COHORT, array('userid' => $student26->id));
        $this->assertCount(2, $DB->get_records_sql(self::SQL_GET_COHORT, array('userid' => $student1->id)));
        $this->assertCount(1, $DB->get_records_sql(self::SQL_GET_COHORT, array('userid' => $student26->id)));
        $this->assertContains($a1cohortid, array_map(function($cohort) {
            return $cohort->id;
        }, $student1cohort));
        $this->assertEquals($a2cohortid, $student26cohort->id);
    }

}
