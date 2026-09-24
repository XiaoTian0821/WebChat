<?php
/**
 * WebConnect - Authentication Helpers
 */

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Require admin login
 */
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Get current admin ID
 */
function getCurrentAdminId(): int {
    return (int) ($_SESSION['admin_id'] ?? 0);
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return Database::fetchOne(
        "SELECT id, username, email, avatar, status_message, status, last_seen, is_active, created_at FROM users WHERE id = ?",
        [getCurrentUserId()]
    );
}

/**
 * Hash password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Register a new user
 */
function registerUser(string $username, string $email, string $password): int|false {
    // Check duplicates
    $existing = Database::fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    if ($existing) {
        return false;
    }
    $hashed = hashPassword($password);
    return Database::insert('users', [
        'username' => $username,
        'email' => $email,
        'password' => $hashed,
    ]);
}

/**
 * Authenticate user
 */
function loginUser(string $identifier, string $password): array|false {
    $user = Database::fetchOne(
        "SELECT id, username, email, password, is_active FROM users WHERE username = ? OR email = ?",
        [$identifier, $identifier]
    );
    if (!$user || !verifyPassword($password, $user['password'])) {
        return false;
    }
    if (!$user['is_active']) {
        return ['error' => 'account_disabled'];
    }
    // Regenerate session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['logged_in_at'] = time();
    // Update last seen
    Database::update('users', ['last_seen' => date('Y-m-d H:i:s')], 'id = ?', [(int)$user['id']]);
    return $user;
}

/**
 * Authenticate admin
 */
function loginAdmin(string $username, string $password): array|false {
    $admin = Database::fetchOne(
        "SELECT id, username, email, password FROM admins WHERE username = ?",
        [$username]
    );
    if (!$admin || !verifyPassword($password, $admin['password'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    return $admin;
}

/**
 * Logout user
 */
function logoutUser(): void {
    session_destroy();
    setcookie(SESSION_NAME, '', time() - 3600, '/');
}

/**
 * Logout admin
 */
function logoutAdmin(): void {
    session_destroy();
    setcookie(SESSION_NAME, '', time() - 3600, '/admin/');
}
