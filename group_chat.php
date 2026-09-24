<?php
/**
 * WebConnect - Group Chat Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$userId = getCurrentUserId();
$groupId = (int) ($_GET['id'] ?? 0);

if (!$groupId || !isGroupMember($groupId, $userId)) {
    redirect(APP_URL . '/groups.php');
}

$group = Database::fetchOne("SELECT * FROM group_chats WHERE id = ?", [$groupId]);
if (!$group) { redirect(APP_URL . '/groups.php'); }

$members = getGroupMembers($groupId);
$myRole = getGroupRole($groupId, $userId);

$title = e($group['name']);
$additionalScripts = ['/assets/js/chat.js'];
?>

<div class="chat-container">
    <!-- Sidebar (Conversations) -->
    <div class="chat-sidebar" id="chat-sidebar">
        <div class="sidebar-header">
            <h5><i class="fas fa-comments me-2"></i>Chats</h5>
        </div>
        <div class="sidebar-tabs">
            <a href="<?php echo APP_URL; ?>/chat.php" class="nav-link"><i class="fas fa-comment me-1"></i>Private</a>
            <a href="<?php echo APP_URL; ?>/chat.php?type=groups" class="nav-link active"><i class="fas fa-users me-1"></i>Groups</a>
        </div>
        <div class="conversation-list" id="conversation-list">
            <?php foreach (getUserGroups($userId) as $g): ?>
            <a href="<?php echo APP_URL; ?>/group_chat.php?id=<?php echo (int)$g['id']; ?>" class="conversation-item <?php echo (int)$g['id'] === $groupId ? 'active' : ''; ?>" data-id="<?php echo (int)$g['id']; ?>" data-type="group">
                <div class="conv-header">
                    <?php if ($g['avatar'] && file_exists(GROUP_PATH . '/' . $g['avatar'])): ?>
                        <img src="<?php echo APP_URL; ?>/api/media.php?file=<?php echo urlencode($g['avatar']); ?>&type=group" style="width:42px;height:42px;border-radius:10px;object-fit:cover;" class="me-2">
                    <?php else: ?>
                        <div class="avatar avatar-default me-2" style="width:42px;height:42px;border-radius:10px;font-size:16px;"><i class="fas fa-users"></i></div>
                    <?php endif; ?>
                    <div class="conv-name"><?php echo e($g['name']); ?></div>
                    <span class="conv-time ms-auto"><?php echo $g['last_msg_time'] ? formatTime($g['last_msg_time']) : ''; ?></span>
                </div>
                <p class="conv-preview text-muted mb-0"><?php echo e(($g['last_msg'] ?? 'No messages') ? substr($g['last_msg'], 0, 40) . '...' : 'No messages yet'); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Chat -->
    <div class="chat-main" id="chat-main">
        <div class="chat-header">
            <button class="btn btn-sm btn-light d-lg-none" id="chat-back-btn"><i class="fas fa-arrow-left"></i></button>
            <div id="chat-header-avatar" class="flex-shrink-0">
                <?php if ($group['avatar'] && file_exists(GROUP_PATH . '/' . $group['avatar'])): ?>
                    <img src="<?php echo APP_URL; ?>/api/media.php?file=<?php echo urlencode($group['avatar']); ?>&type=group" style="width:38px;height:38px;border-radius:10px;object-fit:cover;">
                <?php else: ?>
                    <div class="avatar avatar-default" style="width:38px;height:38px;border-radius:10px;font-size:16px;"><i class="fas fa-users"></i></div>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1">
                <div id="chat-header-name" class="user-name"><?php echo e($group['name']); ?></div>
                <div id="chat-header-status" class="user-status text-muted small"><?php echo count($members); ?> members</div>
            </div>
            <div class="chat-header-actions d-flex">
                <button class="btn btn-light btn-icon" onclick="window.chatApp?.startGroupVoiceCall(<?php echo $groupId; ?>)" title="Voice Call"><i class="fas fa-phone"></i></button>
                <button class="btn btn-light btn-icon" onclick="window.chatApp?.startGroupVideoCall(<?php echo $groupId; ?>)" title="Video Call"><i class="fas fa-video"></i></button>
                <button class="btn btn-light btn-icon" onclick="window.chatApp?.toggleInfoPanel()" title="Info"><i class="fas fa-info-circle"></i></button>
            </div>
        </div>

        <div class="messages-area" id="messages-area">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading messages...</div>
        </div>

        <div class="reply-bar d-none" id="reply-bar">
            <i class="fas fa-reply text-primary"></i>
            <span class="reply-text" id="reply-text"></span>
            <button class="btn-close-reply" id="reply-cancel"><i class="fas fa-times"></i></button>
        </div>

        <div class="chat-input-area">
            <div class="position-relative" id="emoji-picker">
                <button class="input-action-btn btn-attach" type="button"><i class="fas fa-smile"></i></button>
            </div>
            <textarea class="form-control" id="message-input" placeholder="Type a message..." rows="1"></textarea>
            <input type="file" id="attach-input" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,application/zip">
            <button class="input-action-btn btn-attach" type="button" id="attach-btn"><i class="fas fa-paperclip"></i></button>
            <button class="input-action-btn btn-voice" type="button" id="voice-btn"><i class="fas fa-microphone"></i></button>
            <button class="input-action-btn btn-send" type="button" id="send-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="chat-info-panel" id="chat-info-panel" style="display:none;">
        <div class="info-panel-section">
            <h6>Group Info</h6>
            <p class="small text-muted"><?php echo e($group['description'] ?? 'No description'); ?></p>
            <?php if ($myRole === 'owner'): ?>
            <button class="btn btn-sm btn-outline-danger w-100" onclick="window.chatApp?.deleteGroup(<?php echo $groupId; ?>)">
                <i class="fas fa-trash me-1"></i>Delete Group
            </button>
            <?php elseif ($myRole === 'member'): ?>
            <button class="btn btn-sm btn-outline-danger w-100" onclick="window.chatApp?.leaveGroup(<?php echo $groupId; ?>)">
                <i class="fas fa-sign-out-alt me-1"></i>Leave Group
            </button>
            <?php endif; ?>
        </div>
        <div class="info-panel-section">
            <h6>Members (<?php echo count($members); ?>)</h6>
            <?php foreach ($members as $m): ?>
            <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                <?php echo getUserAvatar((int)$m['user_id'], 32); ?>
                <div class="flex-grow-1 small">
                    <div class="fw-semibold"><?php echo e($m['username']); ?></div>
                    <div class="text-muted"><?php echo getStatusBadge($m['status'], $m['last_seen']); ?></div>
                </div>
                <span class="badge bg-<?php echo $m['role'] === 'owner' ? 'warning' : ($m['role'] === 'admin' ? 'info' : 'secondary'); ?> fs-6"><?php echo e(ucfirst($m['role'])); ?></span>
                <?php if ($myRole === 'owner' && $m['role'] !== 'owner'): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                    <ul class="dropdown-menu">
                        <?php if ($m['role'] !== 'admin'): ?>
                        <li><a class="dropdown-item" href="#" onclick="window.chatApp?.changeMemberRole(<?php echo $groupId; ?>, <?php echo (int)$m['user_id']; ?>, 'admin')">Make Admin</a></li>
                        <?php else: ?>
                        <li><a class="dropdown-item" href="#" onclick="window.chatApp?.changeMemberRole(<?php echo $groupId; ?>, <?php echo (int)$m['user_id']; ?>, 'member')">Remove Admin</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="#" onclick="window.chatApp?.removeGroupMember(<?php echo $groupId; ?>, <?php echo (int)$m['user_id']; ?>)">Remove Member</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($myRole === 'owner' || $myRole === 'admin'): ?>
            <button class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-user-plus me-1"></i>Add Member
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="search-group-member" placeholder="Search users...">
                <div id="group-member-search-results"></div>
            </div>
        </div>
    </div>
</div>

<!-- Call Overlay -->
<div class="call-overlay d-none" id="call-overlay">
    <div class="call-video-container" id="call-video-container" style="display:none;">
        <video id="remote-video" autoplay playsinline></video>
        <div class="call-local-video"><video id="local-video" autoplay playsinline muted></video></div>
    </div>
    <div class="call-avatar-large" id="call-avatar"><i class="fas fa-users"></i></div>
    <h3 id="caller-name" class="text-white mb-2"></h3>
    <p id="call-status" class="text-white-50"></p>
    <div class="call-controls">
        <button class="call-btn mute" id="mute-btn"><i class="fas fa-microphone"></i></button>
        <button class="call-btn speaker" id="camera-btn" style="display:none;"><i class="fas fa-video"></i></button>
        <button class="call-btn end-call" id="end-call-btn"><i class="fas fa-phone-slash"></i></button>
    </div>
</div>

<script>
const CURRENT_USER_ID = <?php echo $userId; ?>;
const CURRENT_GROUP_ID = <?php echo $groupId; ?>;
const CURRENT_CHAT_TYPE = 'group';
const MY_ROLE = '<?php echo e($myRole); ?>';
</script>

<?php require_once 'includes/layout_end.php'; ?>
