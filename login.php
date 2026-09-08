<?php
/**
 * Login Page
 */
require_once __DIR__ . '/includes/bootstrap.php';

// Already logged in? Go to admin.
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

// ── Handle login POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrf($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $pdo = getDB();
        [$success, $loginError] = attemptLogin($pdo, $username, $password);

        if ($success) {
            header('Location: admin.php');
            exit;
        }

        $error = $loginError;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — BookShelf</title>
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
            <a href="login.php" class="nav-link active">
                <i data-lucide="lock"></i> Admin
            </a>
            <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
                <i data-lucide="sun" class="icon-sun"></i>
                <i data-lucide="moon" class="icon-moon"></i>
            </button>
        </nav>
    </header>

    <!-- Main -->
    <main class="main-content login-page">

        <div class="login-container">
            <div class="login-card card">

                <div class="login-header">
                    <i data-lucide="shield"></i>
                    <h1>Admin Sign In</h1>
                    <p>Enter your credentials to manage the book collection.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" autocomplete="off" novalidate>
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Enter username"
                            required
                            autofocus
                            maxlength="50"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter password"
                            required
                            maxlength="255"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary login-submit">
                        <i data-lucide="log-in"></i>
                        Sign In
                    </button>
                </form>

            </div>

            <a href="index.php" class="login-back">
                <i data-lucide="arrow-left"></i>
                Back to Library
            </a>
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
