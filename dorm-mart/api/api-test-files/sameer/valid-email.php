<?php
/*
 * Test: Forgot Password - valid UB email
 * Runtime behavior:
 *  - Locally: Calls your local /api/auth/forgot-password.php on the same host (npm/Apache).
 *  - Aptitude/Cattle: Calls the same path on that server; no CORS issues (server-side request).
 * Expected HTTP status codes from THIS test file:
 *  - 200: Test executed and returned a PASS/FAIL payload (expect success=true upstream).
 *  - 400: The test input to THIS file was invalid (e.g., missing/invalid email format).
 *  - 500: Network failure when calling the upstream API or database error.
 */
header('Content-Type: application/json; charset=utf-8');

// Test: Verify forgot-password returns success for a valid UB email

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

function get_valid_email_from_db(): ?string {
    // Get database path relative to this file
    $root = dirname(__DIR__, 3);
    require_once $root . '/api/database/db_connect.php';
    
    try {
        $conn = db();
        
        // Find a user with valid UB email
        $stmt = $conn->prepare('SELECT email FROM user_accounts WHERE email LIKE ? LIMIT 1');
        $pattern = '%@buffalo.edu';
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return null;
        }
        
        $row = $result->fetch_assoc();
        $email = (string)$row['email'];
        $stmt->close();
        $conn->close();
        
        return $email;
    } catch (Exception $e) {
        return null;
    }
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// If email not provided, get valid email from database
if (!isset($input['email'])) {
    $validEmail = get_valid_email_from_db();
    if ($validEmail === null) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get valid email from database',
            'test_result' => 'ERROR - Database operation failed'
        ]);
        exit;
    }
    $email = $validEmail;
} else {
    $email = (string)$input['email'];
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@buffalo\.edu$/', $email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email must be a valid UB email address']);
    exit;
}

$forgotPasswordUrl = build_api_url('/auth/forgot-password.php');

$postData = json_encode(['email' => $email]);
$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAccept: application/json",
        'content' => $postData,
        'ignore_errors' => true,
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

// Verify HTTP status code is 200 (API returns 200 with success in body)
// Verify success response structure
$hasSuccessTrue = isset($response['success']) && $response['success'] === true;
$hasCorrectStatus = $httpStatusCode === 200;
$hasMessage = isset($response['message']);

if ($hasCorrectStatus && $hasSuccessTrue && $hasMessage) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'test_result' => 'PASS - API returned success for valid email',
        'http_status_code' => $httpStatusCode,
        'api_response' => $response,
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'test_result' => 'FAIL - Expected success for valid email',
        'http_status_code' => $httpStatusCode,
        'expected_status' => 200,
        'expected_success' => true,
        'api_response' => $response,
    ]);
}
