<?php
namespace local_cloudsupport\external;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_course;
use moodle_url;

class export_users extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID of the course to export users from')
        ]);
    }

    public static function execute($courseid) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid
        ]);

        $context = context_course::instance($courseid);
        self::validate_context($context);

        require_capability('moodle/course:viewparticipants', $context);

        $users = get_enrolled_users($context);

        $csvdata = [];
        $csvdata[] = ['ID', 'Username', 'Firstname', 'Lastname', 'Email', 'Roles', 'Auth', 'Suspended', 'Password'];

        foreach ($users as $user) {
            $roles = get_user_roles($context, $user->id, false);
            $rolenames = array_map(fn($r) => $r->shortname, $roles);
            $userrecord = \core_user::get_user($user->id);

            $csvdata[] = [
                $user->id,
                $user->username,
                $user->firstname,
                $user->lastname,
                $user->email,
                implode(', ', $rolenames),
                $user->auth,
                $user->suspended,
                $userrecord->password
            ];
        }

        $course = get_course($courseid);
        $shortname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course->shortname);
        $timestamp = date('Ymd-His');
        $filename = "{$shortname}_users_{$timestamp}.csv";

        // Tạo chuỗi CSV
        $csvstring = '';
        foreach ($csvdata as $line) {
            $csvstring .= implode(',', array_map('addslashes', $line)) . "\n";
        }

        // Ghi vào File API
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'local_cloudsupport',
            'filearea'  => 'backupfiles',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
        ];

        // Xóa file cũ nếu có
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

        // Tạo file mới
        $storedfile = $fs->create_file_from_string($fileinfo, $csvstring);

        // Tạo URL tải file
        $url = moodle_url::make_pluginfile_url(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )->out(false);

        return [
            'status' => 'success',
            'filename' => $filename,
            'contenthash' => $storedfile->get_contenthash(),
            'url' => $url,
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Backup status'),
            'filename' => new external_value(PARAM_FILE, 'Backup filename'),
            'contenthash' => new external_value(PARAM_ALPHANUM, 'Content hash of the backup file'),
            'url' => new external_value(PARAM_URL, 'URL to download the file via pluginfile.php'),
        ]);
    }
}
