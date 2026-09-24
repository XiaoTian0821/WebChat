<?php
/**
 * WebConnect - Common Functions
 */

/**
 * Get user avatar HTML
 */
function getUserAvatar(int $userId, int $size = 40, string $username = ''): string {
    $user = Database::fetchOne("SELECT avatar, username FROM users WHERE id = ?", [$userId]);
    $displayName = $username ?: ($user['username'] ?? 'U');
    $initials = substr(strtoupper($displayName), 0, 1);
    if ($user && $user['avatar'] && file_exists(AVATAR_PATH . '/' . $user['avatar'])) {
        return '<img src="' . APP_URL . '/api/media.php?file=' . urlencode($user['avatar']) . '&type=avatar" alt="Avatar" class="avatar" style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;object-fit:cover;">';
    }
    return '<div class="avatar avatar-default" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . ($size * 0.4) . 'px;" title="' . e($displayName) . '">' . $initials . '</div>';
}

/**
 * Get user online status badge
 */
function getStatusBadge(string $status, string $lastSeen = null): string {
    $colors = [
        'online' => 'bg-success',
        'away' => 'bg-warning',
        'busy' => 'bg-danger',
        'offline' => 'bg-secondary'
    ];
    $color = $colors[$status] ?? 'bg-secondary';
    $time = '';
    if ($lastSeen) {
        $timestamp = strtotime($lastSeen);
        if ($timestamp) {
            $diff = time() - $timestamp;
            if ($diff < 60) $time = 'just now';
            elseif ($diff < 3600) $time = (int)($diff / 60) . 'm ago';
            elseif ($diff < 86400) $time = (int)($diff / 3600) . 'h ago';
            else $time = (int)($diff / 86400) . 'd ago';
        }
    }
    return '<span class="status-dot ' . $color . '" title="' . e(ucfirst($status)) . ($time ? ' - Last seen ' . $time : '') . '"></span>';
}

/**
 * Format time ago
 */
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '';
    $diff = time() - $timestamp;
    if ($diff < 60) return 'just now';
    elseif ($diff < 3600) return (int)($diff / 60) . ' min ago';
    elseif ($diff < 86400) return (int)($diff / 3600) . ' hr ago';
    elseif ($diff < 604800) return (int)($diff / 86400) . ' days ago';
    else return date('M j, Y', $timestamp);
}

/**
 * Format file size
 */
