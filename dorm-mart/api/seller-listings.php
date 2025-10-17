<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include security utilities
require_once __DIR__ . '/auth/utility/security.php';
setSecurityHeaders();

require __DIR__ . '/auth/auth_handle.php';
require __DIR__ . '/db_connect.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// CSRF Protection
require_csrf_token();

try {
    // Require authentication - this will redirect to login if not authenticated
    $userId = require_login();
    
    // For now, return test data with proper structure
    // TODO: Replace with real database query in Hotfix 3
    $data = [
        [
            'id' => 1, 
            'title' => escapeHtml('Test Item'), 
            'price' => 25.00,
            'status' => escapeHtml('active'), 
            'buyer_user_id' => null,
            'seller_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'image_url' => null
        ],
        [
            'id' => 2, 
            'title' => escapeHtml('Sold Item'), 
            'price' => 50.00,
            'status' => escapeHtml('sold'), 
            'buyer_user_id' => 2,
            'seller_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'image_url' => null
        ]
    ];
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Throwable $e) {
    // Log error server-side (in production, use proper logging)
    error_log('Seller listings error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
