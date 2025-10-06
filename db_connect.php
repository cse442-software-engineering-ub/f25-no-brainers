<?php
// Central DB connection file. Include this from PHP endpoints.
// NOTE: Do not echo or print anything here to avoid corrupting JSON responses.

$servername = "localhost";
$username   = "anishban";   
$password   = 50457553;           
$database   = "cse442_2025_fall_team_j_db";     

$conn = @new mysqli($servername, $username, $password, $database);
if ($conn && !$conn->connect_error) {
    // Ensure UTF-8 for consistent JSON encoding
    $conn->set_charset('utf8mb4');
} else {
    // Expose a null connection; callers can decide to fallback to mock data
    $conn = null;
}
?>
