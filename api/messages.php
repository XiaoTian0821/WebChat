<?php
/**
 * WebConnect - Messages API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $chatId = (int) ($_GET['id'] ?? $_GET['chat_id'] ?? 0);
        $chatType = $_GET['type'] ?? 'private';
        $limit = (int) ($_GET['limit'] ?? 50);

        if ($chatType === 'group') {
            if (!isGroupMember($chatId, $userId)) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $messages = Database::fetchAll(
                "SELECT gm.*, u.username, u.avatar,
                    (SELECT GROUP_CONCAT(CONCAT('(', reaction, ')', COUNT(*)) SEPARATOR '') FROM group_message_reactions gmr WHERE gmr.message_id = gm.id GROUP BY gmr.reaction) as reactions_json
                 FROM group_messages gm
                 JOIN users u ON gm.sender_id = u.id
                 WHERE gm.group_id = ? AND gm.is_deleted = 0
                 GROUP BY gm.id
                 ORDER BY gm.created_at DESC LIMIT ?",
                [$chatId, $limit]
            );
            // Reverse to get chronological order
            $messages = array_reverse($messages);
            foreach ($messages as &$m) {
                $m['is_sent'] = $m['sender_id'] == $userId;
                // Parse reactions
                $m['reactions'] = [];
                if ($m['reactions_json']) {
                    // Simplified: fetch reactions separately
                    $reactions = Database::fetchAll(
                        "SELECT reaction, COUNT(*) as count, MAX(CASE WHEN user_id=? THEN 1 ELSE 0 END) as is_own
                         FROM group_message_reactions WHERE message_id=? GROUP BY reaction",
                        [$userId, $m['id']]
                    );
                    $m['reactions'] = $reactions;
                }
                // Reply info
                if ($m['reply_to_id']) {
                    $reply = Database::fetchOne("SELECT body, sender_id FROM group_messages WHERE id = ?", [$m['reply_to_id']]);
                    $m['reply_to_body'] = $reply ? ($reply['sender_id'] == $userId || !$reply['is_deleted'] ? $reply['body'] : 'Deleted message') : 'Deleted message';
                }
            }
            unset($m);
        } else {
            // Private messages
            $messages = Database::fetchAll(
                "SELECT m.*, u.username, u.avatar,
                    (SELECT COUNT(*) FROM message_read_receipts r WHERE r.message_id = m.id AND r.user_id = ?) as read_count,
                    (SELECT COUNT(*) FROM messages ms WHERE ms.sender_id = ? AND ms.receiver_id = ? AND ms.is_deleted_receiver = 0) as total
                 FROM messages m
                 JOIN users u ON m.sender_id = u.id
                 WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                   AND m.is_deleted_sender = 0 AND m.is_deleted_receiver = 0
                 ORDER BY m.created_at DESC LIMIT ?",
                [$userId, $userId, $chatId, $userId, $chatId, $chatId, $userId, $limit]
            );
            $messages = array_reverse($messages);
            foreach ($messages as &$m) {
                $m['is_sent'] = $m['sender_id'] == $userId;
                // Reactions
                $m['reactions'] = Database::fetchAll(
                    "SELECT reaction, COUNT(*) as count, MAX(CASE WHEN user_id=? THEN 1 ELSE 0 END) as is_own
                     FROM message_reactions WHERE message_id=? GROUP BY reaction",
                    [$userId, $m['id']]
                );
                // Reply info
                if ($m['reply_to_id']) {
                    $reply = Database::fetchOne("SELECT body FROM messages WHERE id = ?", [$m['reply_to_id']]);
                    $m['reply_to_body'] = $reply ? $reply['body'] : 'Deleted message';
                }
                // Mark as read
                if (!$m['is_sent']) {
                    Database::query("INSERT IGNORE INTO message_read_receipts (message_id, user_id) VALUES (?, ?)", [$m['id'], $userId]);
                }
            }
            unset($m);
        }
        jsonResponse(['success' => true, 'data' => $messages]);
        break;

    case 'send':
        requirePOST();
        $chatId = (int) ($_POST['chat_id'] ?? 0);
        $chatType = $_POST['type'] ?? 'private';
        $body = sanitize($_POST['message'] ?? '');
        $type = sanitize($_POST['message_type'] ?? 'text');
        $replyToId = (int) ($_POST['reply_to'] ?? 0);

        if ($chatType === 'group') {
            if (!isGroupMember($chatId, $userId)) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $msgId = Database::insert('group_messages', [
                'group_id' => $chatId,
                'sender_id' => $userId,
                'type' => $type,
                'body' => $body,
                'reply_to_id' => $replyToId ?: null,
            ]);
            // Notify group members
            $members = Database::fetchAll("SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?", [$chatId, $userId]);
            foreach ($members as $member) {
                createNotification((int)$member['user_id'], 'group_message', 'New Group Message', 'You have a new message in group ' . $chatId, $msgId, $userId);
            }
            jsonResponse(['success' => true, 'data' => ['id' => $msgId, 'group_id' => $chatId, 'sender_id' => $userId, 'type' => $type, 'body' => $body]]);
        } else {
            $receiverId = $chatId;
            if (isBlocked($userId, $receiverId) || isBlocked($receiverId, $userId)) {
                jsonResponse(['success' => false, 'message' => 'Cannot send message to blocked user'], 403);
            }
            $msgId = Database::insert('messages', [
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'type' => $type,
                'body' => $body,
                'reply_to_id' => $replyToId ?: null,
            ]);
            createNotification($receiverId, 'new_message', 'New Message', 'You received a message from ' . getCurrentUser()['username'], $msgId, $userId);
            jsonResponse(['success' => true, 'data' => ['id' => $msgId, 'receiver_id' => $receiverId, 'type' => $type, 'body' => $body]]);
        }
        break;

    case 'delete':
        requirePOST();
        $msgId = (int) ($_POST['message_id'] ?? 0);
        $chatType = $_POST['chat_type'] ?? 'private';
        if ($chatType === 'group') {
            if (!isGroupMember((int)$_POST['chat_id'], $userId)) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            Database::update('group_messages', ['is_deleted' => 1], 'id = ? AND sender_id = ?', [$msgId, $userId]);
        } else {
            Database::update('messages', ['is_deleted_sender' => 1], 'id = ? AND sender_id = ?', [$msgId, $userId]);
        }
        jsonResponse(['success' => true, 'message' => 'Message deleted']);
        break;

    case 'typing':
        requirePOST();
        $chatId = (int) ($_POST['chat_id'] ?? 0);
        $chatType = $_POST['chat_type'] ?? 'private';
        if ($chatType === 'group') {
            Database::query("DELETE FROM typing_indicators WHERE user_id=? AND is_group=1 AND group_id=?", [$userId, $chatId]);
            Database::insert('typing_indicators', ['user_id' => $userId, 'target_id' => $chatId, 'is_group' => 1, 'group_id' => $chatId]);
        } else {
            Database::query("DELETE FROM typing_indicators WHERE user_id=? AND is_group=0 AND target_id=?", [$userId, $chatId]);
            Database::insert('typing_indicators', ['user_id' => $userId, 'target_id' => $chatId, 'is_group' => 0]);
        }
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}