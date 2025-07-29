<?php
require('../../config.php');

global $USER, $DB;

// Try get username if logged in
$username = (isloggedin() && !isguestuser()) ? $USER->username : null;

// Get control_url from DB
$record = $DB->get_record('local_cloudsupport_settings', ['name' => 'control_url']);
$baseurl = $record && !empty($record->value) ? $record->value : null;

if (!$baseurl) {
    print_error('No control_url configured in local_cloudsupport_settings.');
    exit;
}

// Ensure URL has scheme
if (!preg_match('#^https?://#', $baseurl)) {
    $baseurl = 'https://' . $baseurl;
}

// Add username query param only if logged in
if ($username) {
    $separator = (parse_url($baseurl, PHP_URL_QUERY) ? '&' : '?');
    $finalurl = $baseurl . $separator . 'username=' . urlencode($username);
} else {
    $finalurl = $baseurl;
}

// Redirect
redirect($finalurl);
