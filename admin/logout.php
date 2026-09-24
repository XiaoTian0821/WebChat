<?php
/**
 * WebConnect - Admin Logout
 */
require_once '../includes/bootstrap.php';
logoutAdmin();
redirect(APP_URL . '/admin/login.php');
