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

namespace assignfeedback_aif;

/**
 * Tests for AI Assisted Feedback
 *
 * @package    assignfeedback_aif
 * @category   test
 * @copyright  2025 2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
 use mod_assign_test_generator;
 require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

final class submission_test extends \advanced_testcase {

    public function test_submission() :void {
        xdebug_break();


        $this->resetAfterTest();
        xdebug_break();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params = [];
        $options = [
            'assignsubmission_onlinetext_enabled' => 1,
            'assignfeedback_aif_enabled' => 1
        ];

        $instance = $generator->create_instance((object)['course' => $course], $options);



        // $assign = $this->create_instance($course);
        // $context = $assign->get_context();
        // $this->setUser($student->id);
        // $submission = $assign->get_user_submission($student->id, true);

    }
    protected function create_instance($course, $params = [], $options = []) {
        $params['course'] = $course->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $instance = $generator->create_instance($params, $options);
        $cm = get_coursemodule_from_instance('assign', $instance->id);
        $context = context_module::instance($cm->id);

        return new mod_assign_testable_assign($context, $cm, $course);
    }

}
