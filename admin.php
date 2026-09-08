<?php
/**
 * Admin Panel — Protected
 */
require_once __DIR__ . '/includes/bootstrap.php';

// Guard — kicks unauthenticated users to login.php
requireAuth();

$pdo       = getDB();
$bookCount = (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — BookShelf</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        (function() {
            var t = localStorage.getItem('bookshelf-theme');
            if (!t) t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
        })();
    </script>
</head>
<body>

<div class="page-wrapper">

    <!-- Header -->
    <header class="site-header">
        <a href="index.php" class="logo">
            <i data-lucide="book-marked"></i>
            Book<span>Shelf</span>
        </a>
        <nav class="header-nav">
            <a href="index.php" class="nav-link">
                <i data-lucide="library"></i> Library
            </a>
            <a href="admin.php" class="nav-link active">
                <i data-lucide="settings"></i> Admin
            </a>
            <span class="nav-user">
                <i data-lucide="user"></i>
                <?= htmlspecialchars($_SESSION['user_name']) ?>
            </span>
            <a href="logout.php" class="nav-link nav-link--logout">
                <i data-lucide="log-out"></i> Logout
            </a>
            <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
                <i data-lucide="sun" class="icon-sun"></i>
                <i data-lucide="moon" class="icon-moon"></i>
            </button>
        </nav>
    </header>

    <!-- Main -->
    <main class="main-content">

        <div class="page-title-row">
            <h1>
                <i data-lucide="settings"></i>
                Admin Panel
            </h1>
        </div>

        <!-- Stats -->
        <div class="admin-stats">
            <div class="stat-card card">
                <i data-lucide="book-open"></i>
                <div class="stat-card__body">
                    <span class="stat-card__value"><?= $bookCount ?></span>
                    <span class="stat-card__label">Books in Collection</span>
                </div>
            </div>
            <div class="stat-card card">
                <i data-lucide="user-check"></i>
                <div class="stat-card__body">
                    <span class="stat-card__value"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="stat-card__label">Logged in as</span>
                </div>
            </div>
        </div>

        <!-- Placeholder sections for Steps 5 & 6 -->
        <div class="admin-sections">
            <div class="admin-section card">
                <h2 class="admin-section__title">
                    <i data-lucide="plus-circle"></i>
                    Add New Book
                </h2>
                <p class="admin-section__desc">Add book form will be built in the next step.</p>
            </div>

            <div class="admin-section card">
                <h2 class="admin-section__title">
                    <i data-lucide="upload"></i>
                    Import from JSON
                </h2>
                <p class="admin-section__desc">JSON import will be built in a later step.</p>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="site-footer">
        &copy; <?= date('Y') ?> BookShelf &mdash; Book Management System
    </footer>

</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
