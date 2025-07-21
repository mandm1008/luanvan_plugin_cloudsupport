<?php
// CLI script to register a webhook from local_cloudsupport.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Load the webhook handling functions.
require_once($CFG->dirroot . '/local/webhooks/lib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'callback-url' => null,
    'type' => 'json',
    'token' => null,
    'update' => false
], [
    'h' => 'help',
]);

if (!empty($options['help']) || empty($options['callback-url'])) {
$help = "Register a webhook for the local_cloudsupport plugin.

Options:
  --callback-url     Webhook destination URL (required)
  --type             Payload format. Default is 'json'.
  --token            Token for authentication (random if omitted)
  --update           Update flag
  -h, --help         Show this help message

Example:
  php register_webhook.php --callback-url=https://example.com/api/webhook
";

    cli_error($help);
}

$url = $options['callback-url'];
$type = $options['type'];
$token = $options['token'] ?? bin2hex(random_bytes(16));
$update = $options['update'];
$eventname = '\\local_cloudsupport\\event\\quiz_time_updated';

// Build service data object.
$data = (object)[
    'enable' => 1,
    'title' => parse_url($url, PHP_URL_HOST) . ' webhook',
    'url' => $url,
    'type' => $type,
    'token' => $token,
    'events' => [
        $eventname => 1,
    ],
];

// Insert the new webhook record.
if ($update) {
    $existing = $DB->get_record('local_webhooks_service', ['url' => $url]);

    if (!$existing) {
        cli_error("❌ Cannot update: No existing webhook found with URL: $url");
    }

    $data->id = $existing->id;
    $result = local_webhooks_update_record($data, false);
} else {
    $result = local_webhooks_update_record($data, true);
}

// CLI output
cli_writeln("✅ Webhook registration completed.");
cli_writeln("  - Callback URL : $url");
cli_writeln("  - Token        : $token");
cli_writeln("  - Payload type : $type");
cli_writeln("  - Event        : " . $eventname);
