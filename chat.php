<?php
/**
 * WebConnect - Chat Page
 */
require_once 'includes/layout_start.php';
requireLogin();

$userId = getCurrentUserId();
$title = 'Chat';
$additionalScripts = ['/assets/js/chat.js'];

// Get URL parameter for direct chat
$chatId = (int) ($_GET['id'] ?? 0);
$chatType = $_GET['type'] ?? 'private';
?>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="chat-sidebar" id="chat-sidebar">
        <div class="sidebar-header">
            <h5><i class="fas fa-comments me-2"></i>Messages</h5>
            <div class="sidebar-search">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm" placeholder="Search..." id="search-conversations">
            </div>
        </div>
        <div class="sidebar-tabs">
            <a href="#" class="nav-link active" data-tab="private">Private</a>
            <a href="#" class="nav-link" data-tab="groups">Groups</a>
        </div>
        <div class="conversation-list" id="conversation-list">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="conversation-list d-none" id="group-list">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="p-2 border-top">
            <a href="<?php echo APP_URL; ?>/groups.php" class="btn btn-sm btn-outline-primary w-100">
                <i class="fas fa-plus me-1"></i>New Group
            </a>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main chat-hidden" id="chat-main">
        <!-- Chat Header -->
        <div class="chat-header">
            <button class="btn btn-sm btn-light d-lg-none" id="chat-back-btn">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div id="chat-header-avatar" class="flex-shrink-0"></div>
            <div class="flex-grow-1">
                <div id="chat-header-name" class="user-name"></div>
                <div id="chat-header-status" class="user-status text-muted small"></div>
            </div>
            <div class="chat-header-actions d-flex">
                <button class="btn btn-light btn-icon" onclick="window.chatApp?.startVoiceCall(<?php echo $chatId; ?>)" title="Voice Call">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="btn btn-light btn-icon" onclick="window.chatApp?.startVideoCall(<?php echo $chatId; ?>)" title="Video Call">
                    <i class="fas fa-video"></i>
                </button>
                <button class="btn btn-light btn-icon" id="toggle-info" title="Info">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="messages-area" id="messages-area">
            <div class="empty-state">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <p>Select a conversation to start chatting</p>
            </div>
        </div>

        <!-- Reply Bar -->
        <div class="reply-bar d-none" id="reply-bar">
            <i class="fas fa-reply text-primary"></i>
            <span class="reply-text" id="reply-text"></span>
            <button class="btn-close-reply" id="reply-cancel"><i class="fas fa-times"></i></button>
        </div>

        <!-- Input Area -->
        <div class="chat-input-area">
            <div class="position-relative" id="emoji-picker">
                <button class="input-action-btn btn-attach" type="button" title="Emoji">
                    <i class="fas fa-smile"></i>
                </button>
            </div>
            <textarea class="form-control" id="message-input" placeholder="Type a message..." rows="1"></textarea>
            <input type="file" id="attach-input" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,application/zip">
            <button class="input-action-btn btn-attach" type="button" id="attach-btn" title="Attach File">
                <i class="fas fa-paperclip"></i>
            </button>
            <button class="input-action-btn btn-voice" type="button" id="voice-btn" title="Voice Message">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="input-action-btn btn-send" type="button" id="send-btn" title="Send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

        <!-- Voice Recorder Overlay -->
        <div class="voice-recorder-overlay d-none" id="voice-recorder">
            <div class="voice-recorder-content">
                <div class="voice-timer" id="voice-timer">0:00</div>
                <button class="btn btn-danger" id="voice-cancel"><i class="fas fa-times"></i> Cancel</button>
                <button class="btn btn-success" id="voice-send"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="chat-info-panel" id="chat-info-panel" style="display:none;">
        <div class="info-panel-section">
            <h6>Media & Files</h6>
            <div class="media-grid" id="media-grid">
                <div class="text-muted small p-2">No media shared yet</div>
            </div>
        </div>
        <div class="info-panel-section">
            <h6>Members</h6>
            <div id="members-list"></div>
        </div>
    </div>
</div>

<!-- Call Overlay -->
<div class="call-overlay d-none" id="call-overlay">
    <div class="call-video-container" id="call-video-container" style="display:none;">
        <video id="remote-video" autoplay playsinline></video>
        <div class="call-local-video">
            <video id="local-video" autoplay playsinline muted></video>
        </div>
    </div>
    <div class="call-avatar-large" id="call-avatar">
        <i class="fas fa-user"></i>
    </div>
    <h3 id="caller-name" class="text-white mb-2"></h3>
    <p id="call-status" class="text-white-50"></p>
    <div class="call-controls">
        <button class="call-btn mute" id="mute-btn" title="Mute"><i class="fas fa-microphone"></i></button>
        <button class="call-btn speaker" id="camera-btn" title="Camera" style="display:none;"><i class="fas fa-video"></i></button>
        <button class="call-btn end-call" id="end-call-btn" title="End Call"><i class="fas fa-phone-slash"></i></button>
    </div>
    <div class="call-controls" id="incoming-call-controls" style="display:none;">
        <button class="call-btn accept-call" id="accept-call-btn" title="Accept"><i class="fas fa-phone"></i></button>
        <button class="call-btn end-call" id="reject-call-btn" title="Reject"><i class="fas fa-phone-slash"></i></button>
    </div>
</div>

<script>
const CURRENT_USER_ID = <?php echo $userId; ?>;
const DEFAULT_CHAT_ID = <?php echo $chatId; ?>;
const DEFAULT_CHAT_TYPE = '<?php echo $chatType; ?>';
</script>

<?php require_once 'includes/layout_end.php'; ?>
