<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin install function for local_cloudsupport.
 */
function xmldb_local_cloudsupport_install() {
    global $DB;

    // add cloud exams to menu
    $current = get_config('core', 'custommenuitems');
    $newline = 'Cloud Exams | https://control.elsystem.dominhman.id.vn';
    if (strpos($current, $newline) === false) {
        $updated = trim($current . "\n" . $newline);
        set_config('custommenuitems', $updated);
    }
}
