<?php
/**
 * WebConnect - Presence API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'update':
        requirePOST();
        $status = sanitize($_POST['status'] ?? 'online');
        if (!in_array($status, ['online', 'away', 'busy', 'offline'])) {
            $status = 'online';
        }
        Database::update('users', [
            'status' => $status,
            'last_seen' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$userId]);
        jsonResponse(['success' => true]);
        break;

    case 'get':
        // Get online friends
        $friends = Database::fetchAll(
            "SELECT u.id, u.username, u.avatar, u.status, u.last_seen
             FROM friendships f
             JOIN users u ON (f.friend_id = u.id AND f.user_id = ?)
             WHERE u.is_active = 1
             ORDER BY u.status DESC, u.username",
            [$userId]
        );
        jsonResponse(['success' => true, 'data' => $friends]);
        break;

    case 'heartbeat':
        // Lightweight heartbeat to keep user online
        Database::update('users', ['last_seen' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
