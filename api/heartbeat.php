<?php
/**
 * WebConnect - Presence Heartbeat (called periodically by JavaScript)
 */
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');
$userId = getCurrentUserId();

Database::update('users', ['last_seen' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
jsonResponse(['success' => true]);
