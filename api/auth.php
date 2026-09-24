<?php
/**
 * WebConnect - Auth API
 */
require_once '../includes/bootstrap.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'check':
        jsonResponse(['success' => isLoggedIn(), 'user' => isLoggedIn() ? getCurrentUser() : null]);
        break;

    case 'login':
        requirePOST();
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Invalid token']);
        }
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($identifier) || empty($password)) {
            jsonResponse(['success' => false, 'message' => 'All fields are required']);
        }
        if (!checkRateLimit('login_api_' . $identifier, 5, 60)) {
            jsonResponse(['success' => false, 'message' => 'Too many attempts. Wait a moment.']);
        }
        $result = loginUser($identifier, $password);
        if ($result === false) {
            jsonResponse(['success' => false, 'message' => 'Invalid credentials']);
        } elseif (is_array($result) && isset($result['error'])) {
            jsonResponse(['success' => false, 'message' => 'Account disabled']);
        } else {
            jsonResponse(['success' => true, 'message' => 'Login successful', 'data' => [
                'user_id' => $result['id'],
                'username' => $result['username'],
            ]]);
        }
        break;

    case 'register':
        requirePOST();
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Invalid token']);
        }
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!isValidUsername($username)) {
            jsonResponse(['success' => false, 'message' => 'Invalid username']);
        }
        if (!isValidEmail($email)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email']);
        }
        if (!isValidPassword($password)) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters']);
        }
        if ($password !== $confirm) {
            jsonResponse(['success' => false, 'message' => 'Passwords do not match']);
        }
        $userId = registerUser($username, $email, $password);
        if ($userId) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            jsonResponse(['success' => true, 'message' => 'Registration successful', 'data' => ['user_id' => $userId]]);
        }
        jsonResponse(['success' => false, 'message' => 'Username or email already exists']);
        break;

    case 'logout':
        logoutUser();
        jsonResponse(['success' => true, 'message' => 'Logged out']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
