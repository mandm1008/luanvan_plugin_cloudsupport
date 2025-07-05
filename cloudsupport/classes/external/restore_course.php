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
            'courseid' => new external_value(PARAM_INT, 'ID của khóa học sẽ restore vào'),
        ]);
    }

    public static function execute($filename, $courseid) {
        global $CFG, $DB, $USER;

        // Kiểm tra khóa học
        if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new moodle_exception('Invalid course ID.');
        }

        $context = context_course::instance($courseid);
        require_capability('moodle/restore:restorecourse', $context);

        // Tìm file từ File API (GCS / filedir)
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

        // Lưu file tạm để restore_controller có thể xử lý
        $tempdir = make_request_directory(); // tạo thư mục tạm trong dataroot/temp
        $temppath = $tempdir . '/' . $filename;
        $storedfile->copy_content_to($temppath);

        if (!file_exists($temppath)) {
            throw new moodle_exception('Failed to copy file to temp directory.');
        }

        // Tạo restore controller
        $controller = new \restore_controller(
            $temppath,
            $courseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_EXISTING_DELETING
        );

        if (!$controller) {
            throw new moodle_exception('Failed to create restore controller.');
        }

        // Convert nếu cần
        if ($controller->get_status() === \backup::STATUS_REQUIRE_CONV) {
            $controller->convert();
        }

        if ($controller->get_status() === \backup::STATUS_SETTING_UI) {
            $controller->finish_ui();
        }

        // Lấy và build plan
        $plan = $controller->get_plan();
        if (!$plan) {
            throw new moodle_exception('Restore plan is not available.');
        }
        $plan->build();

        // Precheck
        $precheck = $controller->execute_precheck();
        if ($precheck !== true) {
            $errors = '';
            foreach ($precheck as $error) {
                $errors .= $error . "\n";
            }
            throw new moodle_exception('Precheck failed: ' . $errors);
        }

        // Thực hiện restore
        $controller->execute_plan();

        if ($controller->get_status() !== \backup::STATUS_COMPLETED) {
            throw new moodle_exception('Restore failed.');
        }

        $restoredcourseid = $controller->get_courseid();
        $controller->destroy();

        return [
            'status' => 'success',
            'message' => 'Course restored successfully.',
            'courseid' => $restoredcourseid,
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
