<?php
// File: local/cloudsupport/classes/ui/menu_config.php

namespace local_cloudsupport\ui;

defined('MOODLE_INTERNAL') || die();

class menu_config {
    public static function update_custommenu_for_cloud(?string $url = null): void {
        global $CFG, $DB;

        $current = get_config('core', 'custommenuitems');

        if ($url === null) {
            $parsed = parse_url($CFG->wwwroot);
            $host = $parsed['host'] ?? '';
            $host = 'control.' . $host;
            $scheme = $parsed['scheme'] ?? 'https';
            $url = $scheme . '://' . $host;
        }

        $record = $DB->get_record('local_cloudsupport_settings', ['name' => 'control_url']);
        $now = time();

        if ($record) {
            $record->value = $url;
            $record->timemodified = $now;
            $DB->update_record('local_cloudsupport_settings', $record);
        } else {
            $DB->insert_record('local_cloudsupport_settings', (object)[
                'name' => 'control_url',
                'value' => $url,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }

    public static function set_redirect_menu_item(): void {
        $current = get_config('core', 'custommenuitems');

        $url = '/local/cloudsupport/redirect.php';
        $newline = 'Cloud Exams | ' . $url;

        $lines = explode("\n", $current ?? '');
        $found = false;

        foreach ($lines as &$line) {
            if (str_starts_with(trim($line), 'Cloud Exams |')) {
                $line = $newline;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $lines[] = $newline;
        }

        $updated = implode("\n", array_filter(array_map('trim', $lines)));
        set_config('custommenuitems', $updated);
    }

}
