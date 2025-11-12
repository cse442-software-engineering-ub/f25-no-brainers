<?php

/**
 * Clear All Lockouts - Development Utility
 * 
 * This script clears ALL rate limiting lockouts for all sessions.
 * Useful for development/testing.
 * 
 * Rate limiting details:
 * - Maximum attempts before lockout: 5 failed attempts
 * - Lockout duration: 3 minutes
 * - Lockouts are stored in: api/utility/lockouts/locked_sessions.json
 * - Rate limiting is enforced entirely on the backend
 * 
 * USAGE:
 * ======
 * 
 * Browser: Visit http://localhost:8080/api/utility/reset_session_lockout.php
 * CLI: php api/utility/reset_session_lockout.php
 * 
 * NOTE: This clears ALL lockouts, not just your current session.
 */

// Include security headers
require_once __DIR__ . '/../security/security.php';
setSecurityHeaders();
setSecureCORS();

// Include auth handle to ensure session is started
require_once __DIR__ . '/../auth/auth_handle.php';
auth_boot_session();

// Set content type for both web and CLI
$isWebRequest = php_sapi_name() !== 'cli';
if ($isWebRequest) {
    // Output HTML for browser access to allow localStorage clearing
    header('Content-Type: text/html; charset=utf-8');
}

try {
    require_once __DIR__ . '/../security/security.php';
    $cleared = clear_all_lockouts();
    
    $_SESSION['login_failed_attempts'] = 0;
    $_SESSION['login_last_failed_attempt'] = null;

    if (php_sapi_name() === 'cli') {
        echo "SUCCESS: All rate limiting lockouts have been cleared!\n";
        echo "Session ID: " . session_id() . "\n";
        echo "Lockouts cleared: $cleared\n";
        echo "You can now attempt login without restrictions.\n";
    } else {
        // Output HTML page showing reset result
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset All Lockouts</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>All Lockouts Cleared</h1>
    <div class="success">
        <strong>✓ Success!</strong> All rate limiting lockouts have been cleared.
    </div>
    <div class="info">
        <strong>Your Session ID:</strong> <code><?php echo htmlspecialchars(session_id()); ?></code><br>
        <strong>Lockouts Cleared:</strong> <?php echo $cleared; ?><br>
        <strong>Status:</strong> All users can now attempt login without rate limiting restrictions.
    </div>
    <p><a href="/">← Back to Login</a></p>
</body>
</html>
        <?php
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "ERROR: Failed to clear lockouts\n";
        echo "Details: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Error</h1>
    <div class="error">
        <strong>✗ Failed to clear lockouts</strong><br>
        <?php echo htmlspecialchars($e->getMessage()); ?>
    </div>
    <p><a href="/">← Back to Login</a></p>
</body>
</html>
        <?php
    }
}

