<?php

declare(strict_types=1);

// Include security headers for XSS protection
require_once __DIR__ . '/../security/security.php';
setSecurityHeaders();
// Ensure CORS headers are present for React dev server and local PHP server
setSecureCORS();

header('Content-Type: application/json; charset=utf-8');

// HTTPS enforcement for production (exclude localhost for development)
$isLocalhost = (
    ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' ||
    ($_SERVER['HTTP_HOST'] ?? '') === 'localhost:8080' ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') === 0
);

if (!$isLocalhost && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    $httpsUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    header("Location: $httpsUrl", true, 301);
    exit;
}

require __DIR__ . '/auth_handle.php';
require __DIR__ . '/../database/db_connect.php';

// Respond to CORS preflight after setting CORS headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Rate limiting will be checked after recording failed attempts
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$ct = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    // XSS PROTECTION: Decode JSON first, then validate individual fields (don't HTML-encode JSON)
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON format']);
        exit;
    }

    $emailRaw = strtolower(trim((string) ($data['email'] ?? '')));
    $passwordRaw = (string) ($data['password'] ?? '');
} else {
    $emailRaw = strtolower(trim((string) ($_POST['email'] ?? '')));
    $passwordRaw = (string) ($_POST['password'] ?? '');
}

// XSS PROTECTION: Check for XSS patterns in email field (single check, no duplication)
// Note: SQL injection is already prevented by prepared statements
if (containsXSSPattern($emailRaw)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input format']);
    exit;
}

$email = validateInput($emailRaw, 50, '/^[^@\s]+@buffalo\.edu$/');
$password = validateInput($passwordRaw, 64);

if ($email === false || $password === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input format']);
    exit;
}

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}
if (strlen($email) >= 50 || strlen($password) >= 64) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Username or password is too large']);
    exit;
}
if (!preg_match('/^[^@\s]+@buffalo\.edu$/', $email)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email must be @buffalo.edu']);
    exit;
}

try {
    // Check session rate limiting before email validation to prevent brute force attacks
    $sessionRateLimitCheck = check_session_rate_limit();
    
    if (isset($sessionRateLimitCheck['blocked']) && $sessionRateLimitCheck['blocked']) {
        $lockoutUntil = $sessionRateLimitCheck['lockout_until'] ?? null;
        $remainingMinutes = get_remaining_lockout_minutes($lockoutUntil);
        http_response_code(429);
        echo json_encode([
            'ok' => false, 
            'error' => "Too many failed attempts. Please try again in {$remainingMinutes} minutes."
        ]);
        exit;
    }

    $conn = db();

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare('SELECT user_id, hash_pass FROM user_accounts WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $stmt->close();
        $conn->close();

        // Record failed attempt (return same error message to prevent email enumeration)
        $lockoutInfo = record_session_failed_attempt();
        
        // Check if we just hit the lockout threshold
        if ($lockoutInfo && isset($lockoutInfo['blocked']) && $lockoutInfo['blocked']) {
            $remainingMinutes = get_remaining_lockout_minutes($lockoutInfo['lockout_until']);
            http_response_code(429);
            echo json_encode([
                'ok' => false, 
                'error' => "Too many failed attempts. Please try again in {$remainingMinutes} minutes."
            ]);
            exit;
        }

        http_response_code(401);
    echo json_encode([
        'ok' => false, 
        'error' => 'Invalid credentials'
    ]);
    exit;
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    // Verify password using bcrypt hash
    if (!password_verify($password, (string) $row['hash_pass'])) {
        $conn->close();

        // Record failed attempt
        $lockoutInfo = record_session_failed_attempt();
        
        // Check if we just hit the lockout threshold
        if ($lockoutInfo && isset($lockoutInfo['blocked']) && $lockoutInfo['blocked']) {
            $remainingMinutes = get_remaining_lockout_minutes($lockoutInfo['lockout_until']);
            http_response_code(429);
            echo json_encode([
                'ok' => false, 
                'error' => "Too many failed attempts. Please try again in {$remainingMinutes} minutes."
            ]);
            exit;
        }

        http_response_code(401);
    echo json_encode([
        'ok' => false, 
        'error' => 'Invalid credentials'
    ]);
    exit;
    }

    $userId = (int) $row['user_id'];

    // SQL INJECTION PROTECTION: Prepared statement for theme query (user_id parameter bound safely)
    $themeStmt = $conn->prepare('SELECT theme FROM user_accounts WHERE user_id = ?');
    $themeStmt->bind_param('i', $userId);  // 'i' = integer type
    $themeStmt->execute();
    $themeRes = $themeStmt->get_result();
    $themeRow = $themeRes->fetch_assoc();
    $themeStmt->close();
    $conn->close();

    $theme = 'light'; // default
    if ($themeRow && isset($themeRow['theme'])) {
        $theme = $themeRow['theme'] ? 'dark' : 'light';
    }

    // Regenerate session ID to prevent session fixation attacks
    regenerate_session_on_login();
    $_SESSION['user_id'] = $userId;

    // Reset rate limiting on successful login
    reset_session_rate_limit();

    // Persist across restarts
    issue_remember_cookie($userId);

    echo json_encode(['ok' => true, 'theme' => $theme]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Login error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}