<?php
// Minimal router for /serve/dorm-mart/api
header('Content-Type: application/json'); // tell the client we speak JSON

// 1) Read the rewritten path from ?path=... (set by .htaccess)
//    The null-coalescing operator (??) uses '' if 'path' isn't present.
$rawPath = $_GET['path'] ?? '';

// 2) Normalize the path: strip any accidental query string + leading/trailing slashes
//    parse_url(..., PHP_URL_PATH) drops "?a=b"; trim(..., '/') removes extra slashes.
$path = trim(parse_url($rawPath, PHP_URL_PATH), '/');

// 3) Allow only safe characters to avoid directory traversal or oddities
//    Regex: letters, digits, underscore, dash, forward-slash.
if ($path !== '' && !preg_match('#^[A-Za-z0-9_/\-]+$#', $path)) {
  http_response_code(400); // Bad Request
  echo json_encode(['success' => false, 'error' => 'Invalid path']);
  exit;
}

// 4) If no path, return 404 (you can map a default if you prefer)
if ($path === '') {
  http_response_code(404);
  echo json_encode(['success' => false, 'error' => 'Not found']);
  exit;
}

// 5) Resolve to a handler file under /api, e.g. "ping" → "/api/ping.php"
$file = __DIR__ . '/' . $path . '.php';

// 6) If the handler file doesn't exist, 404 cleanly
if (!is_file($file)) {
  http_response_code(404);
  echo json_encode(['success' => false, 'error' => 'Not found']);
  exit;
}

// 7) Run the handler. Handlers should set their own status codes and echo JSON.
require $file;
