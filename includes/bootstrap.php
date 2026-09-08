<?php

/**
 * Bootstrap — included at the top of every page.
 *
 * Handles:
 *  - Secure session configuration & start
 *  - Database connection
 *  - Auto-initialization of the database on first run
 *  - Auth helpers available globally
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/init_db.php';
require_once __DIR__ . '/auth.php';

// Configure secure session parameters before starting
configureSession();
session_start();

// Auto-initialize database tables on first request
$dbPath = __DIR__ . '/../database/books.db';
if (!file_exists($dbPath) || filesize($dbPath) === 0) {
    initDatabase();
} else {
    $pdo = getDB();
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='books'");
    if ($check->fetchColumn() === false) {
        initDatabase();
    }
}
