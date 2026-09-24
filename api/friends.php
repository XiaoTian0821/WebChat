<?php
/**
 * WebConnect - Friends API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'search':
        $q = sanitize($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            jsonResponse(['success' => false, 'message' => 'Search query too short']);
        }
        $users = Database::fetchAll(
            "SELECT id, username, avatar, status, last_seen FROM users
             WHERE (username LIKE ? OR email LIKE ?) AND id != ? AND is_active = 1
             ORDER BY username LIMIT 20",
            ["%$q%", "%$q%", $userId]
        );
        $result = [];
        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $isFriend = isFriends($userId, $uid);
            $req = getPendingFriendRequest($userId, $uid);
            $reqStatus = null;
            if ($req) {
                $reqStatus = $req['sender_id'] == $userId ? 'pending_sent' : 'pending_received';
            }
            $result[] = [
                'id' => $uid,
                'username' => $u['username'],
                'avatar' => $u['avatar'],
                'status' => $u['status'],
                'last_seen' => $u['last_seen'],
                'is_friend' => $isFriend,
                'request_status' => $reqStatus,
                'is_blocked' => isBlocked($userId, $uid),
            ];
        }
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'send':
        requirePOST();
        $friendId = (int) ($_POST['friend_id'] ?? 0);
        if ($friendId === $userId) { jsonResponse(['success' => false, 'message' => 'Cannot add yourself'], 400); }
        if (isFriends($userId, $friendId)) { jsonResponse(['success' => false, 'message' => 'Already friends'], 400); }
        if (isBlocked($userId, $friendId) || isBlocked($friendId, $userId)) { jsonResponse(['success' => false, 'message' => 'Cannot send request to blocked user'], 400); }
        $existing = Database::fetchOne("SELECT id FROM friend_requests WHERE sender_id=? AND receiver_id=? AND status='pending'", [$userId, $friendId]);
        if ($existing) { jsonResponse(['success' => false, 'message' => 'Request already sent'], 400); }
        Database::insert('friend_requests', ['sender_id' => $userId, 'receiver_id' => $friendId]);
        $sender = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$userId]);
        createNotification($friendId, 'friend_request', 'New Friend Request', $sender['username'] . ' wants to be your friend', null, $userId);
        jsonResponse(['success' => true, 'message' => 'Friend request sent']);
        break;

    case 'respond':
        requirePOST();
        $friendId = (int) ($_POST['friend_id'] ?? 0);
        $respond = sanitize($_POST['response'] ?? $_POST['status'] ?? $_POST['action'] ?? '');
        $req = Database::fetchOne("SELECT id FROM friend_requests WHERE sender_id=? AND receiver_id=? AND status='pending'", [$friendId, $userId]);
        if (!$req) { jsonResponse(['success' => false, 'message' => 'Request not found'], 404); }
        if ($respond === 'accept') {
            Database::insert('friendships', ['user_id' => $userId, 'friend_id' => $friendId]);
            Database::insert('friendships', ['user_id' => $friendId, 'friend_id' => $userId]);
            Database::update('friend_requests', ['status' => 'accepted'], 'id = ?', [$req['id']]);
            createNotification($friendId, 'friend_accepted', 'Friend Request Accepted', 'You are now friends with ' . getCurrentUser()['username'], null, $userId);
            jsonResponse(['success' => true, 'message' => 'Now friends!']);
        } elseif ($respond === 'reject') {
            Database::update('friend_requests', ['status' => 'rejected'], 'id = ?', [$req['id']]);
            jsonResponse(['success' => true, 'message' => 'Request rejected']);
        }
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        break;

    case 'remove':
        requirePOST();
        $friendId = (int) ($_POST['friend_id'] ?? 0);
        Database::query("DELETE FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)", [$userId, $friendId, $friendId, $userId]);
        Database::query("DELETE FROM friend_requests WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)", [$userId, $friendId, $friendId, $userId]);
        jsonResponse(['success' => true, 'message' => 'Friend removed']);
        break;

    case 'list':
        $direction = $_GET['direction'] ?? 'friends'; // friends, pending_sent, pending_received
        if ($direction === 'friends') {
            $friends = Database::fetchAll(
                "SELECT u.id, u.username, u.avatar, u.status, u.last_seen
                 FROM friendships f JOIN users u ON (f.friend_id = u.id AND f.user_id = ?)
                 WHERE u.is_active = 1 ORDER BY u.username",
                [$userId]
            );
            jsonResponse(['success' => true, 'data' => $friends]);
        } elseif ($direction === 'pending_sent') {
            $requests = Database::fetchAll(
                "SELECT u.id, u.username, u.avatar, u.status, fr.created_at
                 FROM friend_requests fr JOIN users u ON fr.receiver_id = u.id
                 WHERE fr.sender_id = ? AND fr.status = 'pending' ORDER BY fr.created_at DESC",
                [$userId]
            );
            jsonResponse(['success' => true, 'data' => $requests]);
        } elseif ($direction === 'pending_received') {
            $requests = Database::fetchAll(
                "SELECT u.id, u.username, u.avatar, u.status, fr.created_at
                 FROM friend_requests fr JOIN users u ON fr.sender_id = u.id
                 WHERE fr.receiver_id = ? AND fr.status = 'pending' ORDER BY fr.created_at DESC",
                [$userId]
            );
            jsonResponse(['success' => true, 'data' => $requests]);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
