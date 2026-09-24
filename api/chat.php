<?php
/**
 * WebConnect - Chat API (conversations, info, typing)
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'conversations':
        $convs = getConversations($userId);
        jsonResponse(['success' => true, 'data' => $convs]);
        break;

    case 'group_conversations':
        $groups = getUserGroups($userId);
        jsonResponse(['success' => true, 'data' => $groups]);
        break;

    case 'chat_info':
        $chatId = (int) ($_GET['id'] ?? 0);
        if ($chatId === $userId) { jsonResponse(['success' => false, 'message' => 'Invalid'], 400); }
        $user = Database::fetchOne("SELECT id, username, avatar, status, last_seen, status_message FROM users WHERE id = ?", [$chatId]);
        if (!$user) { jsonResponse(['success' => false, 'message' => 'User not found'], 404); }
        jsonResponse(['success' => true, 'data' => $user]);
        break;

    case 'group_info':
        $groupId = (int) ($_GET['id'] ?? 0);
        if (!isGroupMember($groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $group = Database::fetchOne("SELECT * FROM group_chats WHERE id = ?", [$groupId]);
        if (!$group) { jsonResponse(['success' => false, 'message' => 'Group not found'], 404); }
        $members = getGroupMembers($groupId);
        jsonResponse(['success' => true, 'data' => array_merge($group, ['members' => $members])]);
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

    case 'get_typing':
        $chatId = (int) ($_GET['id'] ?? 0);
        $chatType = $_GET['type'] ?? 'private';
        if ($chatType === 'group') {
            $typing = getTypingIndicators($chatId, $userId, true, $chatId);
        } else {
            $typing = getTypingIndicators($userId, $chatId);
        }
        jsonResponse(['success' => true, 'data' => $typing]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
