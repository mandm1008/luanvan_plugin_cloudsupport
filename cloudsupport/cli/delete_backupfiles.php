<?php
// local/cloudsupport/cli/delete_backupfiles.php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/filelib.php');

cli_heading('Xóa tất cả các file trong local_cloudsupport::backupfiles');

$fs = get_file_storage();
$contextid = context_system::instance()->id;

// Lấy tất cả file trong component 'local_cloudsupport', filearea 'backupfiles'
$files = $fs->get_area_files($contextid, 'local_cloudsupport', 'backupfiles', 0, 'itemid, filepath, filename', false);

$total = count($files);

if ($total === 0) {
    mtrace("✅ Không có file nào để xóa.");
    exit(0);
}

mtrace("🔍 Đã tìm thấy {$total} file. Đang tiến hành xóa...");

$deleted = 0;
foreach ($files as $file) {
    $file->delete();
    $deleted++;
}

mtrace("🗑️  Đã xóa {$deleted} file trong filearea 'backupfiles'.");
