<?php
namespace local_cloudsupport\external;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../../lib/externallib.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_system;
use moodle_exception;
use moodle_url;

class upload_backup_file extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'filename' => new external_value(PARAM_FILE, 'Tên file (vd: backup.mbz)'),
            'filecontent' => new external_value(PARAM_RAW, 'Nội dung file được mã hóa base64'),
        ]);
    }

    public static function execute($filename, $filecontent) {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'filename' => $filename,
            'filecontent' => $filecontent,
        ]);

        // Kiểm tra định dạng .mbz
        if (pathinfo($params['filename'], PATHINFO_EXTENSION) !== 'mbz') {
            throw new moodle_exception('Invalid file format. Expected .mbz');
        }

        $context = context_system::instance();
        self::validate_context($context);

        $decoded = base64_decode($params['filecontent'], true);
        if ($decoded === false) {
            throw new moodle_exception('Failed to decode base64 file content.');
        }

        $fs = get_file_storage();

        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'local_cloudsupport',
            'filearea'  => 'backupfiles',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $params['filename'],
        ];

        // Xóa file cũ nếu đã tồn tại (tránh lỗi duplicate)
        if ($fs->file_exists(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )) {
            $fs->get_file(
                $fileinfo['contextid'],
                $fileinfo['component'],
                $fileinfo['filearea'],
                $fileinfo['itemid'],
                $fileinfo['filepath'],
                $fileinfo['filename']
            )->delete();
        }

        // Tạo file trong hệ thống Moodle
        $file = $fs->create_file_from_string($fileinfo, $decoded);

        if (!$file) {
            throw new moodle_exception('Failed to create file in Moodle file storage.');
        }

        return [
            'status' => 'success',
            'filename' => $file->get_filename(),
            'contenthash' => $file->get_contenthash(),
            'url' => moodle_url::make_pluginfile_url(
                $fileinfo['contextid'],
                $fileinfo['component'],
                $fileinfo['filearea'],
                $fileinfo['itemid'],
                $fileinfo['filepath'],
                $fileinfo['filename']
            )->out(false)
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Trạng thái thao tác'),
            'filename' => new external_value(PARAM_FILE, 'Tên file đã lưu'),
            'contenthash' => new external_value(PARAM_ALPHANUM, 'Content hash của file'),
            'url' => new external_value(PARAM_URL, 'URL truy cập qua pluginfile.php'),
        ]);
    }
}
