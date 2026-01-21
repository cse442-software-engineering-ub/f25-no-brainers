<?php

/**
 * CORS Configuration Module
 * 
 * Sets secure CORS headers for trusted origins only
 */

/**
 * Extract base origin (scheme + host + port) from a full URL
 * 
 * This function dynamically extracts the base origin from any URL format.
 * Works with any domain, port, or path combination - fully dynamic.
 * 
 * @param string $url Full URL (e.g., "http://localhost/serve/dorm-mart" or "https://example.com:8080/path")
 * @return string Base origin (e.g., "http://localhost" or "https://example.com:8080")
 */
function extract_base_origin(string $url): string {
    // If URL is empty, return empty string
    if (empty($url)) {
        return '';
    }
    
    // Parse the URL
    $parsed = parse_url($url);
    
    // If parsing fails, return original URL (shouldn't happen with valid URLs)
    if ($parsed === false) {
        return $url;
    }
    
    // Extract scheme (default to http if not specified)
    $scheme = $parsed['scheme'] ?? 'http';
    
    // Extract host (required)
    $host = $parsed['host'] ?? '';
    
    // If no host, try to extract from path (for URLs like "localhost/path")
    if (empty($host) && isset($parsed['path'])) {
        $parts = explode('/', $parsed['path'], 2);
        $host = $parts[0];
    }
    
    // If still no host, return empty string (invalid URL)
    if (empty($host)) {
        return '';
    }
    
    // Build base origin with scheme and host
    $baseOrigin = $scheme . '://' . $host;
    
    // Add port only if explicitly specified and not a standard port
    if (isset($parsed['port'])) {
        $port = (int)$parsed['port'];
        // Only add port if it's not a standard port (80 for http, 443 for https)
        if (!(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $baseOrigin .= ':' . $port;
        }
    }
    
    return $baseOrigin;
}

/**
 * Set secure CORS headers for trusted origins only
 * This prevents unauthorized cross-origin requests
 * 
 * Fully dynamic - all allowed origins are derived from .env configuration.
 * Automatically extracts base origins to handle subpath scenarios.
 */
function setSecureCORS() {
    // Skip CORS for CLI requests
    if (php_sapi_name() === 'cli') {
        return;
    }
    
    // Load environment configuration if not already loaded
    if (!function_exists('get_cors_allowed_origins')) {
        require_once __DIR__ . '/../utility/env_config.php';
    }
    
    // #region agent log
    $logFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.cursor' . DIRECTORY_SEPARATOR . 'debug.log';
    @mkdir(dirname($logFile), 0777, true); // Ensure directory exists
    $logData = [
        'timestamp' => time() * 1000,
        'sessionId' => 'debug-session',
        'runId' => 'cors-debug',
        'hypothesisId' => 'A',
        'location' => 'cors.php:setSecureCORS',
        'message' => 'CORS check start',
        'data' => [
            'http_origin' => $_SERVER['HTTP_ORIGIN'] ?? '(empty)',
            'http_host' => $_SERVER['HTTP_HOST'] ?? '(empty)',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '(empty)',
        ]
    ];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    // #endregion
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    // Normalize origin: trim and remove trailing slash
    $origin = rtrim(trim($origin), '/');
    
    // Get allowed origins from environment configuration (fully dynamic from .env)
    $allowedOrigins = get_cors_allowed_origins();
    
    // Build combined list of allowed origins (exact matches + base origins)
    // This allows both full URLs and base domain origins
    $combinedAllowedOrigins = [];
    
    // Add exact matches from configuration
    foreach ($allowedOrigins as $allowedOrigin) {
        // Normalize: trim and remove trailing slash
        $normalizedAllowed = rtrim(trim($allowedOrigin), '/');
        $combinedAllowedOrigins[] = $normalizedAllowed;
        
        // Extract base origin (scheme + host + port) from each allowed origin
        $baseOrigin = extract_base_origin($normalizedAllowed);
        
        // Add base origin if it's different from the exact match
        // (prevents duplicates when base origin equals exact origin)
        if (!empty($baseOrigin) && $baseOrigin !== $normalizedAllowed) {
            $combinedAllowedOrigins[] = $baseOrigin;
        }
    }
    
    // Remove duplicates while preserving order
    $combinedAllowedOrigins = array_values(array_unique($combinedAllowedOrigins));
    
    // #region agent log
    $logData = [
        'timestamp' => time() * 1000,
        'sessionId' => 'debug-session',
        'runId' => 'cors-debug',
        'hypothesisId' => 'B',
        'location' => 'cors.php:setSecureCORS',
        'message' => 'CORS origins built',
        'data' => [
            'normalized_origin' => $origin ?: '(empty)',
            'allowed_origins' => $allowedOrigins,
            'combined_allowed_origins' => $combinedAllowedOrigins,
        ]
    ];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    // #endregion
    
    // If origin is empty, this is likely a same-origin request (browsers don't send Origin header for same-origin)
    // Check if the request appears to be from a same-origin by comparing the request host with allowed origins
    if (empty($origin)) {
        $requestHost = $_SERVER['HTTP_HOST'] ?? '';
        $requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        
        // Build the request origin from the current request
        if (!empty($requestHost)) {
            // Parse host to handle port correctly (remove standard ports)
            $hostParts = explode(':', $requestHost, 2);
            $host = $hostParts[0];
            $port = isset($hostParts[1]) ? (int)$hostParts[1] : null;
            
            $requestOrigin = $requestScheme . '://' . $host;
            // Only add port if it's explicitly set and not a standard port
            if ($port !== null && !(($requestScheme === 'http' && $port === 80) || ($requestScheme === 'https' && $port === 443))) {
                $requestOrigin .= ':' . $port;
            }
            
            // Check if this request origin matches any allowed base origin
            // #region agent log
            $logData = [
                'timestamp' => time() * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'cors-debug',
                'hypothesisId' => 'C',
                'location' => 'cors.php:setSecureCORS',
                'message' => 'Checking same-origin match',
                'data' => [
                    'request_origin' => $requestOrigin,
                    'combined_allowed_origins' => $combinedAllowedOrigins,
                    'is_match' => in_array($requestOrigin, $combinedAllowedOrigins),
                ]
            ];
            @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            
            if (in_array($requestOrigin, $combinedAllowedOrigins)) {
                // Same-origin request from an allowed origin - allow it (no CORS headers needed)
                return;
            }
        }
        
        // Empty origin but doesn't match allowed origins - reject for security
        // #region agent log
        $logData = [
            'timestamp' => time() * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'cors-debug',
            'hypothesisId' => 'D',
            'location' => 'cors.php:setSecureCORS',
            'message' => 'Empty origin rejected',
            'data' => [
                'request_host' => $requestHost ?? '(empty)',
                'request_origin' => $requestOrigin ?? '(not built)',
            ]
        ];
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);
        // #endregion
        
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Origin not allowed']);
        exit;
    }
    
    // Check if origin is explicitly allowed (exact match or base origin match)
    $isAllowedOrigin = in_array($origin, $combinedAllowedOrigins);
    
    // #region agent log
    $logData = [
        'timestamp' => time() * 1000,
        'sessionId' => 'debug-session',
        'runId' => 'cors-debug',
        'hypothesisId' => 'E',
        'location' => 'cors.php:setSecureCORS',
        'message' => 'Checking origin match',
        'data' => [
            'origin' => $origin,
            'is_allowed' => $isAllowedOrigin,
            'combined_allowed_origins' => $combinedAllowedOrigins,
        ]
    ];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    // #endregion
    
    // Set CORS headers based on the request type
    if ($isAllowedOrigin) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');
    } else {
        // Reject requests from untrusted origins
        // #region agent log
        $logData = [
            'timestamp' => time() * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'cors-debug',
            'hypothesisId' => 'F',
            'location' => 'cors.php:setSecureCORS',
            'message' => 'Origin rejected',
            'data' => [
                'origin' => $origin,
                'combined_allowed_origins' => $combinedAllowedOrigins,
            ]
        ];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Origin not allowed']);
        exit;
    }
}

