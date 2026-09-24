<?php
/**
 * WebConnect - Reactions API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'toggle':
        $msgId = (int) ($_POST['message_id'] ?? 0);
        $reaction = sanitize($_POST['reaction'] ?? '');
        $chatType = $_POST['chat_type'] ?? 'private';
        $groupId = (int) ($_POST['group_id'] ?? 0);

        if (!in_array($reaction, ['👍','❤️','😂','😮','😢','👏','🔥','💯','🎉','😡','👎'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid reaction']);
        }

        if ($chatType === 'group' && $groupId > 0) {
            // Check if user is in group
            if (!isGroupMember($groupId, $userId)) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            // Check if reaction exists
            $existing = Database::fetchOne("SELECT id FROM group_message_reactions WHERE message_id=? AND user_id=?", [$msgId, $userId]);
            if ($existing) {
                Database::update('group_message_reactions', ['reaction' => $reaction], 'id = ?', [$existing['id']]);
                $isOwn = true;
            } else {
                Database::insert('group_message_reactions', ['message_id' => $msgId, 'user_id' => $userId, 'reaction' => $reaction]);
                $isOwn = true;
            }
            // Get counts
            $reactions = Database::fetchAll(
                "SELECT reaction, COUNT(*) as count FROM group_message_reactions WHERE message_id=? GROUP BY reaction",
                [$msgId]
            );
            jsonResponse(['success' => true, 'data' => ['count' => count($reactions), 'reactions' => $reactions, 'is_own' => $isOwn]]);
        } else {
            $existing = Database::fetchOne("SELECT id FROM message_reactions WHERE message_id=? AND user_id=?", [$msgId, $userId]);
            if ($existing) {
                Database::update('message_reactions', ['reaction' => $reaction], 'id = ?', [$existing['id']]);
            } else {
                Database::insert('message_reactions', ['message_id' => $msgId, 'user_id' => $userId, 'reaction' => $reaction]);
            }
            $reactions = Database::fetchAll(
                "SELECT reaction, COUNT(*) as count FROM message_reactions WHERE message_id=? GROUP BY reaction",
                [$msgId]
            );
            jsonResponse(['success' => true, 'data' => ['reactions' => $reactions, 'is_own' => true]]);
        }
        break;

    case 'remove':
        $msgId = (int) ($_POST['message_id'] ?? 0);
        $existing = Database::fetchOne("SELECT id FROM message_reactions WHERE message_id=? AND user_id=?", [$msgId, $userId]);
        if ($existing) {
            Database::delete('message_reactions', 'id = ?', [$existing['id']]);
        }
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