function formatFileSize(int $bytes): string {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log(1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * Format voice duration
 */
function formatVoiceDuration(float $seconds): string {
    $mins = (int)($seconds / 60);
    $secs = (int)($seconds % 60);
    return $mins . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
}

/**
 * Get unread message count
 */
function getUnreadMessageCount(int $userId): int {
    return (int) Database::fetchColumn(
        "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_deleted_receiver = 0 AND sender_id != ?",
        [$userId, $userId]
    );
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount(int $userId): int {
    return (int) Database::fetchColumn(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
}

/**
 * Get friend count
 */
function getFriendCount(int $userId): int {
    return (int) Database::fetchColumn(
        "SELECT COUNT(*) FROM friendships WHERE user_id = ?",
        [$userId]
    );
}

/**
 * Check if two users are friends
 */
function isFriends(int $userId1, int $userId2): bool {
    $count = (int) Database::fetchColumn(
        "SELECT COUNT(*) FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)",
        [$userId1, $userId2, $userId2, $userId1]
    );
    return $count > 0;
}

/**
 * Check if user has sent a friend request
 */
function getPendingFriendRequest(int $userId1, int $userId2): ?array {
    return Database::fetchOne(
        "SELECT id, sender_id, receiver_id, status, created_at FROM friend_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'pending'",
        [$userId1, $userId2, $userId2, $userId1]
    );
}

/**
 * Check if user is blocked
 */
function isBlocked(int $userId, int $targetId): bool {
    $count = (int) Database::fetchColumn(
        "SELECT COUNT(*) FROM user_blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)",
        [$userId, $targetId, $targetId, $userId]
    );
    return $count > 0;
}

/**
 * Create notification
 */
function createNotification(int $userId, string $type, string $title, string $message = null, int $referenceId = null, int $senderId = null): int {
    return Database::insert('notifications', [
        'user_id' => $userId,
        'sender_id' => $senderId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'reference_id' => $referenceId,
    ]);
}

/**
 * Get typing indicators for a user
 */
function getTypingIndicators(int $userId, int $targetId, bool $isGroup = false, int $groupId = null): array {
    $params = [$userId, $targetId, $isGroup ? 1 : 0];
    if ($isGroup && $groupId) {
        $params[] = $groupId;
        return Database::fetchAll(
            "SELECT u.id, u.username, u.avatar FROM typing_indicators t JOIN users u ON t.user_id = u.id WHERE t.target_id = ? AND t.user_id != ? AND t.is_group = ? AND t.group_id = ? AND t.created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            $params
        );
    }
    return Database::fetchAll(
        "SELECT u.id, u.username, u.avatar FROM typing_indicators t JOIN users u ON t.user_id = u.id WHERE t.target_id = ? AND t.user_id != ? AND t.is_group = 0 AND t.created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        $params
    );
}

/**
 * Clear old typing indicators
 */
function clearOldTypingIndicators(): void {
    Database::query("DELETE FROM typing_indicators WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)", [TYPING_TIMEOUT]);
}

/**
 * Get group member role
 */
function getGroupRole(int $groupId, int $userId): ?string {
    return Database::fetchColumn(
        "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?",
        [$groupId, $userId]
    );
}

/**
 * Check if user is in group
 */
function isGroupMember(int $groupId, int $userId): bool {
    return Database::fetchColumn(
        "SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?",
        [$groupId, $userId]
    ) > 0;
}

/**
 * Get last message for a conversation
 */
function getLastMessage(int $userId1, int $userId2): ?array {
    return Database::fetchOne(
        "SELECT m.*, u.username, u.avatar FROM messages m JOIN users u ON m.sender_id = u.id WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) AND m.is_deleted_sender = 0 AND m.is_deleted_receiver = 0 ORDER BY m.created_at DESC LIMIT 1",
        [$userId1, $userId2, $userId2, $userId1]
    );
}

/**
 * Get last group message
 */
function getLastGroupMessage(int $groupId): ?array {
    return Database::fetchOne(
        "SELECT gm.*, u.username, u.avatar FROM group_messages gm JOIN users u ON gm.sender_id = u.id WHERE gm.group_id = ? AND gm.is_deleted = 0 ORDER BY gm.created_at DESC LIMIT 1",
        [$groupId]
    );
}

/**
 * Get conversation partners for a user - FIXED: remove duplicates
 */
function getConversations(int $userId): array {
    return Database::fetchAll(
        "SELECT DISTINCT u.id, u.username, u.avatar, u.status, u.last_seen, u.status_message,
            (SELECT COUNT(*) FROM messages m WHERE m.receiver_id = u.id AND m.sender_id = ? AND m.is_deleted_receiver = 0 AND m.is_deleted_sender = 0) as unread,
            (SELECT body FROM messages m WHERE (m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = ? AND m.receiver_id = u.id) AND m.is_deleted_sender = 0 AND m.is_deleted_receiver = 0 ORDER BY m.created_at DESC LIMIT 1) as last_msg,
            (SELECT created_at FROM messages m WHERE (m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = ? AND m.receiver_id = u.id) AND m.is_deleted_sender = 0 AND m.is_deleted_receiver = 0 ORDER BY m.created_at DESC LIMIT 1) as last_msg_time
         FROM users u
         JOIN friendships f ON (f.user_id = ? AND f.friend_id = u.id) OR (f.user_id = u.id AND f.friend_id = ?)
         WHERE u.id != ? AND u.is_active = 1
         ORDER BY last_msg_time DESC",
        [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]
    );
}

/**
 * Get user groups
 */
function getUserGroups(int $userId): array {
    return Database::fetchAll(
        "SELECT gc.*, gm.role,
            (SELECT COUNT(*) FROM group_messages gm WHERE gm.group_id = gc.id AND gm.is_deleted = 0) as message_count,
            (SELECT body FROM group_messages gm WHERE gm.group_id = gc.id AND gm.is_deleted = 0 ORDER BY gm.created_at DESC LIMIT 1) as last_msg,
            (SELECT created_at FROM group_messages gm WHERE gm.group_id = gc.id AND gm.is_deleted = 0 ORDER BY gm.created_at DESC LIMIT 1) as last_msg_time
         FROM group_chats gc
         JOIN group_members gm ON gm.group_id = gc.id AND gm.user_id = ?
         ORDER BY last_msg_time DESC",
        [$userId]
    );
}

/**
 * Get group members
 */
function getGroupMembers(int $groupId): array {
    return Database::fetchAll(
        "SELECT u.id, u.username, u.avatar, u.status, u.last_seen, gm.role FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? ORDER BY FIELD(gm.role,'owner','admin','member'), u.username",
        [$groupId]
    );
}
