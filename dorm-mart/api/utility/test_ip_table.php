<?php
/**
 * Quick test to check if ip_login_attempts table exists and is accessible
 */

require_once __DIR__ . '/../database/db_connect.php';

header('Content-Type: application/json');

try {
    $conn = db();
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'ip_login_attempts'");
    $tableExists = $result->num_rows > 0;
    
    if ($tableExists) {
        // Try to query the table
        $testResult = $conn->query("SELECT COUNT(*) as count FROM ip_login_attempts");
        $row = $testResult->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'table_exists' => true,
            'record_count' => (int)$row['count'],
            'message' => 'Table exists and is accessible'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'table_exists' => false,
            'message' => 'Table does NOT exist. Please run: php api/database/migrate_schema.php'
        ]);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

