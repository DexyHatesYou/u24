<?php

/**
 * Database Initialization Script
 * 
 * Creates the required tables and seeds the default admin user.
 * Safe to run multiple times — uses IF NOT EXISTS.
 * 
 * Usage: php includes/init_db.php
 *   or:  require this file from any entry point.
 */

require_once __DIR__ . '/db.php';

function initDatabase(): void
{
    $pdo = getDB();

    // ── Books table ─────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT    NOT NULL,
            author       TEXT    NOT NULL,
            release_year INTEGER NOT NULL,
            annotation   TEXT    DEFAULT '',
            rating       REAL    DEFAULT 0 CHECK(rating >= 0 AND rating <= 5),
            created_at   TEXT    DEFAULT (datetime('now'))
        )
    ");

    // ── Users table (admin accounts) ────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT NOT NULL UNIQUE,
            password   TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now'))
        )
    ");

    // ── Seed default admin if the table is empty ────────────────────
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    if ((int) $count === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:u, :p)");
        $stmt->execute([
            ':u' => 'admin',
            ':p' => $hash,
        ]);
        echo "✔ Default admin user created (admin / admin123)\n";
    }

    echo "✔ Database initialized successfully.\n";
}

// Run when executed directly (php includes/init_db.php)
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    initDatabase();
}
