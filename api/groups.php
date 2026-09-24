<?php
/**
 * WebConnect - Groups API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'my_groups':
        $groups = getUserGroups($userId);
        jsonResponse(['success' => true, 'data' => $groups]);
        break;

    case 'create':
        requirePOST();
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Invalid token'], 403);
        }
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Group name is required'], 400);
        }
        $groupId = Database::insert('group_chats', [
            'name' => $name,
            'description' => $description,
            'owner_id' => $userId,
        ]);
        Database::insert('group_members', [
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => 'owner',
        ]);
        jsonResponse(['success' => true, 'message' => 'Group created', 'data' => ['group_id' => $groupId]]);
        break;

    case 'info':
        $groupId = (int) ($_GET['id'] ?? 0);
        if (!isGroupMember($groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $group = Database::fetchOne("SELECT * FROM group_chats WHERE id = ?", [$groupId]);
        $members = getGroupMembers($groupId);
        jsonResponse(['success' => true, 'data' => array_merge($group, ['members' => $members])]);
        break;

    case 'search_users':
        $q = sanitize($_GET['q'] ?? '');
        $groupId = (int) ($_GET['group_id'] ?? 0);
        $users = Database::fetchAll(
            "SELECT id, username, avatar FROM users WHERE (username LIKE ? OR email LIKE ?) AND id != ? AND is_active = 1 LIMIT 20",
            ["%$q%", "%$q%", $userId]
        );
        jsonResponse(['success' => true, 'data' => $users]);
        break;

    case 'add_member':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $memberId = (int) ($_POST['user_id'] ?? 0);
        if (!isGroupMember($groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $role = getGroupRole($groupId, $userId);
        if (!in_array($role, ['owner', 'admin'])) {
            jsonResponse(['success' => false, 'message' => 'Only owner/admin can add members'], 403);
        }
        if (isGroupMember($groupId, $memberId)) {
            jsonResponse(['success' => false, 'message' => 'User is already a member'], 400);
        }
        Database::insert('group_members', ['group_id' => $groupId, 'user_id' => $memberId, 'role' => 'member']);
        createNotification($memberId, 'group_invitation', 'Group Invitation', 'You have been added to ' . Database::fetchColumn("SELECT name FROM group_chats WHERE id=?", [$groupId]), $groupId, $userId);
        jsonResponse(['success' => true, 'message' => 'Member added']);
        break;

    case 'remove_member':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $memberId = (int) ($_POST['user_id'] ?? 0);
        $myRole = getGroupRole($groupId, $userId);
        $memberRole = getGroupRole($groupId, $memberId);
        if ($myRole !== 'owner' && $myRole !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if ($memberRole === 'owner') {
            jsonResponse(['success' => false, 'message' => 'Cannot remove the owner'], 400);
        }
        if ($memberId === $userId) {
            jsonResponse(['success' => false, 'message' => 'Cannot remove yourself this way, use leave group'], 400);
        }
        Database::delete('group_members', 'group_id = ? AND user_id = ?', [$groupId, $memberId]);
        jsonResponse(['success' => true, 'message' => 'Member removed']);
        break;

    case 'change_role':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $memberId = (int) ($_POST['user_id'] ?? 0);
        $newRole = sanitize($_POST['role'] ?? '');
        if (!in_array($newRole, ['owner', 'admin', 'member'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid role'], 400);
        }
        if (getGroupRole($groupId, $userId) !== 'owner') {
            jsonResponse(['success' => false, 'message' => 'Only owner can change roles'], 403);
        }
        Database::update('group_members', ['role' => $newRole], 'group_id = ? AND user_id = ?', [$groupId, $memberId]);
        jsonResponse(['success' => true, 'message' => 'Role updated']);
        break;

    case 'leave':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $myRole = getGroupRole($groupId, $userId);
        if ($myRole === 'owner') {
            // Transfer ownership or delete
            $otherMembers = Database::fetchAll("SELECT user_id, role FROM group_members WHERE group_id = ? AND user_id != ? ORDER BY FIELD(role,'admin','member') LIMIT 1", [$groupId, $userId]);
            if (count($otherMembers) > 0) {
                Database::update('group_members', ['role' => 'owner'], 'group_id = ? AND user_id = ?', [$groupId, (int)$otherMembers[0]['user_id']]);
            }
        }
        Database::delete('group_members', 'group_id = ? AND user_id = ?', [$groupId, $userId]);
        jsonResponse(['success' => true, 'message' => 'Left the group']);
        break;

    case 'delete':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        if (getGroupRole($groupId, $userId) !== 'owner') {
            jsonResponse(['success' => false, 'message' => 'Only owner can delete the group'], 403);
        }
        Database::delete('group_chats', 'id = ? AND owner_id = ?', [$groupId, $userId]);
        jsonResponse(['success' => true, 'message' => 'Group deleted']);
        break;

    case 'update':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        if (!isGroupMember($groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $role = getGroupRole($groupId, $userId);
        if (!in_array($role, ['owner', 'admin'])) {
            jsonResponse(['success' => false, 'message' => 'Only owner/admin can edit group'], 403);
        }
        Database::update('group_chats', ['name' => $name, 'description' => $description], 'id = ? AND owner_id = ?', [$groupId, $userId]);
        jsonResponse(['success' => true, 'message' => 'Group updated']);
        break;

    case 'upload_avatar':
        requirePOST();
        $groupId = (int) ($_POST['group_id'] ?? 0);
        if (!isGroupMember($groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $role = getGroupRole($groupId, $userId);
        if (!in_array($role, ['owner', 'admin'])) {
            jsonResponse(['success' => false, 'message' => 'Only owner/admin can change avatar'], 403);
        }
        $file = $_FILES['avatar'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Upload error']);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ALLOWED_AVATAR_MIME) || !in_array($ext, ALLOWED_AVATAR_EXT)) {
            jsonResponse(['success' => false, 'message' => 'Invalid file type']);
        }
        if ($file['size'] > MAX_AVATAR_SIZE) {
            jsonResponse(['success' => false, 'message' => 'File too large']);
        }
        $randomName = generateRandomFilename($ext);
        $dest = GROUP_PATH . '/' . $randomName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Remove old avatar
            $old = Database::fetchColumn("SELECT avatar FROM group_chats WHERE id = ?", [$groupId]);
            if ($old && file_exists(GROUP_PATH . '/' . $old)) @unlink(GROUP_PATH . '/' . $old);
            Database::update('group_chats', ['avatar' => $randomName], 'id = ?', [$groupId]);
            jsonResponse(['success' => true, 'data' => ['avatar' => $randomName]]);
        }
        jsonResponse(['success' => false, 'message' => 'Failed to save']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
