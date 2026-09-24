<?php
/**
 * WebConnect - Profile API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int) ($_GET['id'] ?? $userId);
        $user = Database::fetchOne("SELECT id, username, avatar, status_message, status, last_seen, created_at FROM users WHERE id = ?", [$id]);
        if (!$user) { jsonResponse(['success' => false, 'message' => 'User not found'], 404); }
        jsonResponse(['success' => true, 'data' => $user]);
        break;

    case 'update':
        requirePOST();
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Invalid token'], 403);
        }
        $statusMessage = sanitize($_POST['status_message'] ?? '');
        Database::update('users', ['status_message' => $statusMessage], 'id = ?', [$userId]);
        jsonResponse(['success' => true, 'message' => 'Profile updated']);
        break;

    case 'change_password':
        requirePOST();
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $user = Database::fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$user || !verifyPassword($current, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 401);
        }
        if (!isValidPassword($new)) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
        }
        if ($new !== $confirm) {
            jsonResponse(['success' => false, 'message' => 'Passwords do not match'], 400);
        }
        Database::update('users', ['password' => hashPassword($new)], 'id = ?', [$userId]);
        jsonResponse(['success' => true, 'message' => 'Password changed']);
        break;

    case 'upload_avatar':
        requirePOST();
        $file = $_FILES['avatar'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Upload error']);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ALLOWED_AVATAR_MIME) || !in_array($ext, ALLOWED_AVATAR_EXT)) {
            jsonResponse(['success' => false, 'message' => 'Invalid file type. Use JPG, PNG, GIF, or WEBP']);
        }
        if ($file['size'] > MAX_AVATAR_SIZE) {
            jsonResponse(['success' => false, 'message' => 'File too large (max 2MB)']);
        }
        $randomName = generateRandomFilename($ext);
        $dest = AVATAR_PATH . '/' . $randomName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $old = Database::fetchColumn("SELECT avatar FROM users WHERE id = ?", [$userId]);
            if ($old && file_exists(AVATAR_PATH . '/' . $old)) @unlink(AVATAR_PATH . '/' . $old);
            Database::update('users', ['avatar' => $randomName], 'id = ?', [$userId]);
            jsonResponse(['success' => true, 'data' => ['avatar' => $randomName]]);
        }
        jsonResponse(['success' => false, 'message' => 'Failed to save avatar']);
        break;

    case 'remove_avatar':
        requirePOST();
        $user = Database::fetchOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
        if ($user['avatar'] && file_exists(AVATAR_PATH . '/' . $user['avatar'])) {
            @unlink(AVATAR_PATH . '/' . $user['avatar']);
        }
        Database::update('users', ['avatar' => null], 'id = ?', [$userId]);
        jsonResponse(['success' => true, 'message' => 'Avatar removed']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
