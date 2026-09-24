<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/bootstrap.php';

$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<h2>Reset Passwords</h2>";
echo "<p>New hash: $hash</p>";

// Update using raw SQL to avoid parameter binding issues
Database::query("UPDATE users SET password = ? WHERE username IN (?, ?, ?)", [$hash, 'ali', 'meimei', 'charlie']);

echo "<p>✓ Updated passwords for ali, meimei, charlie</p>";

// Verify
$user = Database::fetchOne("SELECT id, username, password FROM users WHERE username = 'ali'");
echo "<p>Stored hash: " . substr($user['password'], 0, 50) . "...</p>";
echo "<p>password_verify: " . (password_verify($password, $user['password']) ? 'TRUE' : 'FALSE') . "</p>";

// Test login
$result = loginUser('ali', $password);
if ($result) {
    echo "<p style='color:green'>✓ Login successful! Session ID: " . session_id() . "</p>";
    echo "<pre>Session: " . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<p style='color:red'>✗ Login failed</p>";
}
?>
