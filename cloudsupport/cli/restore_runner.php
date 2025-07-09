<?php
// CLI script to restore a Moodle course from a .mbz file in File API.
//
// @package     local_cloudsupport
// @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

// Ghi log ra stdout
ini_set('log_errors', 1);
ini_set('error_log', 'php://stdout');

// Help text
$help = <<<EOD
Restore a Moodle course from a .mbz backup file stored in File API.

Usage:
  php restore_runner.php --filename=FILENAME --courseid=ID [--webhook-api=URL] [--token=TOKEN]

Options:
  --filename=FILENAME     The .mbz file uploaded to File API
  --courseid=ID           The course ID to restore into (0 = create new)
  --webhook-api=URL       Optional: Webhook URL to notify after restore
  --token=TOKEN           Optional: Token to include in webhook payload
  -h, --help              Show this help

EOD;

// CLI options
list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'filename' => null,
    'courseid' => null,
    'webhook-api' => null,
    'token' => null,
], [
    'h' => 'help'
]);

// Handle unrecognized options
if ($unrecognized) {
    $unrecognized = implode(PHP_EOL . '  ', $unrecognized);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognized));
}

// Show help
if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

// Validate required parameters
if (empty($options['filename']) || !isset($options['courseid'])) {
    cli_error("Missing required parameters: --filename and --courseid\n\n$help", 2);
}

$filename = $options['filename'];
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

cli_writeln("🔁 Starting restore: filename=\"$filename\", courseid=$courseid, webhookapi=$webhookApi");

// Lấy file từ File API
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
    cli_error("❌ File not found in File API: $filename");
}

$backupid = $storedfile->get_contenthash();
$tempdir = make_backup_temp_directory($backupid);

// Copy và giải nén
$zipfilepath = $tempdir . '/' . $filename;
$storedfile->copy_content_to($zipfilepath);
$fp = get_file_packer('application/vnd.moodle.backup');
$fp->extract_to_pathname($zipfilepath, $tempdir);
@unlink($zipfilepath);

// Sử dụng admin user
$USER = get_admin();

// Xác định target và course id
$target = \backup::TARGET_EXISTING_DELETING;
$restorecontext = null;
$restorecourseid = $courseid;

if ($courseid <= 0 || !$DB->record_exists('course', ['id' => $courseid])) {
    $target = \backup::TARGET_NEW_COURSE;
    $restorecontext = context_system::instance();
    $restorecourseid = \restore_dbops::create_new_course('', '', 1);
} else {
    $restorecontext = context_course::instance($courseid);
}

// Khởi tạo controller
$controller = new \restore_controller(
    $backupid,
    $restorecourseid,
    \backup::INTERACTIVE_NO,
    \backup::MODE_GENERAL,
    $USER->id,
    $target
);

if (!$controller) {
    cli_error("❌ Failed to create restore controller.");
}

// Giai đoạn chuẩn bị
if ($controller->get_status() === \restore_controller::STATUS_REQUIRE_CONV) {
    $controller->convert();
}

if ($controller->get_status() === \restore_controller::STATUS_SETTING_UI) {
    $controller->finish_ui();
}

// Precheck
$precheck = $controller->execute_precheck();
if ($precheck !== true) {
    cli_writeln("❌ Precheck failed:");
    foreach ($precheck as $error) {
        cli_writeln("  - $error");
    }

    // Gửi webhook nếu có
    if (!empty($webhookApi)) {
        $payload = [
            'eventname' => '\\local_cloudsupport\\event\\restore_finished',
            'other' => [
                'courseid' => $restorecourseid,
                'token' => $token,
                'status' => 'failed',
                'error' => implode("; ", $precheck),
            ],
            'host' => parse_url($CFG->wwwroot, PHP_URL_HOST),
        ];
        send_webhook($webhookApi, $payload);
    }

    exit(1);
}

// Restore
$controller->execute_plan();
$restoredid = $controller->get_courseid();

if ($restoredid && $restoredid > 0) {
    cli_writeln("✅ Course restored successfully. ID = $restoredid");

    if (!empty($webhookApi)) {
        $payload = [
            'eventname' => '\\local_cloudsupport\\event\\restore_finished',
            'other' => [
                'courseid' => $restoredid,
                'token' => $token,
                'status' => 'success',
            ],
            'host' => parse_url($CFG->wwwroot, PHP_URL_HOST),
        ];
        send_webhook($webhookApi, $payload);
    }
} else {
    cli_writeln("❌ Restore failed.");

    if (!empty($webhookApi)) {
        $payload = [
            'eventname' => '\\local_cloudsupport\\event\\restore_finished',
            'other' => [
                'courseid' => $restoredid,
                'token' => $token,
                'status' => 'failed',
                'error' => 'Restore did not complete successfully',
            ],
            'host' => parse_url($CFG->wwwroot, PHP_URL_HOST),
        ];
        send_webhook($webhookApi, $payload);
    }
}

$controller->destroy();
