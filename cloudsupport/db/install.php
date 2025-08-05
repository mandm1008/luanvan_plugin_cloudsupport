<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin install function for local_cloudsupport.
 */
function xmldb_local_cloudsupport_install(): void {
    \local_cloudsupport\ui\menu_config::set_redirect_menu_item();
    \local_cloudsupport\ui\menu_config::update_custommenu_for_cloud();
}
