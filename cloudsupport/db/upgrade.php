<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for local_cloudsupport plugin
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_cloudsupport_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    return true;
}
