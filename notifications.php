<?php
/**
 * WebConnect - Notifications Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$title = 'Notifications';
$additionalScripts = ['/assets/js/notifications.js'];
$userId = getCurrentUserId();

// Get notifications
$notifications = Database::fetchAll(
    "SELECT n.*, u.username, u.avatar
     FROM notifications n
     LEFT JOIN users u ON n.sender_id = u.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC LIMIT 100",
    [$userId]
);

// Get unread count
$unreadCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-bell me-2 text-primary"></i>Notifications</h4>
        <div>
            <span class="badge bg-primary me-2"><?php echo $unreadCount; ?> unread</span>
            <button class="btn btn-sm btn-outline-primary me-2" id="mark-all-read">
                <i class="fas fa-check-double me-1"></i>Mark All Read
            </button>
            <button class="btn btn-sm btn-outline-danger" id="delete-all-notif">
                <i class="fas fa-trash me-1"></i>Delete All
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $n): ?>
                <a href="<?php echo getNotificationLink($n); ?><?php echo ($n['type'] === 'incoming_call' && $n['reference_id']) ? '&call_id=' . $n['reference_id'] : ''; ?>" class="notification-item list-group-item list-group-item-action <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon me-3 <?php echo getNotificationIconColor($n['type']); ?>">
                            <i class="fas <?php echo getNotificationIcon($n['type']); ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?php echo e($n['title']); ?></div>
                            <div class="small text-muted"><?php echo e($n['message'] ?? ''); ?></div>
                            <div class="small text-muted mt-1">
                                <?php if ($n['username']): ?>
                                    <i class="fas fa-user me-1"></i><?php echo e($n['username']); ?>
                                <?php endif; ?>
                                <span class="ms-2"><i class="fas fa-clock me-1"></i><?php echo timeAgo($n['created_at']); ?></span>
                            </div>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <span class="badge bg-primary rounded-pill ms-2"></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state p-5 text-center">
                    <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                    <p class="text-muted">No notifications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function getNotificationLink($n) {
    switch ($n['type']) {
        case 'friend_request':
        case 'friend_accepted':
            return APP_URL . '/friends.php';
        case 'new_message':
            return APP_URL . '/chat.php';
        case 'group_message':
        case 'group_invitation':
            return APP_URL . '/groups.php';
        case 'incoming_call':
        case 'call_missed':
        case 'call_accepted':
        case 'call_rejected':
        case 'call_ended':
            return APP_URL . '/chat.php';
        case 'forum_comment':
        case 'forum_like':
            return APP_URL . '/forums.php';
        default:
            return '#';
    }
}

function getNotificationIcon($type) {
    $icons = [
        'friend_request' => 'fa-user-plus',
        'friend_accepted' => 'fa-check-circle',
        'new_message' => 'fa-comment',
        'group_invitation' => 'fa-users',
        'group_message' => 'fa-comments',
        'incoming_call' => 'fa-phone',
        'call_missed' => 'fa-phone-slash',
        'call_accepted' => 'fa-phone',
        'call_rejected' => 'fa-phone-slash',
        'call_ended' => 'fa-phone',
        'reaction' => 'fa-thumbs-up',
        'forum_comment' => 'fa-comment-dots',
        'forum_like' => 'fa-heart'
    ];
    return $icons[$type] ?? 'fa-bell';
}

function getNotificationIconColor($type) {
    $colors = [
        'friend_request' => 'bg-warning text-white',
        'friend_accepted' => 'bg-success text-white',
        'new_message' => 'bg-primary text-white',
        'group_invitation' => 'bg-info text-white',
        'group_message' => 'bg-primary text-white',
        'incoming_call' => 'bg-danger text-white',
        'call_missed' => 'bg-secondary text-white',
        'call_accepted' => 'bg-success text-white',
        'call_rejected' => 'bg-danger text-white',
        'call_ended' => 'bg-secondary text-white',
        'reaction' => 'bg-warning text-white',
        'forum_comment' => 'bg-info text-white',
        'forum_like' => 'bg-danger text-white'
    ];
    return $colors[$type] ?? 'bg-secondary text-white';
}
?>

<script>
// Mark all read
document.getElementById('mark-all-read')?.addEventListener('click', async () => {
    const res = await fetch('/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_all_read' })
    });
    const data = await res.json();
    if (data.success) {
        location.reload();
    }
});

// Delete all notifications
document.getElementById('delete-all-notif')?.addEventListener('click', async () => {
    if (!confirm('Are you sure you want to delete all notifications?')) return;
    
    const res = await fetch('/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_all' })
    });
    const data = await res.json();
    if (data.success) {
        alert('Deleted ' + data.data + ' notifications');
        location.reload();
    }
});
</script>

<?php require_once 'includes/layout_end.php'; ?>
