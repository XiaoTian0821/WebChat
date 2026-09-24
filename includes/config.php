<?php
/**
 * WebConnect - Configuration
 * Central configuration for the application.
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'webconnect');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_CHARSET', 'utf8mb4');

// Application
define('APP_NAME', 'WebConnect');
define('APP_URL', 'http://localhost/WebChat');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('IMAGE_PATH', UPLOAD_PATH . '/images');
define('FILE_PATH', UPLOAD_PATH . '/files');
define('VOICE_PATH', UPLOAD_PATH . '/voices');
define('GROUP_PATH', UPLOAD_PATH . '/groups');

// Session
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'webconnect_sid');

// Security
define('CSRF_TOKEN_LENGTH', 32);

// File Upload Limits
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024);      // 2MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);        // 5MB
define('MAX_FILE_SIZE', 10 * 1024 * 1024);        // 10MB
define('MAX_VOICE_SIZE', 10 * 1024 * 1024);       // 10MB

// Allowed MIME types
define('ALLOWED_AVATAR_MIME', ['image/jpeg','image/png','image/gif','image/webp']);
define('ALLOWED_IMAGE_MIME', ['image/jpeg','image/png','image/gif','image/webp']);
define('ALLOWED_FILE_MIME', [
    'application/pdf','application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain','application/zip'
]);
define('ALLOWED_VOICE_MIME', [
    'audio/webm','audio/ogg','audio/mpeg','audio/wav','audio/mp4','audio/x-m4a'
]);

// Allowed extensions
define('ALLOWED_AVATAR_EXT', ['jpg','jpeg','png','gif','webp']);
define('ALLOWED_IMAGE_EXT', ['jpg','jpeg','png','gif','webp']);
define('ALLOWED_FILE_EXT', ['pdf','doc','docx','xls','xlsx','txt','zip']);
define('ALLOWED_VOICE_EXT', ['webm','ogg','mp3','wav','m4a']);

// Polling intervals (seconds)
define('MSG_POLL_INTERVAL', 2);
define('NOTIF_POLL_INTERVAL', 5);
define('PRESENCE_POLL_INTERVAL', 10);
define('CALL_POLL_INTERVAL', 1);

// Typing indicator timeout
define('TYPING_TIMEOUT', 3); // seconds

// Error log
define('ERROR_LOG', BASE_PATH . '/uploads/errors.log');

// Load local override if exists
if (file_exists(BASE_PATH . '/includes/config.local.php')) {
    require BASE_PATH . '/includes/config.local.php';
}

// Start session with secure settings
@ini_set('session.cookie_httponly', 1);
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('session.use_strict_mode', 1);
@session_name(SESSION_NAME);
@session_start();

// Set default timezone
date_default_timezone_set('UTC');
