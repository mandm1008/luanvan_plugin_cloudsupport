<?php
namespace local_cloudsupport\external;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_course;
use context_system;
use moodle_exception;

class restore_course extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'filename' => new external_value(PARAM_FILE, 'Tên file .mbz đã upload vào File API'),
            'courseid' => new external_value(PARAM_INT, 'ID của khóa học sẽ restore vào (0 nếu tạo mới)'),
        ]);
    }

    public static function execute($filename, $courseid) {
        global $CFG, $DB, $USER;

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $storedfile = $fs->get_file(
            $syscontext->id,
            'local_cloudsupport',
            'backupfiles',
            0,
            '/',
            $filename
        );

        if (!$storedfile) {
            throw new moodle_exception("File not found in File API: $filename");
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'mbz') {
            throw new moodle_exception('Invalid file format. Must be .mbz');
        }

        $restorecontext = context_system::instance();
        require_capability('moodle/restore:restorecourse', $restorecontext);

        $script = $CFG->dirroot . '/local/cloudsupport/cli/restore_runner.php';
        $cmd = "php $script --filename=" . escapeshellarg($filename) . " --courseid=$courseid > /proc/1/fd/1 2>&1 &";
        exec($cmd);

        return [
            'status' => 'success',
            'message' => 'Restore is running in background by ' . $cmd,
            'courseid' => $courseid,
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Trạng thái'),
            'message' => new external_value(PARAM_TEXT, 'Thông báo'),
            'courseid' => new external_value(PARAM_INT, 'ID của khóa học đã được phục hồi')
        ]);
    }
}
