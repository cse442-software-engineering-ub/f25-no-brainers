<?php
/**
 * Comprehensive Security Module
 * This file contains all security-related functions for the application
 * 
 * @author Team f25-no-brainers
 * @version 1.0
 */

// ============================================================================
// SECURITY HEADERS
// ============================================================================

/**
 * Set comprehensive security headers for all API endpoints
 * This function should be called at the start of every API endpoint
 */
function setSecurityHeaders()
{
    // Content Security Policy - Prevents XSS by controlling resource loading
    // Restricts which resources can be loaded and executed
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none';");

    // X-XSS-Protection - Enables browser's built-in XSS filter
    header("X-XSS-Protection: 1; mode=block");

    // X-Content-Type-Options - Prevents MIME type sniffing
    header("X-Content-Type-Options: nosniff");

    // X-Frame-Options - Prevents clickjacking
    header("X-Frame-Options: DENY");

    // Referrer Policy - Controls referrer information
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Permissions Policy - Controls browser features
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

    // Remove X-Powered-By header to hide PHP version
    header_remove('X-Powered-By');
}

// ============================================================================
// CORS CONFIGURATION
// ============================================================================

/**
 * Set secure CORS headers for trusted origins only
 * This prevents unauthorized cross-origin requests
 */
function setSecureCORS()
{
    // Skip CORS for CLI requests
    if (php_sapi_name() === 'cli') {
        return;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // SECURE CORS Configuration - Only allow specific trusted origins
    $allowedOrigins = [
        'http://localhost:3000',      // React dev server
        'http://localhost:8080',      // PHP dev server  
        'http://localhost',           // Apache local setup
        'https://aptitude.cse.buffalo.edu',  // Test server
        'https://cattle.cse.buffalo.edu'    // Production server
    ];

    // Check if this is a localhost request
    $isLocalhost = (
        $host === 'localhost' ||
        $host === 'localhost:8080' ||
        strpos($host, '127.0.0.1') === 0
    );

    // Check if this is a production server request
    $isProductionServer = (
        $host === 'aptitude.cse.buffalo.edu' ||
        $host === 'cattle.cse.buffalo.edu'
    );

    // Check if origin is explicitly allowed
    $isAllowedOrigin = in_array($origin, $allowedOrigins);

    // Set CORS headers based on the request type
    if ($isLocalhost) {
        // Localhost development - allow specific origins with credentials
        if ($isAllowedOrigin) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        } else {
            // Fallback for localhost requests without origin header
            header("Access-Control-Allow-Origin: http://localhost:3000");
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');
    } elseif ($isProductionServer) {
        // Production servers - allow same domain and specific origins
        if ($isAllowedOrigin) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // Allow same domain requests
            header("Access-Control-Allow-Origin: https://$host");
        }
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');
    } elseif ($isAllowedOrigin) {
        // Explicitly allowed origin
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');
    } else {
        // Reject requests from untrusted origins
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Origin not allowed']);
        exit;
    }
}

// ============================================================================
// INPUT SANITIZATION & VALIDATION
// ============================================================================

/**
 * Sanitize string input to prevent XSS attacks
 * @param string $input The input string to sanitize
 * @param int $maxLength Maximum allowed length (default: 1000)
 * @return string Sanitized string
 */
function sanitize_string($input, $maxLength = 1000)
{
    if (!is_string($input)) {
        return '';
    }

    // Trim whitespace
    $input = trim($input);

    // Limit length
    $input = substr($input, 0, $maxLength);

    // Remove null bytes
    $input = str_replace("\0", '', $input);

    // XSS PROTECTION: HTML encode special characters to prevent XSS attacks
    $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return $input;
}

/**
 * Sanitize email input
 * @param string $email Email to sanitize
 * @return string Sanitized email
 */
function sanitize_email($email)
{
    if (!is_string($email)) {
        return '';
    }

    // Convert to lowercase and trim
    $email = strtolower(trim($email));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }

    // Additional sanitization
    $email = sanitize_string($email, 254); // RFC 5321 limit

    return $email;
}

/**
 * Sanitize JSON input
 * @param string $json JSON string to sanitize
 * @return array|false Sanitized array or false if invalid
 */
function sanitize_json($json)
{
    if (!is_string($json)) {
        return false;
    }

    // Decode JSON
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    // Recursively sanitize all string values
    $data = sanitize_array($data);

    return $data;
}

