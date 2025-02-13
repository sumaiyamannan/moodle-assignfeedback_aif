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
 * Class aif
 *
 * @package    assignfeedback_aif
 * @copyright  2025 2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \stdClass;
class aif {
    public function perform_request(string $prompt, string $purpose = 'feedback'): string {
            global $USER;
            $manager = new \core_ai\manager();
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->contextid,
                userid: $USER->id,
                prompttext: $prompt
            );
            $llmresponse = $manager->process_action($action);
            $responsedata = $llmresponse->get_response_data();
            return $responsedata['generatedcontent'];
        }
}
