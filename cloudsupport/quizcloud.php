<?php

require('../../config.php');
require_once(__DIR__ . '/classes/output/cloud_form.php');

use local_cloudsupport\db\quiz_config;
use local_cloudsupport\services\event_dispatcher;

$cmid = required_param('cmid', PARAM_INT); // Course module ID

// Get course module
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);

// Get course
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

// Get quiz instance
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

// Require login and permission
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Page setup
$PAGE->set_url('/local/cloudsupport/quizcloud.php', ['cmid' => $cmid]);
$PAGE->set_cm($cm, $course);
$PAGE->set_context($context);
$PAGE->set_title(get_string('cloudoptions', 'local_cloudsupport'));
$PAGE->set_heading(get_string('cloudoptions', 'local_cloudsupport'));

// Form setup
$mform = new \local_cloudsupport\output\cloud_form(null, ['cmid' => $cmid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/quiz/edit.php', ['cmid' => $cmid]));
} else if ($data = $mform->get_data()) {
    // Save config
    quiz_config::upsert_config($quiz->id, $data->usecloud, $data->cloudregion);

    // Trigger event using service
    event_dispatcher::trigger_quiz_time_updated($quiz->id, $course->id, $cm->id);

    redirect($PAGE->url);
}

// Load default values
$config = quiz_config::get_by_quizid($quiz->id);
if ($config) {
    $config->cmid = $cmid;
    $mform->set_data($config);
} else {
    $mform->set_data((object)['cmid' => $cmid]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('cloudoptions', 'local_cloudsupport'));
$mform->display();
echo $OUTPUT->footer();
