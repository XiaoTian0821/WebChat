<?php
/**
 * WebConnect - Upload API
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? '';

if ($action === 'send') {
    $chatId = (int) ($_POST['chat_id'] ?? 0);
    $chatType = $_POST['chat_type'] ?? 'private';
    $msgType = sanitize($_POST['type'] ?? 'image'); // image or voice
    $file = $_FILES['file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => 'File upload error']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($file['tmp_name']);
    $randomName = generateRandomFilename($ext);

    if ($msgType === 'voice') {
        $allowedMime = ALLOWED_VOICE_MIME;
        $allowedExt = ALLOWED_VOICE_EXT;
        $destPath = VOICE_PATH;
        $mediaType = 'voice';
    } else {
        $allowedMime = ALLOWED_IMAGE_MIME;
        $allowedExt = ALLOWED_IMAGE_EXT;
        $destPath = IMAGE_PATH;
        $mediaType = 'image';
    }

    if (!in_array($mime, $allowedMime) || !in_array($ext, $allowedExt)) {
        jsonResponse(['success' => false, 'message' => 'Invalid file type']);
    }

    $maxSize = $msgType === 'voice' ? MAX_VOICE_SIZE : MAX_IMAGE_SIZE;
    if ($file['size'] > $maxSize) {
        jsonResponse(['success' => false, 'message' => 'File too large']);
    }

    $dest = $destPath . '/' . $randomName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['success' => false, 'message' => 'Failed to save file']);
    }

    $voiceDuration = $msgType === 'voice' ? null : null;
    // For voice, we can get duration from the audio file
    if ($msgType === 'voice') {
        $voiceDuration = getAudioDuration($dest);
    }

    if ($chatType === 'group') {
        if (!isGroupMember($chatId, $userId)) {
            @unlink($dest);
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $msgId = Database::insert('group_messages', [
            'group_id' => $chatId,
            'sender_id' => $userId,
            'type' => $msgType,
            'file_path' => $randomName,
            'file_name' => sanitize($file['name']),
            'mime_type' => $mime,
            'file_size' => $file['size'],
            'voice_duration' => $voiceDuration,
        ]);
        // Notify
        $members = Database::fetchAll("SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?", [$chatId, $userId]);
        foreach ($members as $member) {
            createNotification((int)$member['user_id'], 'group_message', 'New Group Message', 'New media in group ' . $chatId, $msgId, $userId);
        }
        jsonResponse(['success' => true, 'data' => [
            'id' => $msgId,
            'group_id' => $chatId,
            'type' => $msgType,
            'file_path' => $randomName,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'voice_duration' => $voiceDuration,
            'created_at' => date('Y-m-d H:i:s'),
        ]]);
    } else {
        $receiverId = $chatId;
        if (isBlocked($userId, $receiverId) || isBlocked($receiverId, $userId)) {
            @unlink($dest);
            jsonResponse(['success' => false, 'message' => 'Cannot send to blocked user'], 403);
        }
        $msgId = Database::insert('messages', [
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'type' => $msgType,
            'file_path' => $randomName,
            'file_name' => sanitize($file['name']),
            'mime_type' => $mime,
            'file_size' => $file['size'],
            'voice_duration' => $voiceDuration,
        ]);
        createNotification($receiverId, 'new_message', 'New Message', 'You received a ' . $msgType . ' from ' . getCurrentUser()['username'], $msgId, $userId);
        jsonResponse(['success' => true, 'data' => [
            'id' => $msgId,
            'receiver_id' => $receiverId,
            'type' => $msgType,
            'file_path' => $randomName,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'voice_duration' => $voiceDuration,
            'created_at' => date('Y-m-d H:i:s'),
        ]]);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get audio duration using shell (fallback) or getID3
 */
function getAudioDuration(string $filepath): ?float {
    // Use ffprobe if available, otherwise return null
    if (function_exists('shell_exec')) {
        $output = shell_exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . str_replace('"', '\"', $filepath) . '" 2>&1');
        if ($output && is_numeric(trim($output))) {
            return (float) trim($output);
        }
    }
    // Fallback: try PHP extension
    if (function_exists('dio_open')) {
        // Alternative: use getid3 library
    }
    return null;
}
