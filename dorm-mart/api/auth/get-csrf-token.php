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
