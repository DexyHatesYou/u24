<?php

/**
 * Bootstrap file — included at the top of every page.
 * 
 * Handles:
 *  - Session start
 *  - Database connection
 *  - Auto-initialization of the database on first run
 */

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/init_db.php';

// Auto-initialize database tables on first request
$dbPath = __DIR__ . '/../database/books.db';
if (!file_exists($dbPath) || filesize($dbPath) === 0) {
    initDatabase();
} else {
    // Ensure tables exist even if the db file exists (e.g. empty file)
    $pdo = getDB();
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='books'");
    if ($check->fetchColumn() === false) {
        initDatabase();
    }
}
