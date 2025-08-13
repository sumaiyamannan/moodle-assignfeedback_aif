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
include "../pdfparser/vendor/autoload.php";
require_once($CFG->libdir . '/filelib.php');
/**
 * Class aif
 *
 * @package    assignfeedback_aif
 * @copyright  2025 2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \stdClass;

class aif {
    public  int $contextid;

    public function __construct($contextid) {
        $this->contextid = $contextid;
    }

    public function perform_request(string $prompt, string $purpose = 'feedback'): string {
        global $USER;
        $manager = \core\di::get(\core_ai\manager::class);
        $action = new \core_ai\aiactions\generate_text(
            contextid: $this->contextid,
            userid: $USER->id,
            prompttext: $prompt
        );
        $llmresponse = $manager->process_action($action);
        $responsedata = $llmresponse->get_response_data();
        return $responsedata['generatedcontent'];
    }

    public function get_prompt(stdClass $assignment, string $gradingmethod): string {
        global $DB, $CFG;
        // If feedback exists then skip.
        $count = $DB->count_records('assignfeedback_aif_feedback',
        ['aif'=>$assignment->aifid, 'submission' => $assignment->subid]);
        if ($count > 0) {
            mtrace("Skipping as feedback exists");
            return '';
        }
        if ($gradingmethod == 'rubric') {
            $rsql = "SELECT * FROM {grading_areas} ga
                JOIN {grading_definitions} gd ON gd.areaid = ga.id
                JOIN {gradingform_rubric_criteria} rc ON rc.definitionid = gd.id
                WHERE ga.contextid = :contextid AND ga.activemethod LIKE :gradingmethod AND ga.areaname = :areaname";
            $params = ['contextid' => $assignment->contextid,
            'gradingmethod' => $gradingmethod,
            'areaname' => 'submissions'];
            $records = $DB->get_records_sql($rsql, $params);
            if (empty($records)) {
                return '';
            }
            mtrace("Assignment {$assignment->aid} submission {$assignment->subid}");
            $prompt = $assignment->prompt . ': ';
            foreach ($records as $record) {
                $definition = $DB->get_field_sql("SELECT '- ' || string_agg(definition, ' - ')
                FROM {gradingform_rubric_levels} WHERE criterionid = :rcid",
                ['rcid' => $record->id]);
                $prompt .= " ". $record->description. " " . $definition;
            }
            // Get prompt from text submissions.
            if ($onlinetext = $DB->get_field('assignsubmission_onlinetext',
                'onlinetext', ['submission' => $assignment->subid] )) {
                mtrace("Content from text submission added to the prompt.");
                $prompt .= " ".strip_tags($onlinetext);
            }
            // Get prompt from files submissions.
            if ($filetext = self::extract_text_files($assignment)) {
                $prompt .= " ".strip_tags($filetext);
            }
            mtrace("Final prompt: ".$prompt);
            return $prompt;
        } else {
            $id = optional_param('id', 0, PARAM_INT);
            $prompt = $DB->get_record('assignfeedback_aif', ['assignment' => $id]);
            return $prompt;
        }
    }


    /**
     * This function will extract text from PDF files.
     *
     * @param object $assignment
     * @return string
     */
    protected static function extract_text_files($assignment) {
        global $CFG;
        $filetext = '';
        if (isset($CFG->filedir)) {
            $filedir = $CFG->filedir;
        } else {
            $filedir = $CFG->dataroot.'/filedir';
        }
        $fs = get_file_storage();
        $converter = new \core_files\converter();
        $contextid = $assignment->contextid;
        $component = 'assignsubmission_file';
        $filearea = 'submission_files';
        $itemid = $assignment->subid;
        $filepath = '/';
        if ($files = $fs->get_area_files($contextid, $component,
            $filearea, $itemid, 'itemid, filepath, filename', false)) {
            foreach($files as $file) {
                if ($file instanceof \stored_file) {
                    $loadfile = $file;
                    print "<pre>file";print_r($file);print"</pre>";
                    $mimetype = $file->get_mimetype();
                    mtrace("mimetype: ".$mimetype);
                    if ($mimetype === "image/jpeg" || $mimetype === '') {
                        break;
                    }
                    if ($mimetype !== 'application/pdf') {
                        $conversion = $converter->start_conversion($file, 'pdf');
                        mtrace("Start process to convert files to PDF");
                        if ($conversion->get('status') === \core_files\conversion::STATUS_COMPLETE) {
                            if (!$convertedfile = $conversion->get_destfile()) {
                                break;
                            }
                            print "<pre>convertedfile";print_r($convertedfile);print"</pre>";
                            $loadfile = $convertedfile;
                        } else {
                            mtrace("File could not be converted to PDF");
                        }
                    }
                }
                $filedirsub = substr($loadfile->get_contenthash(), 0, 2);
                $filedirsubsub = substr($loadfile->get_contenthash(), 2, 2);
                $filepath = $filedir . '/' . $filedirsub .'/'. $filedirsubsub  . '/' .$loadfile->get_contenthash();
                if (!file_exists($filepath)) {
                    mtrace("File does not exists on server.");
                    break;
                }
                mtrace("filepath: ".$filepath);
                // Parse PDF file and build necessary objects.
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filepath);
                $filetext .= " ". $pdf->getText();
                mtrace("Content from file submissions added to the prompt.");
                mtrace("filetext: ".$filetext);
                return $filetext;
            }
        }
    }


}