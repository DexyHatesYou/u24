<?php
/**
 * Quick DB utility — run via: php -S localhost:8000 → visit /dbutil.php?action=...
 *
 * Actions:
 *   ?action=info          — Show tables and row counts
 *   ?action=books         — List all books
 *   ?action=users         — List users (password hashes hidden)
 *   ?action=reset_password&new=YOUR_NEW_PASSWORD  — Change admin password
 *
 * DELETE THIS FILE before deploying to production!
 */

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo    = getDB();
$action = $_GET['action'] ?? 'info';

switch ($action) {
    case 'info':
        echo "=== DATABASE INFO ===\n\n";
        echo "File: database/books.db\n";
        echo "Books:  " . $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn() . " rows\n";
        echo "Users:  " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . " rows\n";
        echo "\nAvailable actions:\n";
        echo "  ?action=info\n";
        echo "  ?action=books\n";
        echo "  ?action=users\n";
        echo "  ?action=reset_password&new=YOUR_NEW_PASSWORD\n";
        break;

    case 'books':
        echo "=== ALL BOOKS ===\n\n";
        $books = $pdo->query("SELECT * FROM books ORDER BY id")->fetchAll();
        if (empty($books)) {
            echo "(empty — no books yet)\n";
        }
        foreach ($books as $b) {
            echo "#{$b['id']} | {$b['title']} | {$b['author']} | {$b['release_year']} | rating: {$b['rating']}\n";
        }
        break;

    case 'users':
        echo "=== USERS ===\n\n";
        $users = $pdo->query("SELECT id, username, created_at FROM users")->fetchAll();
        foreach ($users as $u) {
            echo "#{$u['id']} | {$u['username']} | created: {$u['created_at']}\n";
        }
        break;

    case 'reset_password':
        $newPass = $_GET['new'] ?? '';
        if (strlen($newPass) < 4) {
            echo "ERROR: Password must be at least 4 characters.\n";
            echo "Usage: ?action=reset_password&new=YOUR_NEW_PASSWORD\n";
            break;
        }
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$hash]);
        echo "Admin password changed successfully.\n";
        echo "New password: {$newPass}\n";
        break;

    default:
        echo "Unknown action. Use ?action=info\n";
}
