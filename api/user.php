<?php
/**
 * WebConnect - User Profile API (for viewing other users)
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($id === 0) {
    jsonResponse(['success' => false, 'message' => 'User ID required'], 400);
}

// Check if user exists and is active
$user = Database::fetchOne("SELECT id, username, avatar, status_message, status, last_seen, created_at, is_active FROM users WHERE id = ?", [$id]);
if (!$user || !$user['is_active']) {
    jsonResponse(['success' => false, 'message' => 'User not found'], 404);
}

// Check blocking
if (isBlocked($userId, $id) || isBlocked($id, $userId)) {
    jsonResponse(['success' => false, 'message' => 'Cannot view this user'], 403);
}

$friendship_status = null;
$req = getPendingFriendRequest($userId, $id);
if ($req) {
    $friendship_status = $req['sender_id'] == $userId ? 'pending_sent' : 'pending_received';
}

jsonResponse(['success' => true, 'data' => array_merge($user, [
    'friendship_status' => $friendship_status,
    'is_friend' => isFriends($userId, $id),
])]);
