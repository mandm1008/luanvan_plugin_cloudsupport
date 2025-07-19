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

    if ($oldversion < 2025071916) {

        // Define table local_cloudsupport_quizcfg
        $table = new xmldb_table('local_cloudsupport_quizcfg');

        if (!$dbman->table_exists($table)) {
            // Add fields
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('usecloud', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('cloudregion', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

            // Add keys
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('quizfk', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);

            // Create table
            $dbman->create_table($table);
        }

        // Save upgrade point
        upgrade_plugin_savepoint(true, 2025071916, 'local', 'cloudsupport');
    }

    return true;
}
