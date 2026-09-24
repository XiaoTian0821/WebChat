<?php
/**
 * WebConnect - Logout
 */
require_once 'includes/bootstrap.php';
logoutUser();
redirect(APP_URL . '/login.php');
