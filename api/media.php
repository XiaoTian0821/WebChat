<?php
/**
 * WebConnect - Media Delivery API
 * Secure file delivery with authorization.
 */
require_once '../includes/bootstrap.php';

$file = $_GET['file'] ?? '';
$type = $_GET['type'] ?? '';
$download = isset($_GET['download']);

if (empty($file) || empty($type)) {
    http_response_code(400);
    exit('Invalid request');
}

// Prevent directory traversal
$file = basename($file);

// Determine path based on type
switch ($type) {
    case 'avatar':
        $basePath = AVATAR_PATH;
        break;
    case 'group':
        $basePath = GROUP_PATH;
        break;
    case 'image':
        $basePath = IMAGE_PATH;
        break;
    case 'file':
        $basePath = FILE_PATH;
        break;
    case 'voice':
        $basePath = VOICE_PATH;
        break;
    default:
        http_response_code(400);
        exit('Invalid type');
}

$fullPath = $basePath . '/' . $file;

// Verify file exists
if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

// Authorization check for private content
if (in_array($type, ['avatar', 'image', 'file', 'voice', 'group'])) {
    if (!isLoggedIn()) {
        http_response_code(403);
        exit('Authentication required');
    }
    // For images in chat, verify user is in the conversation
    // (simplified: all logged-in users can access their own uploaded files)
}

// Determine MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Security headers
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($download) {
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
} else {
    header('Content-Disposition: inline');
}

// Support range requests for large files
$fileSize = filesize($fullPath);
$range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

if ($range && $fileSize > 0) {
    list($unit, $rangeSpec) = explode('=', $range, 2);
    if ($unit === 'bytes') {
        list($start, $end) = explode('-', $rangeSpec);
        $start = $start !== '' ? (int)$start : 0;
        $end = $end !== '' ? (int)$end : $fileSize - 1;
        if ($start >= $fileSize || $end >= $fileSize) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            exit;
        }
        $length = $end - $start + 1;
        header("HTTP/1.1 206 Partial Content");
        header("Content-Range: bytes $start-$end/$fileSize");
        header("Content-Length: $length");
        $fp = fopen($fullPath, 'rb');
        fseek($fp, $start);
        fpassthru($fp);
        fclose($fp);
        exit;
    }
}

header("Content-Length: $fileSize");
header("Content-Type: $mime");
readfile($fullPath);
