<?php
namespace local_cloudsupport\external;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_system;

class import_users extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'filename' => new external_value(PARAM_FILE, 'Tên file CSV cần import (từ filearea exportedusers)')
        ]);
    }

    public static function execute($filename) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'filename' => $filename
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        require_capability('moodle/user:create', $context);

        $fs = get_file_storage();
        $storedfile = $fs->get_file(
            $context->id,
            'local_cloudsupport',
            'backupfiles',
            0,
            '/',
            $params['filename']
        );

        if (!$storedfile) {
            throw new \moodle_exception('file not found in File API: ' . $params['filename']);
        }

        $content = $storedfile->get_content();
        $lines = explode("\n", $content);

        if (count($lines) < 2) {
            throw new \moodle_exception('Empty or invalid CSV file');
        }

        $header = str_getcsv(array_shift($lines));
        $required_fields = ['Username', 'Firstname', 'Lastname', 'Email'];
        foreach ($required_fields as $field) {
            if (!in_array($field, $header)) {
                throw new \moodle_exception("Missing required field: $field");
            }
        }

        $created = 0;
        $updated = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') continue;

            $data = str_getcsv($line);
            if (count($data) !== count($header)) continue;

            $row = array_combine($header, $data);
            $username = trim($row['Username']);
            if (!$username) continue;

            if ($user = $DB->get_record('user', ['username' => $username, 'deleted' => 0])) {
                // Update
                $user->firstname = $row['Firstname'];
                $user->lastname = $row['Lastname'];
                $user->email = $row['Email'];
                $user->auth = isset($row['Auth']) ? $row['Auth'] : 'manual';
                $user->suspended = isset($row['Suspended']) ? (int)$row['Suspended'] : 0;

                if (!empty($row['Password'])) {
                    $user->password = $row['Password'];
                }

                $DB->update_record('user', $user);
                $updated++;
            } else {
                // Create
                $newuser = new \stdClass();
                $newuser->username = $username;
                $newuser->firstname = $row['Firstname'];
                $newuser->lastname = $row['Lastname'];
                $newuser->email = $row['Email'];
                $newuser->auth = isset($row['Auth']) ? $row['Auth'] : 'manual';
                $newuser->suspended = isset($row['Suspended']) ? (int)$row['Suspended'] : 0;
                $newuser->confirmed = 1;
                $newuser->mnethostid = $CFG->mnet_localhost_id;

                if (!empty($row['Password'])) {
                    $newuser->password = $row['Password'];
                } else {
                    $newuser->password = hash_internal_user_password(random_string(10));
                }

                $DB->insert_record('user', $newuser);
                $created++;
            }
        }

        return [
            'status' => 'success',
            'created' => $created,
            'updated' => $updated
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status'),
            'created' => new external_value(PARAM_INT, 'Số user được tạo'),
            'updated' => new external_value(PARAM_INT, 'Số user được cập nhật')
        ]);
    }
}