/**
 * Recursively sanitize array values
 * @param array $data Array to sanitize
 * @return array Sanitized array
 */
function sanitize_array($data)
{
    if (!is_array($data)) {
        return (array) sanitize_string($data);
    }

    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitizedKey = sanitize_string($key, 100);
        $sanitizedValue = is_array($value) ? sanitize_array($value) : sanitize_string($value);
        $sanitized[$sanitizedKey] = $sanitizedValue;
    }

    return $sanitized;
}

/**
 * Sanitize number input with min/max validation
 * @param mixed $input The input to sanitize
 * @param int $min Minimum allowed value
 * @param int $max Maximum allowed value
 * @return int Sanitized number
 */
function sanitize_number($input, $min = 0, $max = PHP_INT_MAX)
{
    $number = (int) $input;
    return max($min, min($max, $number));
}

// ============================================================================
// ACCESS CONTROL
// ============================================================================

/**
 * Validate user access to prevent IDOR attacks
 * @param int $requestedUserId The user ID being requested
 * @param int $loggedInUserId The currently logged in user ID
 */
function validateUserAccess($requestedUserId, $loggedInUserId)
{
    // IDOR Protection - Ensure user can only access their own data
    if ($requestedUserId != $loggedInUserId) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Permission denied - cannot access other user data']);
        exit;
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Escape HTML output to prevent XSS
 * @param string $str String to escape
 * @return string Escaped string
 */
function escapeHtml($str)
{
    // XSS PROTECTION: HTML encode output to prevent XSS attacks
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Escape JSON output to prevent XSS
 * @param string $str String to escape
 * @return string Escaped JSON string
 */
function escapeJson($str)
{
    // XSS PROTECTION: JSON encode with hex encoding to prevent XSS attacks
    return json_encode($str ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Validate input with custom rules
 * @param string $input Input to validate
 * @param int $maxLength Maximum length allowed
 * @param string|null $allowedChars Regex pattern for allowed characters
 * @return string|false Validated input or false if invalid
 */
function validateInput($input, $maxLength = 255, $allowedChars = null)
{
    $input = trim($input);
    if (strlen($input) > $maxLength) {
        return false;
    }
    if ($allowedChars && !preg_match($allowedChars, $input)) {
        return false;
    }
    return $input;
}

/**
 * Check if input contains XSS attack patterns
 * @param string $input Input to check
 * @return bool True if XSS pattern detected
 */
function containsXSSPattern($input)
{
    if (!is_string($input)) {
        return false;
    }

    $xssPatterns = [
        '/<script/i',
        '/javascript:/i',
        '/onerror=/i',
        '/onload=/i',
        '/onclick=/i',
        '/onmouseover=/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/<img[^>]*on/i',
        '/<svg[^>]*on/i',
        '/expression\s*\(/i',
        '/vbscript:/i'
    ];

    foreach ($xssPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }

    return false;
}

/**
 * Check if input contains SQL injection attack patterns
 * @param string $input Input to check
 * @return bool True if SQL injection pattern detected
 */
function containsSQLInjectionPattern($input)
{
    if (!is_string($input)) {
        return false;
    }

    $sqlPatterns = [
        '/;\s*(DROP|DELETE|INSERT|UPDATE|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE)/i',
        '/\'\s*;\s*--/',
        '/\/\*/',
        '/UNION\s+SELECT/i',
        '/OR\s+1\s*=\s*1/i',
        '/OR\s+\'1\'\s*=\s*\'1\'/i',
        '/\'\s+OR\s+\'\'/i',
        '/\'\s+OR\s+1\s*=/i'
    ];

    foreach ($sqlPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }

    return false;
}

// ============================================================================
// RATE LIMITING FUNCTIONS
// ============================================================================

/**
 * Get remaining lockout minutes
 * @param string $lockoutUntil Lockout end time
 * @return int Remaining minutes
 */
function get_remaining_lockout_minutes($lockoutUntil)
{
    if (empty($lockoutUntil)) {
        return 0;
    }

    try {
        // Handle both integer timestamp and datetime string formats
        $lockoutTime = null;
        if (is_numeric($lockoutUntil)) {
            $lockoutTime = (int) $lockoutUntil;
        } else {
            $lockoutTime = strtotime($lockoutUntil);
            if ($lockoutTime === false) {
                return 0;
            }
        }

        $currentTime = time();
        $remainingSeconds = $lockoutTime - $currentTime;
        
        if ($remainingSeconds <= 0) {
            return 0;
        }

        return max(0, ceil($remainingSeconds / 60));
    } catch (Exception $e) {
        // If calculation fails, return 0 to avoid breaking the login flow
        return 0;
    }
}

/**
 * Reset all lockouts (admin function)
 * NOTE: With session-based rate limiting, lockouts are stored in individual sessions.
 * This function is deprecated - users can clear their own lockouts by clearing cookies.
 * Kept for API compatibility but does nothing.
 */
function reset_all_lockouts()
{
    // Session-based rate limiting: lockouts are per-session, cannot be reset globally
    // Users can clear their own lockouts by clearing cookies
    return;
}

/**
 * Get lockout file path for storing locked-out session IDs
 * @return string Path to lockout file
 */
function get_lockout_file_path()
{
    $lockoutDir = __DIR__ . '/../utility/lockouts';
    if (!is_dir($lockoutDir)) {
        mkdir($lockoutDir, 0755, true);
    }
    return $lockoutDir . '/locked_sessions.json';
}

/**
 * Check if a session ID is currently locked out
 * @param string $sessionId Session ID to check
 * @return array|null Returns lockout info if locked out, null otherwise
 */
function check_session_lockout_file($sessionId)
{
    $lockoutFile = get_lockout_file_path();
    
    if (!file_exists($lockoutFile)) {
        return null;
    }
    
    $lockouts = json_decode(file_get_contents($lockoutFile), true) ?? [];
    $currentTime = time();
    $updatedLockouts = [];
    $isLocked = false;
    $lockoutUntil = null;
    
    foreach ($lockouts as $lockedSessionId => $expiryTime) {
        if ($expiryTime > $currentTime) {
            $updatedLockouts[$lockedSessionId] = $expiryTime;
            if ($lockedSessionId === $sessionId) {
                $isLocked = true;
                $lockoutUntil = date('Y-m-d H:i:s', $expiryTime);
            }
        }
    }
    
    if (count($updatedLockouts) !== count($lockouts)) {
        file_put_contents($lockoutFile, json_encode($updatedLockouts, JSON_PRETTY_PRINT));
    }
    
    return $isLocked ? ['blocked' => true, 'lockout_until' => $lockoutUntil] : null;
}

/**
 * Store a session ID as locked out
 * @param string $sessionId Session ID to lock out
 * @param int $lockoutMinutes Lockout duration in minutes
 */
function store_session_lockout($sessionId, $lockoutMinutes = 3)
{
    $lockoutFile = get_lockout_file_path();
    $currentTime = time();
    $lockouts = [];
    
    if (file_exists($lockoutFile)) {
        $lockouts = json_decode(file_get_contents($lockoutFile), true) ?? [];
        
        // Auto-cleanup expired lockouts
        foreach ($lockouts as $sid => $expiryTime) {
            if ($expiryTime <= $currentTime) {
                unset($lockouts[$sid]);
            }
        }
    }
    
    $lockouts[$sessionId] = $currentTime + ($lockoutMinutes * 60);
    file_put_contents($lockoutFile, json_encode($lockouts, JSON_PRETTY_PRINT));
}

/**
 * Check if session has exceeded rate limit
 * @param int $maxAttempts Maximum attempts before lockout (default: 5)
 * @param int $lockoutMinutes Lockout duration in minutes (default: 3)
 * @return array Rate limit status
 */
function check_session_rate_limit($maxAttempts = 5, $lockoutMinutes = 3)
{
    // Ensure session is started
    require_once __DIR__ . '/../auth/auth_handle.php';
    auth_boot_session();

    $sessionId = $_COOKIE[session_name()] ?? session_id();
    
    // Check lockout file first (source of truth)
    $fileLockout = check_session_lockout_file($sessionId);
    if ($fileLockout && $fileLockout['blocked']) {
        return $fileLockout;
    }

    // Initialize session variables
    if (!isset($_SESSION['login_failed_attempts'])) {
        $_SESSION['login_failed_attempts'] = 0;
    }
    if (!isset($_SESSION['login_last_failed_attempt'])) {
        $_SESSION['login_last_failed_attempt'] = null;
    }
    
    $attempts = (int) $_SESSION['login_failed_attempts'];
    $lastAttempt = $_SESSION['login_last_failed_attempt'];
    
    // If session has high attempts but no file lockout, lockouts were manually cleared - reset
    if ($attempts >= $maxAttempts && !$fileLockout) {
        $_SESSION['login_failed_attempts'] = 0;
        $_SESSION['login_last_failed_attempt'] = null;
        return ['blocked' => false, 'attempts' => 0, 'lockout_until' => null];
    }
    
    if ($attempts === 0) {
        return ['blocked' => false, 'attempts' => 0, 'lockout_until' => null];
    }

    // Apply decay: reduce attempts by 1 if 10+ seconds have passed
    $currentTime = time();
    $timeSinceLastAttempt = $currentTime - ($lastAttempt ? (int) $lastAttempt : 0);

    if ($timeSinceLastAttempt >= 10 && $attempts > 0) {
        $attempts = max(0, $attempts - 1);
        $_SESSION['login_failed_attempts'] = $attempts;
    }

    // Failsafe: if at threshold, enforce lockout
    if ($attempts >= $maxAttempts) {
        $lockoutExpiry = $currentTime + ($lockoutMinutes * 60);
        $lockoutUntilStr = date('Y-m-d H:i:s', $lockoutExpiry);
        store_session_lockout($sessionId, $lockoutMinutes);
        return ['blocked' => true, 'attempts' => $attempts, 'lockout_until' => $lockoutUntilStr];
    }

    return ['blocked' => false, 'attempts' => $attempts, 'lockout_until' => null];
}

/**
 * Record a failed login attempt for session-based rate limiting
 * @return array|null Returns lockout info if lockout should be triggered, null otherwise
 */
function record_session_failed_attempt()
{
    require_once __DIR__ . '/../auth/auth_handle.php';
    auth_boot_session();

    if (!isset($_SESSION['login_failed_attempts'])) {
        $_SESSION['login_failed_attempts'] = 0;
    }
    if (!isset($_SESSION['login_last_failed_attempt'])) {
        $_SESSION['login_last_failed_attempt'] = null;
    }

    $newAttempts = (int) $_SESSION['login_failed_attempts'] + 1;
    $_SESSION['login_failed_attempts'] = $newAttempts;
    $_SESSION['login_last_failed_attempt'] = time();

    // Enforce lockout after 5 attempts
    if ($newAttempts >= 5) {
        $sessionId = $_COOKIE[session_name()] ?? session_id();
        store_session_lockout($sessionId, 3);
        
        $lockoutExpiry = time() + (3 * 60);
        $lockoutUntilStr = date('Y-m-d H:i:s', $lockoutExpiry);
        return ['blocked' => true, 'attempts' => $newAttempts, 'lockout_until' => $lockoutUntilStr];
    }
    
    return null;
}

/**
 * Reset session rate limiting (call on successful login)
 */
function reset_session_rate_limit()
{
    require_once __DIR__ . '/../auth/auth_handle.php';
    auth_boot_session();

    $_SESSION['login_failed_attempts'] = 0;
    $_SESSION['login_last_failed_attempt'] = null;
    
    $sessionId = $_COOKIE[session_name()] ?? session_id();
    $lockoutFile = get_lockout_file_path();
    
    if (file_exists($lockoutFile)) {
        $lockouts = json_decode(file_get_contents($lockoutFile), true) ?? [];
        if (isset($lockouts[$sessionId])) {
            unset($lockouts[$sessionId]);
            file_put_contents($lockoutFile, json_encode($lockouts, JSON_PRETTY_PRINT));
        }
    }
}

/**
 * Clear all lockouts (admin utility function)
 * @return int Number of lockouts cleared
 */
function clear_all_lockouts()
{
    $lockoutFile = get_lockout_file_path();
    if (file_exists($lockoutFile)) {
        $lockouts = json_decode(file_get_contents($lockoutFile), true) ?? [];
        $count = count($lockouts);
        file_put_contents($lockoutFile, json_encode([], JSON_PRETTY_PRINT));
        return $count;
    }
    return 0;
}

// ============================================================================
// PASSWORD SECURITY
// ============================================================================

/**
 * Hash password securely using bcrypt
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password)
{
    require_once __DIR__ . '/../utility/hash_password.php';
    return password_hash($password, PASSWORD_BCRYPT);
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize security for API endpoints
 * Call this function at the start of every API endpoint
 */
function initSecurity()
{
    setSecurityHeaders();
    setSecureCORS();
}

?>