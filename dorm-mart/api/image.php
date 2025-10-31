<?php
declare(strict_types=1);

// dorm-mart/api/image.php
// Serves images that are stored under /images/ on disk
// Accepts either ?file=filename.png OR ?url=/images/filename.png

$IMAGE_DIR = realpath(__DIR__ . '/../images');
if ($IMAGE_DIR === false) {
    http_response_code(500);
    exit('Image directory not found');
}

function stream_image(string $path): void {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $path);
    finfo_close($finfo);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// 1) ?file=filename.png
if (isset($_GET['file']) && $_GET['file'] !== '') {
    $file = basename($_GET['file']);
    $path = $IMAGE_DIR . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) {
        http_response_code(404);
        exit('Image not found');
    }
    stream_image($path);
}

// 2) ?url=/images/filename.png OR ?url=/data/images/filename.png
if (isset($_GET['url']) && $_GET['url'] !== '') {
    $url = $_GET['url'];

    // strip query part if present
    $qpos = strpos($url, '?');
    if ($qpos !== false) {
        $url = substr($url, 0, $qpos);
    }

    // Extract filename from any path format:
    // - /images/filename.jpg
    // - /data/images/filename.jpg  
    // - /CSE442/2025-Fall/cse-442j/images/filename.jpg
    // - /CSE442/2025-Fall/cse-442j/data/images/filename.jpg
    // All images are stored in /images/ directory on disk, regardless of URL path
    $file = basename($url);
    
    $path = $IMAGE_DIR . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) {
        http_response_code(404);
        exit('Image not found');
    }
    stream_image($path);
}

// if neither param present
http_response_code(400);
exit('Missing file or url');
