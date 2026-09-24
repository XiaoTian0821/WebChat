<?php
/**
 * WebConnect - Notifications API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $notifications = Database::fetchAll(
            "SELECT n.*, u.username, u.avatar
             FROM notifications n
             LEFT JOIN users u ON n.sender_id = u.id
             WHERE n.user_id = ?
             ORDER BY n.created_at DESC LIMIT 50",
            [$userId]
        );
        jsonResponse(['success' => true, 'data' => $notifications]);
        break;

    case 'count':
        $count = (int) Database::fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
        jsonResponse(['success' => true, 'data' => $count]);
        break;

    case 'mark_read':
        requirePOST();
        $notifId = (int) ($_POST['id'] ?? 0);
        Database::update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notifId, $userId]);
        jsonResponse(['success' => true]);
        break;

    case 'mark_all_read':
        requirePOST();
        Database::update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [$userId]);
        jsonResponse(['success' => true]);
        break;

    case 'delete':
        requirePOST();
        $notifId = (int) ($_POST['id'] ?? 0);
        Database::delete('notifications', 'id = ? AND user_id = ?', [$notifId, $userId]);
        jsonResponse(['success' => true]);
        break;

    case 'delete_all':
        requirePOST();
        $count = Database::delete('notifications', 'user_id = ?', [$userId]);
        jsonResponse(['success' => true, 'data' => $count]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
?>
