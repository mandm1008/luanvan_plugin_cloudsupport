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
  php restore_runner.php --filename=FILENAME --courseid=ID

Options:
  --filename=FILENAME     The .mbz file uploaded to File API
  --courseid=ID           The course ID to restore into (0 = create new)
  -h, --help              Show this help

EOD;

// CLI options
list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'filename' => null,
    'courseid' => null,
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

// Assign CLI args
$filename = $options['filename'];
$courseid = (int)$options['courseid'];

cli_writeln("🔁 Starting restore: filename=\"$filename\", courseid=$courseid");

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
if ($controller->get_status() === \backup::STATUS_REQUIRE_CONV) {
    $controller->convert();
}

if ($controller->get_status() === \backup::STATUS_SETTING_UI) {
    $controller->finish_ui();
}

// Precheck
$precheck = $controller->execute_precheck();
if ($precheck !== true) {
    cli_writeln("❌ Precheck failed:");
    foreach ($precheck as $error) {
        cli_writeln("  - $error");
    }
    exit(1);
}

// Restore
$controller->execute_plan();

if ($controller->get_status() === \backup::STATUS_COMPLETED) {
    $restoredid = $controller->get_courseid();
    cli_writeln("✅ Course restored successfully. ID = $restoredid");
} else {
    cli_writeln("❌ Restore failed.");
}

$controller->destroy();
