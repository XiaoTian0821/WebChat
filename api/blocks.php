<?php
/**
 * WebConnect - Blocks API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'block':
        requirePOST();
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId === $userId) {
            jsonResponse(['success' => false, 'message' => 'Cannot block yourself'], 400);
        }
        // Remove existing friendship
        Database::query("DELETE FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)", [$userId, $targetId, $targetId, $userId]);
        // Remove existing friend requests
        Database::query("DELETE FROM friend_requests WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)", [$userId, $targetId, $targetId, $userId]);
        // Block
        Database::insert('user_blocks', ['blocker_id' => $userId, 'blocked_id' => $targetId]);
        jsonResponse(['success' => true, 'message' => 'User blocked']);
        break;

    case 'unblock':
        requirePOST();
        $targetId = (int) ($_POST['user_id'] ?? 0);
        Database::delete('user_blocks', 'blocker_id = ? AND blocked_id = ?', [$userId, $targetId]);
        jsonResponse(['success' => true, 'message' => 'User unblocked']);
        break;

    case 'list':
        $blocked = Database::fetchAll(
            "SELECT u.id, u.username, u.avatar, u.status, ub.created_at
             FROM user_blocks ub JOIN users u ON ub.blocked_id = u.id
             WHERE ub.blocker_id = ? ORDER BY u.username",
            [$userId]
        );
        jsonResponse(['success' => true, 'data' => $blocked]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
