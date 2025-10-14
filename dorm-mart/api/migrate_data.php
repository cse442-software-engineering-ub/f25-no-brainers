<?php
header('Content-Type: application/json');                      // Return JSON to the client

require __DIR__ . '/db_connect.php';                            // Load your connection helper
$conn = db();                                                   // Get a mysqli connection

// Create (if missing) a table to record runs of each SQL file by filename
$conn->query("
  CREATE TABLE IF NOT EXISTS data_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// Collect all .sql files from ../data and sort them naturally (e.g., 1,2,10)
$dir = dirname(__DIR__) . '/data';                              // Path to the data folder
$files = glob($dir . '/*.sql');                                 // List all .sql files
natsort($files);                                                // Sort numerically by names like 001, 010, etc.

$ran = [];                                                      // Keep track of executed filenames

// Run every file, regardless of past executions
foreach ($files as $path) {
  $name = basename($path);                                      // Extract filename only
  $sql  = file_get_contents($path);                             // Read the SQL script contents

  $conn->begin_transaction();                                   // Start an atomic transaction

  if (!$conn->multi_query($sql)) {                              // Execute possibly multi-statement SQL
    $err = $conn->error;                                        // Capture the MySQL error message
    $conn->rollback();                                          // Undo any partial changes
    echo json_encode(["success" => false,                       // Report failure (which file + why)
                      "message" => "Failed: $name — $err"]);
    exit;                                                       // Stop on first failure
  }

  // Flush all result sets produced by multi_query to clear the connection for next use
  while ($conn->more_results() && $conn->next_result()) { /* flush */ }

  // Record that we ran this file; if it exists, just bump the timestamp
  $stmt = $conn->prepare(                                       // Use the tracking table we created above
    "INSERT INTO data_migrations (filename) VALUES (?)
     ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP"
  );
  $stmt->bind_param("s", $name);                                // Bind the filename as string
  $stmt->execute();                                             // Insert or update the timestamp
  $stmt->close();                                               // Free the statement

  $conn->commit();                                              // Finalize this migration’s transaction
  $ran[] = $name;                                               // Add to the list of executed files
}

echo json_encode(["success" => true, "applied" => $ran]);        // Return summary of executed files
