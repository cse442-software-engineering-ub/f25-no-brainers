<?php
/*
 * Test: Forgot Password - invalid/non-existent UB email
 * Runtime behavior:
 *  - Locally: Calls your local /api/auth/forgot-password.php on the same host (npm/Apache).
 *  - Aptitude/Cattle: Calls the same path on that server; no CORS issues (server-side request).
 * Expected HTTP status codes from THIS test file:
 *  - 200: Test executed and returned a PASS/FAIL payload (upstream may have any status).
 *  - 400: The test input to THIS file was invalid (e.g., missing/invalid email format).
 *  - 500: Network failure when calling the upstream API.
 */
header('Content-Type: application/json; charset=utf-8');

// Test: Verify forgot-password returns error for non-existent/invalid UB email

// Helper: build absolute API URL on current host
function build_api_url(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $req  = $_SERVER['REQUEST_URI'] ?? '/';
    // Replace \/api\/api-test-files\/.* with \/api
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (!isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

$email = (string)$input['email'];

// Validate email format (UB emails only)
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@buffalo\.edu$/', $email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email must be a valid UB email address']);
    exit;
}

// Call the actual forgot-password API on the same host/env
$forgotPasswordUrl = build_api_url('/auth/forgot-password.php');

$postData = json_encode(['email' => $email]);
$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAccept: application/json",
        'content' => $postData,
        'ignore_errors' => true, // capture body even on 4xx/5xx
    ]
]);

$result = @file_get_contents($forgotPasswordUrl, false, $context);
$httpStatusCode = extract_http_status_code();

if ($result === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to call forgot-password API',
        'test_result' => 'ERROR - API call failed'
    ]);
    exit;
}

$response = json_decode($result, true);

// Verify HTTP status code is 200 (API returns 200 with error in body for non-existent email)
// Verify error message indicates email not found
$errorText = (string)($response['error'] ?? '');
$hasExpectedError = $errorText !== '' && stripos($errorText, 'Email not found') !== false;
$hasCorrectStatus = $httpStatusCode === 200;
$hasSuccessFalse = isset($response['success']) && $response['success'] === false;

if ($hasCorrectStatus && $hasSuccessFalse && $hasExpectedError) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'test_result' => 'PASS - API returned error for invalid/non-existent email',
        'http_status_code' => $httpStatusCode,
        'api_response' => $response,
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'test_result' => 'FAIL - Expected error response for invalid/non-existent email',
        'http_status_code' => $httpStatusCode,
        'expected_status' => 200,
        'expected_error' => 'Email not found',
        'api_response' => $response,
    ]);
}
