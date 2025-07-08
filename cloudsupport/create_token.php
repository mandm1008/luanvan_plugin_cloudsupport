<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$providedtoken = $input['main_token'] ?? null;

$EXPECTED_SECRET = getenv('MAIN_TOKEN');

if (!$providedtoken || $providedtoken !== $EXPECTED_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Invalid MAIN_TOKEN']);
    exit;
}

$admin = get_admin();
if (!$admin) {
    http_response_code(500);
    echo json_encode(['error' => 'Admin user not found']);
    exit;
}

//
// ✅ Enable REST protocol and web services globally
//
set_config('enabled', 1, 'webserviceprotocol_rest');
set_config('enablewebservices', 1);

// Tiếp tục như cũ
$servicename = 'Cloud Support Service';
$service = $DB->get_record('external_services', ['name' => $servicename, 'enabled' => 1]);

if (!$service) {
    http_response_code(500);
    echo json_encode(['error' => "Webservice '$servicename' not found or not enabled"]);
    exit;
}

$token = $DB->get_record('external_tokens', [
    'userid' => $admin->id,
    'externalserviceid' => $service->id,
    'tokentype' => EXTERNAL_TOKEN_PERMANENT,
]);

if (!$token) {
    $token = new stdClass();
    $token->token = md5(uniqid(rand(), true));
    $token->userid = $admin->id;
    $token->tokentype = EXTERNAL_TOKEN_PERMANENT;
    $token->externalserviceid = $service->id;
    $token->timecreated = time();
    $token->validuntil = 0;
    $token->iprestriction = null;
    $token->contextid = context_system::instance()->id;

    $token->id = $DB->insert_record('external_tokens', $token);
}

echo json_encode([
    'status' => 'success',
    'token' => $token->token,
    'userid' => $admin->id,
    'username' => $admin->username,
    'service' => $servicename,
]);
exit;
