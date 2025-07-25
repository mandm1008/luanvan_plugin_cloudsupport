<?php
// File: local/cloudsupport/classes/ui/menu_config.php

namespace local_cloudsupport\ui;

defined('MOODLE_INTERNAL') || die();

class menu_config {
    public static function update_custommenu_for_cloud(?string $url = null): void {
        global $CFG;

        $current = get_config('core', 'custommenuitems');

        if (!$url) {
            $parsed = parse_url($CFG->wwwroot);
            $host = $parsed['host'] ?? '';
            $host = 'control.' . $host;
            $scheme = $parsed['scheme'] ?? 'https';
            $url = $scheme . '://' . $host;
        }

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
