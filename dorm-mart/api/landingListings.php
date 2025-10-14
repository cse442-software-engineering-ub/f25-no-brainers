<?php
header('Content-Type: application/json');

// Anonymous access acceptable for landing listings (no auth required)
require_once __DIR__ . '/db_connect.php';

try {
  $conn = db();
  // Basic query: grab a few latest active listings if table exists.
  // NOTE: Adjust table/column names to match actual schema when available.
  $sql = null;
  $tableCheck = $conn->query("SHOW TABLES LIKE 'product_listings'");
  if ($tableCheck && $tableCheck->num_rows > 0) {
    $sql = "SELECT id, title, price, image_url AS image, seller_name AS seller, rating, location, created_at, status, tags FROM product_listings WHERE (status IS NULL OR status <> 'sold') ORDER BY created_at DESC LIMIT 9";
  } else {
    // Attempt alternative naming patterns
    $tableCheck2 = $conn->query("SHOW TABLES LIKE 'items'");
    if ($tableCheck2 && $tableCheck2->num_rows > 0) {
      $sql = "SELECT id, name AS title, price, image AS image, seller AS seller, rating, location, created_at, status, tags FROM items ORDER BY created_at DESC LIMIT 9";
    }
  }

  $rows = [];
  if ($sql) {
    if ($res = $conn->query($sql)) {
      while ($row = $res->fetch_assoc()) {
        // Normalize minimal fields
        $rows[] = [
          'id' => (int)$row['id'],
          'title' => $row['title'] ?? ($row['name'] ?? 'Untitled'),
          'price' => is_numeric($row['price'] ?? null) ? (float)$row['price'] : $row['price'],
          'image' => $row['image'] ?? ($row['image_url'] ?? null),
          'seller' => $row['seller'] ?? ($row['seller_name'] ?? 'Unknown Seller'),
          'rating' => isset($row['rating']) && is_numeric($row['rating']) ? (float)$row['rating'] : 4.7,
          'location' => $row['location'] ?? 'North Campus',
          'created_at' => $row['created_at'] ?? null,
          'status' => $row['status'] ?? null,
          'tags' => $row['tags'] ?? null,
        ];
      }
      $res->close();
    }
  }

  if (!count($rows)) {
    // Provide a short static JSON fallback; frontend ALSO has its own fallback.
    $rows = [
      [ 'id' => 1001, 'title' => 'Sample Lamp', 'price' => 15, 'image' => null, 'seller' => 'Sample Seller', 'rating' => 4.9, 'location' => 'North Campus', 'created_at' => null, 'status' => 'AVAILABLE', 'tags' => 'Decor,Lighting' ],
      [ 'id' => 1002, 'title' => 'Sample Textbook', 'price' => 55, 'image' => null, 'seller' => 'Book Vendor', 'rating' => 4.8, 'location' => 'Ellicott', 'created_at' => null, 'status' => 'JUST POSTED', 'tags' => 'Books,Education' ],
    ];
  }

  echo json_encode($rows);
  $conn->close();
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
  if (isset($conn) && $conn instanceof mysqli) $conn->close();
  exit;
}
