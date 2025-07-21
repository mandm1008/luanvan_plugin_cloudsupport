<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin install function for local_cloudsupport.
 */
function xmldb_local_cloudsupport_install(): void {
    require_once(__DIR__ . '/../locallib.php');

    local_cloudsupport_update_custommenu_for_cloud();
}
