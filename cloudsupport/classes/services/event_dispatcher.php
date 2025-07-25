<?php

namespace local_cloudsupport\services;

use local_cloudsupport\event\quiz_time_updated;

defined('MOODLE_INTERNAL') || die();

class event_dispatcher {

    /**
     * Trigger quiz_time_updated event if applicable.
     *
     * @param int $quizid The quiz ID.
     * @param int $courseid The course ID.
     * @param int $cmid The course module ID.
     */
    public static function trigger_quiz_time_updated(int $quizid, int $courseid, int $cmid): void {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', IGNORE_MISSING);
        if (!$quiz) {
            return;
        }

        $other = [
            'quizid' => $quizid,
            'opentime' => $quiz->timeopen,
            'closetime' => $quiz->timeclose,
        ];

        $cfg = $DB->get_record('local_cloudsupport_quizcfg', ['quizid' => $quizid], '*', IGNORE_MISSING);
        if ($cfg) {
            if (!empty($cfg->usecloud)) {
                $other['usecloud'] = $cfg->usecloud;
            }
            if (!empty($cfg->cloudregion)) {
                $other['cloudregion'] = $cfg->cloudregion;
            }
        }

        quiz_time_updated::create([
            'objectid' => $quizid,
            'context' => \context_module::instance($cmid),
            'courseid' => $courseid,
            'other' => $other,
        ])->trigger();
    }
}
