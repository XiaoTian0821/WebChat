<?php
/**
 * WebConnect - Bootstrap
 * Loads all required files.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
normalizeRequestInput();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Clear old typing indicators periodically
if (mt_rand(1, 10) === 1) {
    clearOldTypingIndicators();
}
