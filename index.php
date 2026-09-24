<?php
/**
 * WebConnect - Home Page
 */
require_once 'includes/layout_start.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="fas fa-comments text-primary me-3"></i>Welcome to <span class="text-primary"><?php echo e(APP_NAME); ?></span>
                </h1>
                <p class="lead text-muted mb-4">
                    A modern web-based communication platform. Chat privately, join groups, make voice & video calls, and connect with friends — all from your browser.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus me-2"></i>Get Started</a>
                    <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-outline-primary btn-lg"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
                    <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/chat.php" class="btn btn-primary btn-lg"><i class="fas fa-comment me-2"></i>Start Chatting</a>
                    <a href="<?php echo APP_URL; ?>/friends.php" class="btn btn-outline-primary btn-lg"><i class="fas fa-user-friends me-2"></i>Friends</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="features-grid mt-5 mt-lg-0">
                    <div class="feature-card">
                        <i class="fas fa-comment-dots fa-2x text-primary mb-3"></i>
                        <h5>Instant Messaging</h5>
                        <p class="text-muted small">Send text, images, files & voice messages in real-time.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-phone-alt fa-2x text-success mb-3"></i>
                        <h5>Voice & Video Calls</h5>
                        <p class="text-muted small">Crystal-clear calls powered by WebRTC technology.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-users fa-2x text-info mb-3"></i>
                        <h5>Group Chats</h5>
                        <p class="text-muted small">Create groups, manage members, and share moments together.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-forum fa-2x text-warning mb-3"></i>
                        <h5>Community Forums</h5>
                        <p class="text-muted small">Discuss topics, share ideas, and connect with the community.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (isLoggedIn()):
    $recentConversations = getConversations((int)$user['id']);
    $recentGroups = getUserGroups((int)$user['id']);
?>
<div class="container my-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-comment me-2 text-primary"></i>Recent Conversations
                </div>
                <div class="card-body p-0">
                    <?php if (count($recentConversations) > 0): ?>
                        <?php foreach (array_slice($recentConversations, 0, 5) as $conv): ?>
                        <a href="chat.php?id=<?php echo (int)$conv['id']; ?>" class="conversation-item d-flex align-items-center p-3 border-bottom text-decoration-none">
                            <div class="me-3"><?php echo getUserAvatar((int)$conv['id'], 45); ?></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?php echo e($conv['username']); ?></div>
                                <div class="small text-muted text-truncate"><?php echo e($conv['last_msg'] ?? 'No messages yet'); ?></div>
                            </div>
                            <?php if ($conv['unread'] > 0): ?>
                                <span class="badge bg-primary rounded-pill"><?php echo (int)$conv['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-comment-slash fa-2x mb-2"></i>
                            <p>No conversations yet. Add friends to start chatting!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="chat.php" class="btn btn-sm btn-outline-primary">View All Chats</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-users me-2 text-info"></i>Your Groups
                </div>
                <div class="card-body p-0">
                    <?php if (count($recentGroups) > 0): ?>
                        <?php foreach (array_slice($recentGroups, 0, 5) as $grp): ?>
                        <a href="group_chat.php?id=<?php echo (int)$grp['id']; ?>" class="d-flex align-items-center p-3 border-bottom text-decoration-none">
                            <div class="me-3">
                                <?php if ($grp['avatar'] && file_exists(GROUP_PATH . '/' . $grp['avatar'])): ?>
                                    <img src="<?php echo APP_URL; ?>/api/media.php?file=<?php echo urlencode($grp['avatar']); ?>&type=group" style="width:45px;height:45px;border-radius:10px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="avatar avatar-default bg-info text-white" style="width:45px;height:45px;border-radius:10px;font-size:18px;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?php echo e($grp['name']); ?></div>
                                <div class="small text-muted text-truncate"><?php echo e($grp['last_msg'] ?? 'No messages yet'); ?></div>
                            </div>
                            <span class="badge bg-info text-dark"><?php echo (int)$grp['role']; ?></span>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <p>You are not in any groups yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="groups.php" class="btn btn-sm btn-outline-info">View All Groups</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/layout_end.php'; ?>
