<?php
/**
 * WebConnect - Security Functions
 * XSS protection, CSRF tokens, input sanitization.
 */

/**
 * Escape output to prevent XSS
 */
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token input field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCSRFToken()) . '">';
}

/**
 * Validate email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username (alphanumeric, underscores, 3-50 chars)
 */
function isValidUsername(string $username): bool {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) === 1;
}

/**
 * Validate password strength (min 6 chars)
 */
function isValidPassword(string $password): bool {
    return strlen($password) >= 6;
}

/**
 * Sanitize filename for storage
 */
function sanitizeFilename(string $filename): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return $sanitized . '.' . $ext;
}

/**
 * Generate random filename
 */
function generateRandomFilename(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

/**
 * Get MIME type from file
 */
function getMimeFromFile(string $filepath): string {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    return $mime;
}

/**
 * Get file size safely
 */
function getFileSize(string $filepath): int {
    return (int) filesize($filepath);
}

/**
 * Log error
 */
function logError(string $message): void {
    error_log('[WebConnect] ' . $message . ' - ' . date('Y-m-d H:i:s'), 3, ERROR_LOG);
}

/**
 * JSON response
 */
function jsonResponse(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Parse JSON request bodies into $_POST for APIs that send JSON via fetch().
 */
function normalizeRequestInput(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!empty($_POST)) {
        return;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        return;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return;
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $_POST = $decoded;
    }
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Validate POST request
 */
function requirePOST(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }
}

/**
 * Require authentication for API
 */
function requireAPIAuth(): void {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

/**
 * Require admin authentication for API
 */
function requireAdminAPIAuth(): void {
    if (!isAdminLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

/**
 * Rate limit check (simple file-based)
 */
function checkRateLimit(string $key, int $maxAttempts = 10, int $window = 60): bool {
    $limitFile = sys_get_temp_dir() . '/wc_rate_' . md5($key);
    $now = time();
    $data = ['count' => 0, 'window_start' => $now];
    if (file_exists($limitFile)) {
        $data = json_decode(file_get_contents($limitFile), true) ?: $data;
    }
    if ($now - $data['window_start'] > $window) {
        $data['count'] = 0;
        $data['window_start'] = $now;
    }
    $data['count']++;
    file_put_contents($limitFile, json_encode($data));
    return $data['count'] <= $maxAttempts;
}
