<?php

// Set JSON response header early
header('Content-Type: application/json');

// __DIR__ points to api/
require __DIR__ . '/../../db_connect.php';

$conn = db();

$input = json_decode(file_get_contents('php://input'), true); // read raw request body and decode JSON
$year  = isset($input['year']) ? intval($input['year']) : null; // coerce to integer if provided

if ($year === null || $year < 2016 || $year > 2025) { // adjust bounds as needed
    http_response_code(400);                          // bad request
    echo json_encode([
        'success' => false,                           // preserve response shape
        'error'   => 'Invalid or missing year'
    ]);
    exit;                                             // stop execution on validation failure
}

$start = sprintf('%04d-01-01 00:00:00', $year);       // start of the year (inclusive)
$end   = sprintf('%04d-01-01 00:00:00', $year + 1);   // start of next year (exclusive)

$sql = "SELECT item_id, title, sold_by, transacted_at, image_url
        FROM purchased_items
        WHERE transacted_at >= ? AND transacted_at < ?
        ORDER BY transacted_at DESC";

$stmt = $conn->prepare($sql);                         // prepare statement to avoid SQL injection
if (!$stmt) {
    http_response_code(500);                          // server error if prepare fails
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query']);
    exit;
}

$stmt->bind_param('ss', $start, $end);                // bind date range params as strings
$stmt->execute();                                     // run the query
$res = $stmt->get_result();                           // fetch mysqli_result

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode(['success' => true, 'data' => $rows]);

