<?php
/*
 * Test: Reset Password - invalid/expired token (API has no confirm field)
 * Runtime behavior:
 *  - Locally: Calls your local /api/auth/reset-password.php on the same host (npm/Apache).
 *  - Aptitude/Cattle: Calls the same path on that server; no CORS issues (server-side request).
 * Expected HTTP status codes from THIS test file:
 *  - 200: Test executed and returned a PASS/FAIL payload (expect error upstream mentioning invalid/expired token).
 *  - 400: The test input to THIS file was invalid (e.g., missing token/newPassword).
 *  - 500: Network failure when calling the upstream API or database error.
 */
header('Content-Type: application/json; charset=utf-8');

// Test: Invalid token (or mismatch scenario) should be rejected by reset-password API
// Note: Backend API does not accept a confirmation field; this test focuses on token validity

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

function generate_expired_token(): ?string {
    // Get database path relative to this file
    $root = dirname(__DIR__, 3);
    require_once $root . '/api/database/db_connect.php';
    
    try {
        $conn = db();
        
        // Find a test user (prefer one that exists, or use first user)
        $stmt = $conn->prepare('SELECT user_id FROM user_accounts LIMIT 1');
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return null;
        }
        
        $row = $result->fetch_assoc();
        $userId = (int)$row['user_id'];
        $stmt->close();
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $hashedToken = password_hash($resetToken, PASSWORD_BCRYPT);
        
        // Set expiration to 2 hours ago (expired)
        $expiresAt = date('Y-m-d H:i:s', strtotime('-2 hours'));
        
        // Update user_accounts with expired token
        $stmt = $conn->prepare('UPDATE user_accounts SET hash_auth = ?, reset_token_expires = ? WHERE user_id = ?');
        $stmt->bind_param('ssi', $hashedToken, $expiresAt, $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        
        return $resetToken; // Return plain token (not hashed)
    } catch (Exception $e) {
        return null;
    }
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// If token not provided, generate expired token from database
if (!isset($input['token'])) {
    $expiredToken = generate_expired_token();
    if ($expiredToken === null) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate expired token from database',
            'test_result' => 'ERROR - Database operation failed'
        ]);
        exit;
    }
    $token = $expiredToken;
} else {
    $token = (string)$input['token'];
}

if (!isset($input['newPassword'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required field: newPassword'
    ]);
    exit;
}

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

// Verify HTTP status code is 200 (API returns 200 with error in body for invalid token)
// Verify error message indicates expired/invalid token
$errorText = (string)($response['error'] ?? '');
$hasExpectedError = $errorText !== '' && (stripos($errorText, 'expired') !== false || stripos($errorText, 'invalid') !== false);
$hasCorrectStatus = $httpStatusCode === 200;
$hasSuccessFalse = isset($response['success']) && $response['success'] === false;

if ($hasCorrectStatus && $hasSuccessFalse && $hasExpectedError) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'test_result' => 'PASS - API rejected invalid/expired token',
        'http_status_code' => $httpStatusCode,
        'api_response' => $response,
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'test_result' => 'FAIL - Expected invalid/expired token error',
        'http_status_code' => $httpStatusCode,
        'expected_status' => 200,
        'expected_error' => 'Invalid or expired reset token',
        'api_response' => $response,
    ]);
}
?>
