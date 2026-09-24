<?php
/**
 * WebConnect - Calls API (WebRTC Signalling)
 * Handles voice/video call creation, signaling, and status management.
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    /**
     * Create a new call
     * POST: action=create, target_id, call_type
     */
    case 'create':
        requirePOST();
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $callType = sanitize($_POST['call_type'] ?? 'voice');

        // Validate call permissions
        if ($targetId === $userId) {
            jsonResponse(['success' => false, 'message' => 'Cannot call yourself'], 400);
        }
        if (!isFriends($userId, $targetId)) {
            jsonResponse(['success' => false, 'message' => 'Can only call friends'], 403);
        }
        if (isBlocked($userId, $targetId) || isBlocked($targetId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Cannot call blocked user'], 403);
        }
        $targetUser = Database::fetchOne("SELECT is_active FROM users WHERE id = ?", [$targetId]);
        if (!$targetUser || !$targetUser['is_active']) {
            jsonResponse(['success' => false, 'message' => 'User is not available'], 404);
        }

        // Create call session
        $callId = Database::insert('voice_call_sessions', [
            'caller_id' => $userId,
            'receiver_id' => $targetId,
            'call_type' => $callType,
            'status' => 'ringing',
        ]);

        // Get caller username for notification
        $caller = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$userId]);
        $callerName = $caller['username'] ?? 'Someone';

        // Notify receiver
        createNotification($targetId, 'incoming_call', 'Incoming ' . ucfirst($callType) . ' Call', 
            'You have an incoming ' . $callType . ' call from ' . $callerName, $callId, $userId);

        jsonResponse(['success' => true, 'data' => ['call_id' => $callId]]);
        break;

    /**
     * Exchange WebRTC signals (offer, answer, ice_candidate)
     * POST: action=signal, call_id, type, data
     */
    case 'signal':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        $type = sanitize($_POST['type'] ?? '');
        $data = $_POST['data'] ?? '';

        // Verify user is part of the call
        $call = Database::fetchOne("SELECT caller_id, receiver_id FROM voice_call_sessions WHERE id = ?", [$callId]);
        if (!$call || ($call['caller_id'] != $userId && $call['receiver_id'] != $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Store signal data as raw string
        Database::insert('voice_call_signals', [
            'call_id' => $callId,
            'sender_id' => $userId,
            'type' => $type,
            'signal_data' => $data,
        ]);
        jsonResponse(['success' => true]);
        break;

    /**
     * Poll for new signals from the other party
     * GET: action=poll_signal, call_id, sender_id
     */
    case 'poll_signal':
        $callId = (int) ($_GET['call_id'] ?? 0);
        $senderId = (int) ($_GET['sender_id'] ?? 0);

        $call = Database::fetchOne("SELECT caller_id, receiver_id FROM voice_call_sessions WHERE id = ?", [$callId]);
        if (!$call || ($call['caller_id'] != $userId && $call['receiver_id'] != $userId)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get signals from the other party
        $otherId = $call['caller_id'] == $userId ? $call['receiver_id'] : $call['caller_id'];
        $signal = Database::fetchOne(
            "SELECT id, type, signal_data, created_at FROM voice_call_signals WHERE call_id = ? AND sender_id = ? ORDER BY id DESC LIMIT 1",
            [$callId, $otherId]
        );

        if ($signal) {
            jsonResponse(['success' => true, 'data' => $signal]);
        } else {
            jsonResponse(['success' => false, 'data' => null]);
        }
        break;

    /**
     * Accept an incoming call
     * POST: action=accept, call_id
     */
    case 'accept':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        
        // Get call details
        $call = Database::fetchOne("SELECT receiver_id, caller_id, call_type FROM voice_call_sessions WHERE id = ? AND status = 'ringing'", [$callId]);
        if (!$call) {
            jsonResponse(['success' => false, 'message' => 'Call not found or already answered'], 404);
        }
        if ($call['receiver_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized - you are not the receiver'], 403);
        }
        
        // Update call status
        Database::update('voice_call_sessions', [
            'status' => 'accepted',
            'started_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$callId]);
        
        // Notify caller that call was accepted
        $receiver = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$userId]);
        createNotification($call['caller_id'], 'call_accepted', 'Call Answered', 
            'Your ' . ucfirst($call['call_type']) . ' call was answered by ' . $receiver['username'], $callId, $userId);
        
        jsonResponse(['success' => true]);
        break;

    /**
     * Reject an incoming call
     * POST: action=reject, call_id
     */
    case 'reject':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        $call = Database::fetchOne("SELECT caller_id, receiver_id FROM voice_call_sessions WHERE id = ?", [$callId]);
        if (!$call) {
            jsonResponse(['success' => false, 'message' => 'Call not found'], 404);
        }
        
        // Update call status
        Database::update('voice_call_sessions', [
            'status' => 'rejected',
            'ended_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$callId]);
        
        // Notify caller
        $receiver = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$userId]);
        createNotification($call['caller_id'], 'call_rejected', 'Call Rejected', 
            'Your call was rejected by ' . $receiver['username'], $callId, $userId);
        
        jsonResponse(['success' => true]);
        break;

    /**
     * End a call
     * POST: action=end, call_id
     */
    case 'end':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        $call = Database::fetchOne("SELECT caller_id, receiver_id, call_type, started_at FROM voice_call_sessions WHERE id = ?", [$callId]);
        if (!$call) {
            jsonResponse(['success' => false, 'message' => 'Call not found'], 404);
        }
        if ($call['caller_id'] != $userId && $call['receiver_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Calculate duration
        $startedAt = strtotime($call['started_at']);
        $duration = $startedAt ? time() - $startedAt : null;
        
        // Update call status
        Database::update('voice_call_sessions', [
            'status' => 'ended',
            'ended_at' => date('Y-m-d H:i:s'),
            'duration' => $duration,
        ], 'id = ?', [$callId]);
        
        // Notify the other party
        $otherId = $call['caller_id'] == $userId ? $call['receiver_id'] : $call['caller_id'];
        $caller = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$otherId]);
        createNotification($otherId, 'call_ended', 'Call Ended', 
            'Your ' . ucfirst($call['call_type']) . ' call with ' . $caller['username'] . ' ended', $callId, $userId);
        
        jsonResponse(['success' => true]);
        break;

    /**
     * Get call status
     * GET: action=get_status, call_id
     */
    case 'get_status':
        $callId = (int) ($_GET['call_id'] ?? 0);
        $call = Database::fetchOne(
            "SELECT c.*, 
                    (SELECT username FROM users WHERE id = c.caller_id) as caller_name,
                    (SELECT username FROM users WHERE id = c.receiver_id) as receiver_name
             FROM voice_call_sessions c 
             WHERE c.id = ? AND (c.caller_id = ? OR c.receiver_id = ?)",
            [$callId, $userId, $userId]
        );
        if (!$call) {
            jsonResponse(['success' => false, 'message' => 'Call not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $call]);
        break;

    /**
     * Get the latest offer SDP
     * GET: action=get_offer, call_id
     */
    case 'get_offer':
        $callId = (int) ($_GET['call_id'] ?? 0);
        $signal = Database::fetchOne(
            "SELECT signal_data FROM voice_call_signals WHERE call_id = ? AND type = 'offer' ORDER BY id DESC LIMIT 1",
            [$callId]
        );
        if (!$signal) {
            jsonResponse(['success' => false, 'message' => 'Offer not found'], 404);
        }
        // Return raw SDP string
        header('Content-Type: text/plain');
        echo $signal['signal_data'];
        exit;
        break;

    /**
     * Get incoming calls for current user
     * GET: action=get_incoming
     */
    case 'get_incoming':
        $call = Database::fetchOne(
            "SELECT c.*, 
                    (SELECT username FROM users WHERE id = c.caller_id) as caller_name
             FROM voice_call_sessions c 
             WHERE c.receiver_id = ? AND c.status = 'ringing'
             ORDER BY c.created_at DESC LIMIT 1",
            [$userId]
        );
        if ($call) {
            jsonResponse(['success' => true, 'data' => $call]);
        } else {
            jsonResponse(['success' => false, 'data' => null]);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
?>
