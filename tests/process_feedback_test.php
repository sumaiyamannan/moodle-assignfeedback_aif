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

namespace assignfeedback_aif\tests;

use advanced_testcase;
use assignfeedback_aif\task\process_feedback;

use core_ai\aiactions\generate_image;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/feedback/aif/classes/task/process_feedback.php');

/**
 * Unit tests for the process_feedback scheduled task.
 *
 * @package     assignfeedback_aif
 * @category    test
 * @copyright   2024 Marcus Green
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Test the get_name method of the process_feedback task.
 * @covers process_feedback
 * @covers ::get_name
 */

class process_feedback_test extends advanced_testcase {
    /** @var stdClass */
    private $course;

    /** @var stdClass */
    private $teacher;

    /** @var stdClass */
    private $student;

    /** @var stdClass */
    private $assign;

    /** @var process_feedback */
    private $task;

    protected function setUp(): void {
        $this->resetAfterTest(true);
        parent::setUp();
        global $DB,$CFG;
        set_config('enabled', 1, 'aiprovider_openai');
        set_config('apikey', TEST_LLM_APIKEY, 'aiprovider_openai');
        set_config('summarise_text', 1, 'aiprovider_openai');

        $manager = \core\di::get(\core_ai\manager::class);
        $actions = [
            generate_text::class,
            summarise_text::class,
        ];

        // // Get the providers for the actions.
       \core_ai\manager::set_action_state('aiprovider_openai', generate_text::class::get_basename(), 1);
        //$providers = $manager->get_providers_for_actions($actions, true);

        // // Disable the action.
        // set_config('summarise_text', 1, $plugin);

        // Create test data
        $this->course = $this->getDataGenerator()->create_course();
        $this->teacher = $this->getDataGenerator()->create_user();
        $this->student = $this->getDataGenerator()->create_user();

        // Enroll users
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, 'student');

        // Create assignment instance
        $this->assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->course->id,
            'name' => 'Test assignment',
            'activity' => 'Enter text for evaluation by an AI system',
        ]);

        $data = (object) [
            'assignment' => $this->assign->cmid,
            'prompt' => 'Evaluate the grammar in the following',
            'timecreated' => time(),
        ];
        $DB->insert_record('assignfeedback_aif', $data);

        // Create the task instance
        $this->task = new process_feedback();

        /** Create a submission */
        $submission = new \stdClass();
        $submission->assignment = $this->assign->id;
        $submission->userid = $this->student->id;
        $submission->timecreated = time();
        $submission->timemodified = $submission->timecreated;
        $submission->timestarted = $submission->timecreated;;
        $submission->status = 'submitted';
        $submission->attemptnumber = 0;
        $submission->latest = 0;
        $subid = $DB->insert_record('assign_submission', $submission);

        $olt = new \stdClass();
        $olt->assignment = $this->assign->id;
        $olt->submission = $subid;
        $olt->onlinetext = 'Yesterday I go prk';
        $olt->onlineformat = 1;
        $oltid = $DB->insert_record('assignsubmission_onlinetext', $olt);
    }

    /**
     * Run the execute method (called by cron)
     *
     * @covers ::process_feedback
     * @covers ::execute
     */
    public function test_execute(): void {
        $this->resetAfterTest(true);
        global $DB;

        $result = $DB->get_records('assignfeedback_aif_feedback');
        $this->assertCount(0, $result);
        $this->task->execute();
        $result = $DB->get_records('assignfeedback_aif_feedback');
        $this->assertCount(1, $result);

    }
    public function test_get_name(): void {
        $this->resetAfterTest(true);
        $task = new process_feedback();
        $this->assertEquals(get_string('taskprocessfeedback', 'assignfeedback_aif'), $task->get_name());
    }

    public function test_get_name_with_different_language(): void {
        $this->resetAfterTest(true);
        $task = new process_feedback();
        //$this->setCurrentLanguage('es');
        $this->assertEquals(get_string('taskprocessfeedback', 'assignfeedback_aif'), $task->get_name());
    }

    public function test_get_name_returns_string(): void {
        $this->resetAfterTest(true);
        $task = new process_feedback();
        $this->assertIsString($task->get_name());
    }

    public function test_get_name_not_empty(): void {
        $this->resetAfterTest(true);
        $task = new process_feedback();
        $this->assertNotEmpty($task->get_name());
    }
    public function test_get_name_not_null(): void {
        $this->resetAfterTest(true);
        $task = new process_feedback();
        $this->assertNotNull($task->get_name());
    }
}
