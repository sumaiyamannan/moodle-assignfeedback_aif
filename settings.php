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
 * Settings for aif assign feedback plugin
 *
 * @package    assignfeedback_aif
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var admin_settingpage $settings */
$settings->add(new admin_setting_configcheckbox('assignfeedback_aif/default',
                   new lang_string('enabledbydefault', 'assignfeedback_aif'),
                   new lang_string('default_help', 'assignfeedback_aif'), 0));

$settings->add(new admin_setting_configtextarea('assignfeedback_aif/prompt',
                    get_string('prompt', 'assignfeedback_aif'),
                    get_string('prompt_text', 'assignfeedback_aif'),
                    get_string('prompt_setting', 'assignfeedback_aif'),

                    PARAM_RAW, 20, 3));

