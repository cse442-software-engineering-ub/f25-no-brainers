<?php
// CSRF Token Endpoint (using existing session system)
require_once __DIR__ . '/utility/security.php';
require_once __DIR__ . '/auth_handle.php';

header('Content-Type: application/json; charset=utf-8');
setSecurityHeaders();

// Generate and return CSRF token using existing session system
$token = generate_csrf_token();

echo json_encode([
    'ok' => true,
    'csrf_token' => $token
]);
?>

/** 
EXAMPLE OF CSRF TOKEN GENERATION AND VALIDATION
*This pretty much prevents another website from making requests to our backend

 
// In auth_handle.php
function generate_csrf_token(): string {
    auth_boot_session();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// In auth_handle.php
function validate_csrf_token(string $token): bool {
    auth_boot_session();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// In login.php, create_account.php, etc.
require_csrf_token(); // Validates token before processing request

*/