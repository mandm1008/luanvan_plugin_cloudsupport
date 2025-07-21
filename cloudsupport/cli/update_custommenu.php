<?php
// CLI script to update Moodle custom menu with Cloud Exams link.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once(__DIR__ . '/../locallib.php');

// CLI options
list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'url' => null,
], [
    'h' => 'help',
]);

if ($options['help']) {
    $help = <<<EOF
Update Moodle custom menu with Cloud Exams link.

Options:
    --url=URL        Optional. URL to use. If omitted, it will derive from \$CFG->wwwroot
    -h, --help       Show this help

Example:
    php update_custommenu.php --url=https://control.elsystem.dominhman.id.vn
    php update_custommenu.php

EOF;
    echo $help;
    exit(0);
}

$url = $options['url'] ?? null;

if ($url) {
    echo "🔧 Updating custom menu with provided URL: $url\n";
} else {
    echo "🔧 No URL provided. Will derive from \$CFG->wwwroot\n";
}

local_cloudsupport_update_custommenu_for_cloud($url);

echo "✅ Custom menu updated successfully.\n";
