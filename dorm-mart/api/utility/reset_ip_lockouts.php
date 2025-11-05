<?php

// Include security utilities
require_once __DIR__ . '/../security/security.php';
setSecurityHeaders();
setSecureCORS();

/**
 * Reset All IP-Based Lockouts - Development Utility
 * 
 * This script resets all active IP-based rate limiting lockouts for local development.
 * Run this script whenever you need to clear IP lockouts during testing.
 * 
 * COMMAND LINE USAGE:
 * ===================
 * 
 * Reset all IP lockouts (command line):
 *   php api/utility/reset_ip_lockouts.php
 * 
 * EXAMPLES:
 * =========
 * php api/utility/reset_ip_lockouts.php
 * 
 * WEB BROWSER USAGE:
 * ==================
 * 
 * 1. NPM START METHOD (React Dev Server):
 *    - Start React dev server: npm start
 *    - Start PHP server: C:\xampp\php\php.exe -S localhost:8080 -t .
 *    - Open browser: http://localhost:3000/api/utility/reset_ip_lockouts.php
 * 
 * 2. NPM BUILD METHOD (Production Build):
 *    - Build React app: npm run build
 *    - Start PHP server: C:\xampp\php\php.exe -S localhost:8080 -t .
 *    - Open browser: http://localhost:8080/api/utility/reset_ip_lockouts.php
 * 
 * NOTES:
 * ======
 * - This script resets failed_attempts to 0, clears last_failed_attempt, and clears lockout_until
 * - All IPs can then attempt login without rate limiting restrictions
 * - Use this during development/testing to reset IP rate limits
 * - Works both from command line and web browser
 */

// Include database connection
require_once __DIR__ . '/../database/db_connect.php';

// Set content type for both web and CLI
// Handles both browser requests and command line execution
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    $conn = db();

    // Reset all IP-based failed login attempts and lockouts
    $stmt = $conn->prepare('UPDATE ip_login_attempts SET failed_attempts = 0, last_failed_attempt = NULL, lockout_until = NULL');
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    // Get current database time for confirmation
    $result = $conn->query("SELECT NOW() as db_time");
    $row = $result->fetch_assoc();
    $currentTime = $row['db_time'];

    $conn->close();

    $response = [
        'success' => true,
        'message' => "All IP-based rate limiting lockouts have been reset!",
        'details' => [
            'affected_ips' => $affectedRows,
            'reset_time' => $currentTime,
            'note' => 'All IPs can now attempt login without rate limiting restrictions.'
        ]
    ];

    if (php_sapi_name() === 'cli') {
        echo "SUCCESS: All IP-based rate limiting lockouts have been reset!\n";
        echo "Affected IPs: $affectedRows\n";
        echo "Reset time: $currentTime\n";
        echo "All IPs can now attempt login without restrictions.\n";
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    $errorResponse = [
        'success' => false,
        'error' => 'Failed to reset IP lockouts',
        'message' => $e->getMessage()
    ];

    if (php_sapi_name() === 'cli') {
        echo "ERROR: Failed to reset IP lockouts\n";
        echo "Details: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    }
}

