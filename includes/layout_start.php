<?php
/**
 * WebConnect - Layout Header
 */
require_once __DIR__ . '/bootstrap.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
$unreadMsgs = isLoggedIn() ? getUnreadMessageCount((int)$user['id']) : 0;
$unreadNotifs = isLoggedIn() ? getUnreadNotificationCount((int)$user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-url" content="<?php echo APP_URL; ?>">
    <?php if (isLoggedIn()): ?>
    <meta name="user-id" content="<?php echo (int)$user['id']; ?>">
    <?php endif; ?>
    <title><?php echo e(APP_NAME); ?> — <?php echo e($title ?? 'Home'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/app.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark main-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo APP_URL; ?>/index.php">
            <i class="fas fa-comments me-2"></i><?php echo e(APP_NAME); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/index.php"><i class="fas fa-home me-1"></i> Home</a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'chat' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/chat.php">
                        <i class="fas fa-comment me-1"></i> Chat
                        <?php if ($unreadMsgs > 0): ?><span class="badge bg-danger ms-1"><?php echo $unreadMsgs; ?></span><?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'friends' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/friends.php"><i class="fas fa-user-friends me-1"></i> Friends</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'groups' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/groups.php"><i class="fas fa-users me-1"></i> Groups</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'forums' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/forums.php"><i class="fas fa-forum me-1"></i> Forums</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/notifications.php">
                        <i class="fas fa-bell me-1"></i> Notifications
                        <?php if ($unreadNotifs > 0): ?><span class="badge bg-danger ms-1"><?php echo $unreadNotifs; ?></span><?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo e($user['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL; ?>/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL; ?>/register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="main-content">
