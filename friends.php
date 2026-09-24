<?php
/**
 * WebConnect - Friends Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$title = 'Friends';
$additionalScripts = ['/assets/js/friends.js'];
$userId = getCurrentUserId();

// Load friends
$friends = Database::fetchAll(
    "SELECT u.id, u.username, u.avatar, u.status, u.last_seen FROM friendships f JOIN users u ON (f.friend_id = u.id AND f.user_id = ?) WHERE u.is_active = 1 ORDER BY u.username",
    [$userId]
);

// Pending sent
$pendingSent = Database::fetchAll(
    "SELECT u.id, u.username, u.avatar, u.status, fr.created_at FROM friend_requests fr JOIN users u ON fr.receiver_id = u.id WHERE fr.sender_id = ? AND fr.status = 'pending' ORDER BY fr.created_at DESC",
    [$userId]
);

// Pending received
$pendingReceived = Database::fetchAll(
    "SELECT u.id, u.username, u.avatar, u.status, fr.created_at FROM friend_requests fr JOIN users u ON fr.sender_id = u.id WHERE fr.receiver_id = ? AND fr.status = 'pending' ORDER BY fr.created_at DESC",
    [$userId]
);

// Blocked users
$blocked = Database::fetchAll(
    "SELECT u.id, u.username, u.avatar, u.status FROM user_blocks ub JOIN users u ON ub.blocked_id = u.id WHERE ub.blocker_id = ? ORDER BY u.username",
    [$userId]
);
?>

<div class="container py-4">
    <h4 class="mb-4"><i class="fas fa-user-friends me-2 text-primary"></i>Friends</h4>

    <div class="row">
        <!-- Search -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-search me-2"></i>Search Users</div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search-user" placeholder="Username or email...">
                    </div>
                    <div id="search-results"></div>
                </div>
            </div>
        </div>

        <!-- Friend Lists -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs friend-tabs" id="friendTabs">
                        <li class="nav-item">
                            <a class="nav-link friend-tab active" href="#" data-target="panel-friends">
                                <i class="fas fa-users me-1"></i>Friends <span class="badge bg-primary rounded-pill"><?php echo count($friends); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link friend-tab" href="#" data-target="panel-pending-sent">
                                <i class="fas fa-clock me-1"></i>Sent <span class="badge bg-warning rounded-pill"><?php echo count($pendingSent); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link friend-tab" href="#" data-target="panel-pending-received">
                                <i class="fas fa-bell me-1"></i>Received <span class="badge bg-info rounded-pill"><?php echo count($pendingReceived); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link friend-tab" href="#" data-target="panel-blocked">
                                <i class="fas fa-ban me-1"></i>Blocked <span class="badge bg-danger rounded-pill"><?php echo count($blocked); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <!-- Friends Panel -->
                    <div id="panel-friends" class="friend-panel">
                        <?php if (count($friends) > 0): ?>
                            <?php foreach ($friends as $friend): ?>
                            <div class="friend-card">
                                <a href="<?php echo APP_URL; ?>/chat.php?id=<?php echo (int)$friend['id']; ?>" class="d-flex align-items-center gap-3 text-decoration-none text-dark w-100">
                                    <?php echo getUserAvatar((int)$friend['id'], 45); ?>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo e($friend['username']); ?></div>
                                        <div class="small text-muted"><?php echo getStatusBadge($friend['status'], $friend['last_seen']); ?>
                                            <?php if ($friend['last_seen']): ?>Last seen <?php echo timeAgo($friend['last_seen']); ?><?php endif; ?></div>
                                    </div>
                                    <div class="friend-actions">
                                        <a href="<?php echo APP_URL; ?>/chat.php?id=<?php echo (int)$friend['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Message">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeFriend(<?php echo (int)$friend['id']; ?>)" title="Remove Friend">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="blockUser(<?php echo (int)$friend['id']; ?>)" title="Block">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-friends fa-2x mb-2"></i>
                                <p>No friends yet. Search for users to add!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Sent Panel -->
                    <div id="panel-pending-sent" class="friend-panel d-none">
                        <?php if (count($pendingSent) > 0): ?>
                            <?php foreach ($pendingSent as $p): ?>
                            <div class="friend-card">
                                <div class="d-flex align-items-center gap-3 w-100">
                                    <?php echo getUserAvatar((int)$p['id'], 45); ?>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo e($p['username']); ?></div>
                                        <div class="small text-muted">Sent <?php echo timeAgo($p['created_at']); ?></div>
                                    </div>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><p>No pending sent requests</p></div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Received Panel -->
                    <div id="panel-pending-received" class="friend-panel d-none">
                        <?php if (count($pendingReceived) > 0): ?>
                            <?php foreach ($pendingReceived as $p): ?>
                            <div class="friend-card">
                                <div class="d-flex align-items-center gap-3 w-100">
                                    <?php echo getUserAvatar((int)$p['id'], 45); ?>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo e($p['username']); ?></div>
                                        <div class="small text-muted">Requested <?php echo timeAgo($p['created_at']); ?></div>
                                    </div>
                                    <div class="friend-actions">
                                        <button class="btn btn-sm btn-success" onclick="respondFriend(<?php echo (int)$p['id']; ?>, 'accept')"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-danger" onclick="respondFriend(<?php echo (int)$p['id']; ?>, 'reject')"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><p>No pending received requests</p></div>
                        <?php endif; ?>
                    </div>

                    <!-- Blocked Panel -->
                    <div id="panel-blocked" class="friend-panel d-none">
                        <?php if (count($blocked) > 0): ?>
                            <?php foreach ($blocked as $b): ?>
                            <div class="friend-card">
                                <div class="d-flex align-items-center gap-3 w-100">
                                    <?php echo getUserAvatar((int)$b['id'], 45); ?>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo e($b['username']); ?></div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="unblockUser(<?php echo (int)$b['id']; ?>)">
                                        <i class="fas fa-unlock me-1"></i>Unblock
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><p>No blocked users</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = '<?php echo APP_URL; ?>/api/';
</script>

<?php require_once 'includes/layout_end.php'; ?>
