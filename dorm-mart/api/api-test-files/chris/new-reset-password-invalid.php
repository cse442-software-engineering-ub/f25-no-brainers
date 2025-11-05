<?php
/*
 * Test: Reset Password - password fails policy (too short / missing classes)
 * Runtime behavior:
 *  - Locally: Calls your local /api/auth/reset-password.php on the same host (npm/Apache).
 *  - Aptitude/Cattle: Calls the same path on that server; no CORS issues (server-side request).
 * Expected HTTP status codes from THIS test file:
 *  - 200: Test executed and returned a PASS/FAIL payload (expect policy error upstream).
 *  - 400: The test input to THIS file was invalid (e.g., missing token/newPassword).
 *  - 500: Network failure when calling the upstream API.
 */
header('Content-Type: application/json; charset=utf-8');

// Test: Invalid password should be rejected by reset-password API

function build_api_url(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $req  = $_SERVER['REQUEST_URI'] ?? '/';
    $base = preg_replace('#/api/api-test-files/.*$#', '/api', $req);
    return $scheme . '://' . $host . $base . $path;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (!isset($input['token']) || !isset($input['newPassword'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: token, newPassword'
    ]);
    exit;
}

$token = (string)$input['token'];
$newPassword = (string)$input['newPassword'];

$resetPasswordUrl = build_api_url('/auth/reset-password.php');

$postData = json_encode([
    'token' => $token,
    'newPassword' => $newPassword,
]);

$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAccept: application/json",
        'content' => $postData,
        'ignore_errors' => true,
    ]
]);

$result = @file_get_contents($resetPasswordUrl, false, $context);

if ($result === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to call reset-password API',
        'test_result' => 'ERROR - API call failed'
    ]);
    exit;
}

$response = json_decode($result, true);

$errorText = (string)($response['error'] ?? '');
if ($errorText !== '' && (stripos($errorText, 'password') !== false || stripos($errorText, 'require') !== false)) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $errorText,
        'test_result' => 'PASS - API rejected invalid password',
        'api_response' => $response,
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'API did not return expected validation error for password',
        'api_response' => $response,
        'test_result' => 'FAIL - Expected password policy error'
    ]);
}
?>
