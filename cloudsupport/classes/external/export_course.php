<?php
namespace local_cloudsupport\external;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_course;
use context_system;
use moodle_exception;

class export_course extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID to backup'),
            'webhookapi' => new external_value(PARAM_RAW, 'Webhook endpoint to call after backup (optional)', VALUE_DEFAULT, ''),
            'webhooktoken' => new external_value(PARAM_RAW, 'Token to include in webhook (optional)', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute($courseid, $webhookapi, $webhooktoken) {
        global $CFG, $USER;

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/backup:backupcourse', $context);

        // ✅ Nếu có webhook, gọi CLI runner (chạy nền)
        if (!empty($webhookapi)) {
            $script = $CFG->dirroot . '/local/cloudsupport/cli/backup_runner.php';
            $cmd = "nohup php $script --courseid=" . escapeshellarg($courseid);

            if (!empty($webhookapi)) {
                $cmd .= " --webhook-api=" . escapeshellarg($webhookapi);
            }

            if (!empty($webhooktoken)) {
                $cmd .= " --token=" . escapeshellarg($webhooktoken);
            }

            $cmd .= " > /tmp/backup.log 2>&1 &";
            exec($cmd);

            return [
                'status' => 'success',
                'message' => 'Backup is running in background by ' . $cmd,
                'filename' => '',
                'contenthash' => '',
                'url' => '',
            ];
        }

        // ✅ Nếu không có webhook, thực hiện backup như cũ
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $courseid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );

        $plan = $bc->get_plan();

        $settings = [
            'users' => true,
            'anonymize' => false,
            'role_assignments' => true,
            'activities' => true,
            'blocks' => true,
            'filters' => true,
            'comments' => true,
            'completion_information' => true,
            'badges' => true,
            'calendarevents' => true,
            'groups' => true,
            'groupings' => true,
            'logs' => true,
            'grade_histories' => true
        ];

        foreach ($settings as $key => $value) {
            if ($plan->setting_exists($key)) {
                $plan->get_setting($key)->set_value($value);
            }
        }

        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $course = get_course($courseid);
        $shortname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course->shortname);
        $timestamp = date('Ymd-His');
        $backupfilename = "{$shortname}-{$timestamp}.mbz";

        $tempcontent = $file->get_content();

        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => context_system::instance()->id,
            'component' => 'local_cloudsupport',
            'filearea'  => 'backupfiles',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $backupfilename,
        ];

        if ($fs->file_exists(...array_values($fileinfo))) {
            $fs->get_file(...array_values($fileinfo))->delete();
        }

        $storedfile = $fs->create_file_from_string($fileinfo, $tempcontent);
        $bc->destroy();

        $url = \moodle_url::make_pluginfile_url(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )->out(false);

        return [
            'status' => 'success',
            'message' => 'Backup completed successfully',
            'filename' => $storedfile->get_filename(),
            'contenthash' => $storedfile->get_contenthash(),
            'url' => $url
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of backup'),
            'message' => new external_value(PARAM_TEXT, 'Details or command line used'),
            'filename' => new external_value(PARAM_FILE, 'Backup filename', VALUE_OPTIONAL),
            'contenthash' => new external_value(PARAM_ALPHANUM, 'Content hash of the backup file', VALUE_OPTIONAL),
            'url' => new external_value(PARAM_URL, 'Download URL of the backup file', VALUE_OPTIONAL),
        ]);
    }
}
