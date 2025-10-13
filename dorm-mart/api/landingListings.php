<?php
// Minimal backend for LandingPage listings
// Tries DB first (table `listings` with columns: id, title, price, image_url, tags, created_at)
// Falls back to sample items if DB/table not present

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Local fallback items (match frontend shape)
$fallback = [
  [ 'id' => 1, 'title' => 'Freedom on My Mind- 3rd Edition', 'price' => 40, 'tags' => ['Textbook'] ],
  [ 'id' => 2, 'title' => 'Small Carpet', 'price' => 40, 'tags' => ['Furniture'] ],
  [ 'id' => 3, 'title' => 'Hard Drive', 'price' => 40, 'tags' => ['Electronics'] ],
];

require_once __DIR__ . '/db_connect.php';

try {
  $conn = db();
  if (!$conn || $conn->connect_error) {
    echo json_encode($fallback);
    exit;
  }

  // Detect if `listings` table exists
  $res = $conn->query("SHOW TABLES LIKE 'listings'");
  if (!$res || $res->num_rows === 0) {
    echo json_encode($fallback);
    exit;
  }

  // Query latest listings (adjust column names if needed)
  $sql = "SELECT id, title, price, image_url, tags FROM listings ORDER BY created_at DESC LIMIT 12";
  $q = $conn->query($sql);
  if (!$q) {
    echo json_encode($fallback);
    exit;
  }

  $items = [];
  while ($row = $q->fetch_assoc()) {
    $tags = [];
    if (isset($row['tags'])) {
      $t = $row['tags'];
      // support JSON array or comma-separated string
      $decoded = json_decode($t, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $tags = $decoded;
      } else {
        $tags = array_values(array_filter(array_map('trim', explode(',', (string)$t))));
      }
    }

    $items[] = [
      'id'    => (int)($row['id'] ?? 0),
      'title' => (string)($row['title'] ?? 'Untitled'),
      'price' => isset($row['price']) ? (float)$row['price'] : 0,
      'image' => isset($row['image_url']) && $row['image_url'] !== '' ? (string)$row['image_url'] : null,
      'tags'  => $tags,
    ];
  }

  echo json_encode(count($items) ? $items : $fallback);
} catch (Throwable $e) {
  // Never leak errors; just return fallback
  echo json_encode($fallback);
}
?>
