<?php
/**
 * Test notification deletion
 */
require_once '../includes/bootstrap.php';

// Delete all notifications
$count = Database::delete('notifications', '1=1');
echo "Deleted {$count} notifications\n";

// Verify deletion
$remaining = Database::fetchColumn("SELECT COUNT(*) FROM notifications");
echo "Remaining notifications: {$remaining}\n";
?>
