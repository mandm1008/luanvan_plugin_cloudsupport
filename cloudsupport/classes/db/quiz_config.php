<?php
// File: local/cloudsupport/classes/db/quiz_config.php

namespace local_cloudsupport\db;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles all database operations related to the local_cloudsupport_quizcfg table.
 */
class quiz_config {

    /**
     * Inserts a new cloud configuration record for a quiz.
     */
    public static function insert_config(int $quizid, int $usecloud, string $cloudregion = ''): bool {
        global $DB;

        $record = (object)[
            'quizid' => $quizid,
            'usecloud' => $usecloud,
            'cloudregion' => $cloudregion,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        return $DB->insert_record('local_cloudsupport_quizcfg', $record, false);
    }

    /**
     * Updates an existing cloud configuration record for a quiz.
     */
    public static function update_config(int $quizid, int $usecloud, string $cloudregion = ''): bool {
        global $DB;

        $record = $DB->get_record('local_cloudsupport_quizcfg', ['quizid' => $quizid], '*', IGNORE_MISSING);
        if (!$record) {
            return false;
        }

        $record->usecloud = $usecloud;
        $record->cloudregion = $cloudregion;
        $record->timemodified = time();

        return $DB->update_record('local_cloudsupport_quizcfg', $record);
    }

    /**
     * Returns the cloud configuration for a given quiz.
     */
    public static function get_by_quizid(int $quizid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_cloudsupport_quizcfg', ['quizid' => $quizid], '*', IGNORE_MISSING) ?: null;
    }

    /**
     * Deletes the cloud configuration for a specific quiz.
     */
    public static function delete_by_quizid(int $quizid): bool {
        global $DB;

        return $DB->delete_records('local_cloudsupport_quizcfg', ['quizid' => $quizid]);
    }

    /**
     * Checks if a cloud configuration exists for a quiz.
     */
    public static function exists(int $quizid): bool {
        global $DB;

        return $DB->record_exists('local_cloudsupport_quizcfg', ['quizid' => $quizid]);
    }

    /**
     * Creates or updates the cloud configuration for a quiz.
     * If a record exists, it is updated; otherwise, a new one is inserted.
     *
     * @param int $quizid The quiz ID.
     * @param int $usecloud Whether cloud mode is enabled.
     * @param string $cloudregion The cloud region.
     * @return bool True if operation succeeded.
     */
    public static function upsert_config(int $quizid, int $usecloud, string $cloudregion = ''): bool {
        if (self::exists($quizid)) {
            return self::update_config($quizid, $usecloud, $cloudregion);
        } else {
            return self::insert_config($quizid, $usecloud, $cloudregion);
        }
    }
}
