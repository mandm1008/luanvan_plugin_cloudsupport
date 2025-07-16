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
use context_system;
use moodle_exception;

class restore_course extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'filename'    => new external_value(PARAM_FILE, 'Tên file .mbz đã upload vào File API'),
            'courseid'    => new external_value(PARAM_INT, 'ID của khóa học sẽ restore vào (0 nếu tạo mới)'),
            'webhookapi'  => new external_value(PARAM_RAW, 'Địa chỉ webhook nhận thông báo khi hoàn tất restore', VALUE_DEFAULT, ''),
            'webhooktoken'  => new external_value(PARAM_RAW, 'Địa chỉ webhook nhận thông báo khi hoàn tất restore', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute($filename, $courseid, $webhookapi, $webhooktoken) {
        global $CFG, $USER;

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        // Kiểm tra file đã upload vào File API chưa
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

        // Kiểm tra quyền
        require_capability('moodle/restore:restorecourse', $syscontext);

        // ✅ Lấy chính xác wstoken đang được dùng trong request
        $token = $webhooktoken;

        // ✅ Tạo lệnh CLI
        $script = $CFG->dirroot . '/local/cloudsupport/cli/restore_runner.php';
        $cmd = "php $script --filename=" . escapeshellarg($filename) .
               " --courseid=" . escapeshellarg($courseid);

        if (!empty($webhookapi)) {
            $cmd .= " --webhook-api=" . escapeshellarg($webhookapi);
        }

        if (!empty($token)) {
            $cmd .= " --token=" . escapeshellarg($token);
        }

        // ✅ Chạy nền, log output vào stdout container
        $cmd .= " > /tmp/restore.log 2>&1 &";

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
