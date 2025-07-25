<?php
// CLI script to backup a Moodle course and store it in File API.
//
// @package     local_cloudsupport
// @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

ini_set('log_errors', 1);
ini_set('error_log', 'php://stdout');

$help = <<<EOD
Backup a Moodle course to File API.

Usage:
  php backup_runner.php --courseid=ID [--webhook-api=URL] [--token=TOKEN]

Options:
  --courseid=ID           The course ID to backup (required)
  --webhook-api=URL       Optional: Webhook URL to notify after backup
  --token=TOKEN           Optional: Token to include in webhook payload
  -h, --help              Show this help

EOD;

// CLI options
list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'courseid' => null,
    'webhook-api' => null,
    'token' => null,
], [
    'h' => 'help'
]);

if ($unrecognized) {
    $unrecognized = implode(PHP_EOL . '  ', $unrecognized);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

if (!isset($options['courseid'])) {
    cli_error("Missing required parameter: --courseid\n\n$help", 2);
}

$courseid = (int)$options['courseid'];
$webhookApi = $options['webhook-api'] ?? null;
$token = $options['token'] ?? null;

// Hàm gửi webhook
function send_webhook($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    cli_writeln("📡 Webhook sent to $url, HTTP $httpcode");
    cli_writeln("🔁 Response: $response");
}

cli_writeln("🚀 Starting backup for course ID: $courseid");

$course = get_course($courseid);
$context = context_course::instance($courseid);
// require_capability('moodle/backup:backupcourse', $context);

$USER = get_admin();

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

// Tạo tên file
$shortname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course->shortname);
$timestamp = date('Ymd-His');
$backupfilename = "{$shortname}-{$timestamp}.mbz";

// Lưu vào File API
$fs = get_file_storage();
$fileinfo = [
    'contextid' => context_system::instance()->id,
    'component' => 'local_cloudsupport',
    'filearea'  => 'backupfiles',
    'itemid'    => 0,
    'filepath'  => '/',
    'filename'  => $backupfilename,
];

// Xóa file trùng tên nếu có
if ($fs->file_exists(...array_values($fileinfo))) {
    $fs->get_file(...array_values($fileinfo))->delete();
}

$storedfile = $fs->create_file_from_string($fileinfo, $file->get_content());
$bc->destroy();

$url = moodle_url::make_pluginfile_url(
    $fileinfo['contextid'],
    $fileinfo['component'],
    $fileinfo['filearea'],
    $fileinfo['itemid'],
    $fileinfo['filepath'],
    $fileinfo['filename']
)->out(false);

cli_writeln("✅ Backup complete: {$backupfilename}");
cli_writeln("🔗 Download URL: $url");

if (!empty($webhookApi)) {
    $payload = [
        'eventname' => '\\local_cloudsupport\\event\\backup_finished',
        'other' => [
            'courseid' => $courseid,
            'token' => $token,
            'filename' => $backupfilename,
            'contenthash' => $storedfile->get_contenthash(),
            'url' => $url,
            'status' => 'success',
        ],
        'host' => parse_url($CFG->wwwroot, PHP_URL_HOST),
    ];
    send_webhook($webhookApi, $payload);
}
