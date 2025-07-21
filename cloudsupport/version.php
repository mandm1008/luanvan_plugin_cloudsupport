<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_cloudsupport';
$plugin->version   = 2025071917;            // Định dạng YYYYMMDDXX
$plugin->requires  = 2023111300;            // Moodle 5.0 (Build: 20231113)
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0 for Moodle 5.0';

$plugin->dependencies = [
    'local_webhooks' => ANY_VERSION,
    'tool_objectfs' => ANY_VERSION,
];
