<?php

defined('MOODLE_INTERNAL') || die();

class local_cloudsupport_observer {

    /**
     * Handles the quiz creation event.
     *
     * @param course_module_created $event
     */
    public static function quiz_created(\core\event\course_module_created $event): void {
        if (!isset($event->other['modulename']) || $event->other['modulename'] !== 'quiz') {
            return;
        }

        $quizid = $event->objectid;
        \local_cloudsupport\services\event_dispatcher::trigger_quiz_time_updated($quizid, $event->courseid, $event->contextinstanceid);
    }

    /**
     * Handles the quiz update event.
     *
     * @param course_module_updated $event
     */
    public static function quiz_updated(\core\event\course_module_updated $event): void {
        if (!isset($event->other['modulename']) || $event->other['modulename'] !== 'quiz') {
            return;
        }

        $quizid = $event->other['instanceid'] ?? null;
        if (!$quizid) {
            return;
        }

        \local_cloudsupport\services\event_dispatcher::trigger_quiz_time_updated($quizid, $event->courseid, $event->contextinstanceid);
    }
}
