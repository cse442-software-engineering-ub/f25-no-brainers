<?php
// Tiny health check for your /api router.
// If this returns JSON 200, your .htaccess + index.php rewriting is fine.
header('Content-Type', 'application/json'); // always set JSON

echo json_encode([
  'ok'      => true,          // simple flag
  'endpoint'=> 'ping',        // tells you which file ran
  'method'  => $_SERVER['REQUEST_METHOD'] // echo method to confirm GET
]);
