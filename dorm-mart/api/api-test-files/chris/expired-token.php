<?php
header('Content-Type: application/json; charset=utf-8');

// Test: Expired token should be rejected by reset-password API

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

// Call the actual reset-password API (expects keys: token, newPassword)
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

// Expect an error that indicates the token is invalid/expired (status code may vary)
$errorText = (string)($response['error'] ?? '');
if ($errorText !== '' && (stripos($errorText, 'expired') !== false || stripos($errorText, 'invalid') !== false)) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $errorText,
        'test_result' => 'PASS - API rejected expired/invalid token',
        'api_response' => $response,
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'API did not return expected error for expired/invalid token',
        'api_response' => $response,
        'test_result' => 'FAIL - Expected expired/invalid token error'
    ]);
}
