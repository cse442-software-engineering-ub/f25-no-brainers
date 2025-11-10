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

function extract_http_status_code(): int {
    global $http_response_header;
    if (!isset($http_response_header) || empty($http_response_header)) {
        return 0;
    }
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
    return isset($matches[1]) ? (int)$matches[1] : 0;
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
$httpStatusCode = extract_http_status_code();

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

// Verify HTTP status code is 400 (API returns 400 for invalid password)
// Verify error message indicates password policy failure
$errorText = (string)($response['error'] ?? '');
$hasExpectedError = $errorText !== '' && stripos($errorText, 'Password does not meet policy requirements') !== false;
$hasCorrectStatus = $httpStatusCode === 400;
$hasSuccessFalse = isset($response['success']) && $response['success'] === false;

if ($hasCorrectStatus && $hasSuccessFalse && $hasExpectedError) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'test_result' => 'PASS - API rejected invalid password',
        'http_status_code' => $httpStatusCode,
        'api_response' => $response,
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'test_result' => 'FAIL - Expected password policy error',
        'http_status_code' => $httpStatusCode,
        'expected_status' => 400,
        'expected_error' => 'Password does not meet policy requirements',
        'api_response' => $response,
    ]);
}
?>
