<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin install function for local_cloudsupport.
 */
function xmldb_local_cloudsupport_install(): void {
    \local_cloudsupport\ui\menu_manager::update_custommenu_for_cloud();
}
