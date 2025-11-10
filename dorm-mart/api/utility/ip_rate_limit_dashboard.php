<?php
/**
 * IP Rate Limiting Dashboard
 * 
 * This script provides a comprehensive view of all IP-based rate limiting activity.
 * 
 * USAGE:
 * php api/utility/ip_rate_limit_dashboard.php
 */

require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../security/security.php';

echo "=== IP RATE LIMITING DASHBOARD ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n\n";

// Get all IPs with failed attempts
$conn = db();
$stmt = $conn->prepare('
    SELECT ip_address, failed_attempts, last_failed_attempt, lockout_until 
    FROM ip_login_attempts 
    WHERE failed_attempts > 0 OR last_failed_attempt IS NOT NULL
    ORDER BY last_failed_attempt DESC
');

// If prepare fails (table doesn't exist), show message
if (!$stmt) {
    echo "⚠️  IP rate limiting table does not exist. Run migration 012 to create it.\n";
    $conn->close();
    exit(1);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "✅ No IPs with failed login attempts\n";
    $stmt->close();
    $conn->close();
    exit(0);
}

echo "📊 IPs WITH FAILED ATTEMPTS:\n";
echo str_repeat("-", 80) . "\n";

while ($row = $result->fetch_assoc()) {
    $ipAddress = $row['ip_address'];
    $attempts = (int)$row['failed_attempts'];
    $lastAttempt = $row['last_failed_attempt'];
    $lockoutUntil = $row['lockout_until'];
    
    echo "🌐 IP: $ipAddress\n";
    echo "   Attempts: $attempts\n";
    echo "   Last Attempt: " . ($lastAttempt ?: 'Never') . "\n";
    
    if ($lastAttempt) {
        $timeSince = time() - strtotime($lastAttempt);
        echo "   Time Since: " . formatTime($timeSince) . "\n";
        echo "   Decay Cycles: " . floor($timeSince / 10) . "\n";
        echo "   Expected After Decay: " . max(0, $attempts - floor($timeSince / 10)) . "\n";
        echo "   🔄  Decay reduces attempts by 1 every 10 seconds\n";
    }
    
    // Check rate limiting status
    $rateLimitCheck = check_ip_rate_limit($ipAddress);
    if ($rateLimitCheck['blocked']) {
        $remainingMinutes = get_remaining_lockout_minutes($rateLimitCheck['lockout_until']);
        echo "   Status: 🔴 LOCKED OUT ($remainingMinutes minutes remaining)\n";
        if ($lockoutUntil) {
            echo "   Lockout Until: $lockoutUntil\n";
        }
    } else {
        echo "   Status: 🟢 NOT LOCKED OUT\n";
    }
    
    echo "\n";
}

$stmt->close();
$conn->close();

echo "💡 COMMANDS:\n";
echo "  php api/utility/monitor_ip_attempts.php [ip_address]  - Monitor specific IP\n";
echo "  php api/utility/reset_ip_lockouts.php                 - Reset all IP lockouts\n";
echo "  php api/utility/ip_rate_limit_dashboard.php           - Show this dashboard\n";

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

