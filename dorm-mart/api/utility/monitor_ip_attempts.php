<?php
/**
 * IP Rate Limiting Monitor
 * 
 * This script allows you to monitor IP login attempts, decay events, and rate limiting status.
 * 
 * USAGE:
 * php api/utility/monitor_ip_attempts.php [ip_address]
 * 
 * Examples:
 * php api/utility/monitor_ip_attempts.php 192.168.1.1
 * php api/utility/monitor_ip_attempts.php ::1
 */

require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../security/security.php';

// Get IP address from command line argument
$ipAddress = $argv[1] ?? '';

if (empty($ipAddress)) {
    echo "Usage: php api/utility/monitor_ip_attempts.php [ip_address]\n";
    echo "Example: php api/utility/monitor_ip_attempts.php 192.168.1.1\n";
    exit(1);
}

// Validate IP address format
if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
    echo "Error: Invalid IP address format\n";
    exit(1);
}

echo "=== IP RATE LIMITING MONITOR ===\n";
echo "IP Address: $ipAddress\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// Get current IP status
$conn = db();
$stmt = $conn->prepare('SELECT failed_attempts, last_failed_attempt, lockout_until FROM ip_login_attempts WHERE ip_address = ?');

// If prepare fails (table doesn't exist), show message
if (!$stmt) {
    echo "⚠️  IP rate limiting table does not exist. Run migration 012 to create it.\n";
    $conn->close();
    exit(1);
}

$stmt->bind_param('s', $ipAddress);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "✅ No failed attempts recorded for this IP\n";
    echo "   Status: 🟢 NOT LOCKED OUT\n";
    $stmt->close();
    $conn->close();
    exit(0);
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

$attempts = (int)$row['failed_attempts'];
$lastAttempt = $row['last_failed_attempt'];
$lockoutUntil = $row['lockout_until'];

echo "📊 CURRENT STATUS:\n";
echo "  Failed Attempts: $attempts\n";
echo "  Last Attempt: " . ($lastAttempt ?: 'Never') . "\n";
if ($lockoutUntil) {
    echo "  Lockout Until: $lockoutUntil\n";
}

if ($lastAttempt) {
    $timeSince = time() - strtotime($lastAttempt);
    echo "  Time Since Last Attempt: " . formatTime($timeSince) . "\n";
    echo "  Decay Cycles (every 10s): " . floor($timeSince / 10) . "\n";
    echo "  Expected Attempts After Decay: " . max(0, $attempts - floor($timeSince / 10)) . "\n";
    echo "  🔄  Decay reduces attempts by 1 every 10 seconds\n";
}

echo "\n";

// Check rate limiting status
echo "🔒 RATE LIMITING STATUS:\n";
$rateLimitCheck = check_ip_rate_limit($ipAddress);
if ($rateLimitCheck['blocked']) {
    $remainingMinutes = get_remaining_lockout_minutes($rateLimitCheck['lockout_until']);
    echo "  Status: 🔴 LOCKED OUT\n";
    echo "  Remaining Time: $remainingMinutes minutes\n";
    if ($rateLimitCheck['lockout_until']) {
        echo "  Lockout Until: " . $rateLimitCheck['lockout_until'] . "\n";
    }
} else {
    echo "  Status: 🟢 NOT LOCKED OUT\n";
    echo "  Current Attempts: " . $rateLimitCheck['attempts'] . "\n";
}

echo "\n";

// Show decay simulation
if ($attempts > 0 && $lastAttempt) {
    echo "⏰ DECAY SIMULATION:\n";
    $currentTime = time();
    $lastAttemptTime = strtotime($lastAttempt);
    $timeSince = $currentTime - $lastAttemptTime;
    
    echo "  Current Time: " . date('Y-m-d H:i:s', $currentTime) . "\n";
    echo "  Last Attempt: " . date('Y-m-d H:i:s', $lastAttemptTime) . "\n";
    echo "  Time Elapsed: " . formatTime($timeSince) . "\n";
    
    $decayCycles = floor($timeSince / 10);
    $expectedAttempts = max(0, $attempts - $decayCycles);
    
    echo "  Decay Cycles: $decayCycles (every 10 seconds)\n";
    echo "  Original Attempts: $attempts\n";
    echo "  Expected After Decay: $expectedAttempts\n";
    
    if ($expectedAttempts < $attempts) {
        echo "  🎯 DECAY WOULD REDUCE ATTEMPTS BY: " . ($attempts - $expectedAttempts) . "\n";
    }
    
    // Show when decay would complete
    if ($attempts > 0) {
        $secondsToComplete = ($attempts * 10) - $timeSince;
        if ($secondsToComplete > 0) {
            echo "  ⏳ Time Until All Decay: " . formatTime($secondsToComplete) . "\n";
        } else {
            echo "  ✅ All decay would be complete!\n";
        }
    }
}

echo "\n";

// Show lockout expiry
if ($lockoutUntil) {
    $lockoutExpiry = strtotime($lockoutUntil);
    $currentTime = time();
    $remainingLockout = $lockoutExpiry - $currentTime;
    
    echo "🚫 LOCKOUT STATUS:\n";
    if ($lastAttempt) {
        echo "  Lockout Started: " . date('Y-m-d H:i:s', strtotime($lastAttempt)) . "\n";
    }
    echo "  Lockout Expires: " . date('Y-m-d H:i:s', $lockoutExpiry) . "\n";
    
    if ($remainingLockout > 0) {
        echo "  Remaining Lockout: " . formatTime($remainingLockout) . "\n";
    } else {
        echo "  ✅ Lockout has expired!\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "💡 TIP: Run this script again to see updated status after decay\n";

/**
 * Format seconds into human-readable time
 */
function formatTime($seconds) {
    if ($seconds < 60) {
        return "$seconds seconds";
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return "$minutes minutes, $remainingSeconds seconds";
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "$hours hours, $minutes minutes";
    }
}
?>

