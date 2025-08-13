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
 * TODO describe file test
 *
 * @package    assignfeedback_aif
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use FastRoute\RouteParser\Std;

require('../../../../config.php');
include "pdfparser/vendor/autoload.php";

require_login();

$url = new moodle_url('/mod/assign/feedback/aif/test.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
    /*    global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $format = 'pdf';

        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'test',
            'filearea' => 'fileconverter_unoconv',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'unoconv_test.docx'
        ];

        // Get the fixture doc file content and generate and stored_file object.
        $fs = get_file_storage();
        $testdocx = $fs->get_file($filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
                $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']);

        if (!$testdocx) {
            $fixturefile = dirname(__DIR__) . '/tests/fixtures/unoconv-source.docx';
            $testdocx = $fs->create_file_from_pathname($filerecord, $fixturefile);
        }

        $conversions = conversion::get_conversions_for_file($testdocx, $format);
        foreach ($conversions as $conversion) {
            if ($conversion->get('id')) {
                $conversion->delete();
            }
        }

        $conversion = new conversion(0, (object) [
                'sourcefileid' => $testdocx->get_id(),
                'targetformat' => $format,
            ]);
        $conversion->create();

        // Convert the doc file to the target format and send it direct to the browser.
        $this->start_document_conversion($conversion);
        do {
            sleep(1);
            $this->poll_conversion_status($conversion);
            $status = $conversion->get('status');
        } while ($status !== conversion::STATUS_COMPLETE && $status !== conversion::STATUS_FAILED);

        readfile_accel($conversion->get_destfile(), 'application/pdf', true);
*/
/*
// Parse PDF file and build necessary objects.
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('teststory.pdf');

$text = $pdf->getText();
echo $text;
*/


        global $DB;
        $aif = new \assignfeedback_aif\aif(\context_system::instance()->id);
        $data = new stdClass();
        $data->assignment = "15";
        $data->users = ["93"];
        $data->action = "generate";
        $assignment = $data->assignment;
        $users = $data->users;
        $action = $data->action;
        foreach ($users as $userid) {
            $sql = "SELECT sub.id AS subid, cx.id AS contextid, aif.id AS aifid, aif.prompt AS prompt, a.id AS aid, olt.onlinetext AS onlinetext
            FROM {assign} a
            JOIN {course_modules} cm
            ON cm.instance = a.id and cm.course = a.course
            JOIN {context} cx
            ON cx.instanceid = cm.id
            JOIN {assignfeedback_aif} aif
            ON aif.assignment = cm.id
            JOIN {assign_submission} sub
            ON sub.assignment = a.id
            JOIN {assignsubmission_onlinetext} olt
            ON olt.assignment = a.id AND olt.submission = sub.id
            WHERE sub.status='submitted' AND contextlevel = 70 AND a.id = :aid AND sub.userid = :userid AND sub.latest = 1";
            $record = $DB->get_record_sql($sql, ['aid' => $assignment, 'userid' => $userid]);
            if ($action == 'generate') {
                if (empty($record)) {
                    continue;
                }
                $prompt =  $aif->get_prompt($record, 'rubric');
                die();
                if (empty($prompt)) {
                    continue;
                }
                $aifeedback =  $aif->perform_request($prompt);
                $data = (object) [
                'aif' => $record->aifid,
                'feedback' => $aifeedback,
                'timecreated' => time(),
                'submission' => $record->subid,
                ];
                $DB->insert_record('assignfeedback_aif_feedback', $data);
                mtrace($prompt);
            }
            if ($action == 'delete') {
                if ($record->subid) {
                    $DB->delete_records('assignfeedback_aif_feedback',
                        ['aif'=>$record->aifid, 'submission'=>$record->subid]);
                    mtrace("AI feedback deleted for assignment {$assignment} submission {$record->subid} ");
                }
            }
        }

echo $OUTPUT->footer();
