<?php

/**
 * Reset Session Lockout - Development Utility
 * 
 * This script clears the current session's rate limiting lockout.
 * Useful for development/testing when you've been locked out.
 * 
 * USAGE:
 * ======
 * 
 * Browser: Visit http://localhost:8080/api/utility/reset_session_lockout.php
 * CLI: php api/utility/reset_session_lockout.php
 */

// Include security headers
require_once __DIR__ . '/../security/security.php';
setSecurityHeaders();
setSecureCORS();

// Include auth handle to ensure session is started
require_once __DIR__ . '/../auth/auth_handle.php';
auth_boot_session();

// Set content type for both web and CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    // Clear session-based rate limiting data
    $_SESSION['login_failed_attempts'] = 0;
    $_SESSION['login_last_failed_attempt'] = null;
    $_SESSION['login_lockout_until'] = null;

    $response = [
        'success' => true,
        'message' => 'Session rate limiting lockout has been cleared!',
        'details' => [
            'session_id' => session_id(),
            'note' => 'You can now attempt login without rate limiting restrictions for this session.'
        ]
    ];

    if (php_sapi_name() === 'cli') {
        echo "SUCCESS: Session rate limiting lockout has been cleared!\n";
        echo "Session ID: " . session_id() . "\n";
        echo "You can now attempt login without restrictions.\n";
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    $errorResponse = [
        'success' => false,
        'error' => 'Failed to reset session lockout',
        'message' => $e->getMessage()
    ];

    if (php_sapi_name() === 'cli') {
        echo "ERROR: Failed to reset session lockout\n";
        echo "Details: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    }
}

