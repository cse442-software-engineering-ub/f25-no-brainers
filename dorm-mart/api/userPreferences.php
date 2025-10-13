<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Handle CORS preflight if needed
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/db_connect.php';

function current_user_id(mysqli $conn): ?int {
  // Expect a simple auth_token cookie set by frontend (base64 of "email:timestamp")
  if (!isset($_COOKIE['auth_token'])) return null;
  $raw = base64_decode($_COOKIE['auth_token']);
  if ($raw === false || strpos($raw, ':') === false) return null;
  [$email, $ts] = explode(':', $raw, 2);
  $email = strtolower(trim($email));
  if ($email === '') return null;

  // Look up user_id by email
  $stmt = $conn->prepare('SELECT user_id FROM user_accounts WHERE LOWER(email)=? LIMIT 1');
  if (!$stmt) return null;
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  $uid = null;
  if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $uid = (int)$row['user_id'];
  }
  $stmt->close();
  return $uid;
}

function ensure_table(mysqli $conn): void {
  $res = $conn->query("SHOW TABLES LIKE 'user_preferences'");
  if ($res && $res->num_rows > 0) return;
  $sql = "CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    promo_emails TINYINT(1) NOT NULL DEFAULT 0,
    interests TEXT NULL,
    theme VARCHAR(16) NOT NULL DEFAULT 'light',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_prefs_user FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
  $conn->query($sql); // best-effort
}

try {
  $conn = db();
  if (!$conn || $conn->connect_error) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'DB unavailable']);
    exit;
  }

  $uid = current_user_id($conn);
  if (!$uid) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
  }

  ensure_table($conn);

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare('SELECT promo_emails, interests, theme FROM user_preferences WHERE user_id=? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [
      'promoEmails' => false,
      'interests' => [],
      'theme' => 'light',
    ];
    if ($res && $res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $data['promoEmails'] = (bool)($row['promo_emails'] ?? 0);
      $interestsRaw = (string)($row['interests'] ?? '');
      $decoded = json_decode($interestsRaw, true);
      $data['interests'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
      $data['theme'] = (string)($row['theme'] ?? 'light');
    }
    $stmt->close();
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
      exit;
    }
    $promo = !empty($body['promoEmails']) ? 1 : 0;
    $interests = isset($body['interests']) && is_array($body['interests']) ? array_values(array_filter(array_map('strval', $body['interests']))) : [];
    $theme = in_array(($body['theme'] ?? 'light'), ['light','dark'], true) ? $body['theme'] : 'light';
    $interestsJson = json_encode($interests, JSON_UNESCAPED_UNICODE);

    // Upsert
    $stmt = $conn->prepare('INSERT INTO user_preferences (user_id, promo_emails, interests, theme) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE promo_emails=VALUES(promo_emails), interests=VALUES(interests), theme=VALUES(theme)');
    $stmt->bind_param('iiss', $uid, $promo, $interestsJson, $theme);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'error' => 'Failed to save']);
      exit;
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error']);
}
?>
