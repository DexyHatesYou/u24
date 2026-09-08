<?php

/**
 * Database Connection (PDO + SQLite)
 * 
 * Returns a singleton PDO instance connected to the SQLite database.
 * The database file is stored in /database/books.db relative to project root.
 */

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dbPath = __DIR__ . '/../database/books.db';
        $dbDir  = dirname($dbPath);

        // Ensure the database directory exists
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable WAL mode for better concurrent read performance
        $pdo->exec('PRAGMA journal_mode = WAL');
        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
