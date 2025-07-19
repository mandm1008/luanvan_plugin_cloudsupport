<?php

require('../../config.php');
require_once(__DIR__.'/classes/output/cloud_form.php');

$cmid = required_param('cmid', PARAM_INT); // course module ID

// Lấy course module
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);

// Lấy course từ cm
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

// Lấy quiz instance
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

// Xác thực và kiểm tra quyền
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Cài đặt trang
$PAGE->set_url('/local/cloudsupport/quizcloud.php', ['cmid' => $cmid]);
$PAGE->set_cm($cm, $course);
$PAGE->set_context($context);
$PAGE->set_title(get_string('cloudoptions', 'local_cloudsupport'));
$PAGE->set_heading(get_string('cloudoptions', 'local_cloudsupport'));

$mform = new \local_cloudsupport\output\cloud_form(null, ['cmid' => $cmid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/quiz/edit.php', ['cmid' => $cmid]));
} else if ($data = $mform->get_data()) {
    // Save to your table
    $record = (object)[
        'quizid' => $quiz->id,
        'usecloud' => $data->usecloud,
        'cloudregion' => $data->cloudregion
    ];
    $exists = $DB->get_record('local_cloudsupport_quizcfg', ['quizid' => $quiz->id]);
    if ($exists) {
        $record->id = $exists->id;
        $DB->update_record('local_cloudsupport_quizcfg', $record);
    } else {
        $DB->insert_record('local_cloudsupport_quizcfg', $record);
    }
    $event = \local_cloudsupport\event\quiz_time_updated::create([
        'objectid' => $quiz->id,
        'context' => $context,
        'courseid' => $course->id,
        'other' => [
            'quizid' => $quiz->id,
            'opentime' => $quiz->timeopen,
            'closetime' => $quiz->timeclose,
            'usecloud' => $data->usecloud,
            'cloudregion' => $data->cloudregion,
        ],
    ]);
    $event->trigger();
    redirect($PAGE->url);
}

// Load default values
$default = $DB->get_record('local_cloudsupport_quizcfg', ['quizid' => $quiz->id]);

if ($default) {
    $default->cmid = $cmid;
    $mform->set_data($default);
} else {
    $mform->set_data((object)['cmid' => $cmid]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('cloudoptions', 'local_cloudsupport'));
$mform->display();
echo $OUTPUT->footer();
