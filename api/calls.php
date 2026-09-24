<?php
/**
 * WebConnect - Calls API (WebRTC Signalling) - Fixed
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
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

        $callId = Database::insert('voice_call_sessions', [
            'caller_id' => $userId,
            'receiver_id' => $targetId,
            'call_type' => $callType,
            'status' => 'ringing',
        ]);

        // Notify receiver
        createNotification($targetId, 'incoming_call', 'Incoming ' . ucfirst($callType) . ' Call', 'You have an incoming ' . $callType . ' call from ' . getCurrentUser()['username'], $callId, $userId);

        jsonResponse(['success' => true, 'data' => ['call_id' => $callId]]);
        break;

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

        Database::insert('voice_call_signals', [
            'call_id' => $callId,
            'sender_id' => $userId,
            'type' => $type,
            'signal_data' => $data,
        ]);
        jsonResponse(['success' => true]);
        break;

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

    case 'accept':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        $call = Database::fetchOne("SELECT receiver_id, caller_id, call_type FROM voice_call_sessions WHERE id = ? AND status = 'ringing'", [$callId]);
        if (!$call || $call['receiver_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        Database::update('voice_call_sessions', [
            'status' => 'accepted',
            'started_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$callId]);
        
        // Notify caller that call was accepted
        createNotification($call['caller_id'], 'call_accepted', 'Call Answered', 'Your ' . ucfirst($call['call_type']) . ' call was answered by ' . getCurrentUser()['username'], $callId, $userId);
        
        jsonResponse(['success' => true]);
        break;

    case 'reject':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        $call = Database::fetchOne("SELECT caller_id, receiver_id FROM voice_call_sessions WHERE id = ?", [$callId]);
        if (!$call) { jsonResponse(['success' => false, 'message' => 'Call not found'], 404); }
        Database::update('voice_call_sessions', ['status' => 'rejected', 'ended_at' => date('Y-m-d H:i:s')], 'id = ?', [$callId]);
        // Notify caller
        createNotification($call['caller_id'], 'call_rejected', 'Call Rejected', 'Your call was rejected by ' . getCurrentUser()['username'], $callId, $userId);
        jsonResponse(['success' => true]);
        break;

    case 'end':
        requirePOST();
        $callId = (int) ($_POST['call_id'] ?? 0);
        $call = Database::fetchOne("SELECT caller_id, receiver_id, call_type FROM voice_call_sessions WHERE id = ?", [$callId]);
        if (!$call) { jsonResponse(['success' => false, 'message' => 'Call not found'], 404); }
        if ($call['caller_id'] != $userId && $call['receiver_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        Database::update('voice_call_sessions', [
            'status' => 'ended',
            'ended_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$callId]);
        
        // If the person ending is not the caller, notify caller
        if ($call['receiver_id'] == $userId) {
            createNotification($call['caller_id'], 'call_ended', 'Call Ended', 'Your ' . ucfirst($call['call_type']) . ' call was ended by ' . getCurrentUser()['username'], $callId, $userId);
        }
        
        jsonResponse(['success' => true]);
        break;

    case 'get_status':
        $callId = (int) ($_GET['call_id'] ?? 0);
        $call = Database::fetchOne("SELECT * FROM voice_call_sessions WHERE id = ? AND (caller_id = ? OR receiver_id = ?)", [$callId, $userId, $userId]);
        if (!$call) {
            jsonResponse(['success' => false, 'message' => 'Call not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $call]);
        break;

    case 'get_offer':
        $callId = (int) ($_GET['call_id'] ?? 0);
        $signal = Database::fetchOne("SELECT signal_data FROM voice_call_signals WHERE call_id = ? AND type = 'offer' ORDER BY id DESC LIMIT 1", [$callId]);
        if (!$signal) {
            jsonResponse(['success' => false, 'message' => 'Offer not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $signal['signal_data']]);
        break;

    case 'get_incoming':
        // Get active ringing calls where user is receiver
        $call = Database::fetchOne(
            "SELECT * FROM voice_call_sessions WHERE receiver_id = ? AND status = 'ringing' ORDER BY created_at DESC LIMIT 1",
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
