<?php

class local_cloudsupport_observer {

    public static function quiz_created(\core\event\course_module_created $event): void {
        if (!isset($event->other['modulename']) || $event->other['modulename'] !== 'quiz') {
            return;
        }

        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $event->objectid], '*', IGNORE_MISSING);
        if (!$quiz) {
            return;
        }

        if ($quiz->timeopen || $quiz->timeclose) {
            \local_cloudsupport\event\quiz_time_updated::create([
                'objectid' => $quiz->id,
                'context' => \context_module::instance($event->contextinstanceid),
                'other' => [
                    'quizid' => $quiz->id,
                    'opentime' => $quiz->timeopen,
                    'closetime' => $quiz->timeclose
                ],
            ])->trigger();
        }
    }

    public static function quiz_updated(\core\event\course_module_updated $event): void {
        if (!isset($event->other['modulename']) || $event->other['modulename'] !== 'quiz') {
            return;
        }

        global $DB;
        $quizid = $event->other['instanceid'] ?? null;
        if (!$quizid) {
            return;
        }

        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', IGNORE_MISSING);
        if (!$quiz) {
            return;
        }

        // Nếu có thời gian mở trong tương lai → trigger
        // if ($quiz->timeopen > time()) {
            \local_cloudsupport\event\quiz_time_updated::create([
                'objectid' => $quiz->id,
                'context' => \context_module::instance($event->contextinstanceid),
                'courseid' => $event->courseid,
                'other' => [
                    'quizid' => $quiz->id,
                    'opentime' => $quiz->timeopen,
                    'closetime' => $quiz->timeclose,
                ],
            ])->trigger();
        // }
    }
}
