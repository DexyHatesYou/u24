<?php
/**
 * Public — Book Detail
 */
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = getDB();

// Validate & fetch
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

$ratingFull  = (int) floor($book['rating']);
$ratingEmpty = 5 - $ratingFull;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> — BookShelf</title>
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
            <a href="index.php" class="nav-link active">
                <i data-lucide="library"></i> Library
            </a>
            <a href="login.php" class="nav-link">
                <i data-lucide="lock"></i> Admin
            </a>
            <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
                <i data-lucide="sun" class="icon-sun"></i>
                <i data-lucide="moon" class="icon-moon"></i>
            </button>
        </nav>
    </header>

    <!-- Main -->
    <main class="main-content">

        <a href="index.php" class="back-link">
            <i data-lucide="arrow-left"></i>
            Back to Library
        </a>

        <div class="detail-layout">

            <!-- Book Cover -->
            <div class="detail-cover">
                <div class="detail-cover__visual">
                    <span class="detail-cover__initial"><?= htmlspecialchars(mb_substr($book['title'], 0, 1)) ?></span>
                    <i data-lucide="book-open" class="detail-cover__icon"></i>
                </div>
            </div>

            <!-- Book Info -->
            <div class="detail-info">
                <h1 class="detail-title"><?= htmlspecialchars($book['title']) ?></h1>

                <div class="detail-meta">
                    <div class="detail-meta__item">
                        <i data-lucide="user"></i>
                        <span><?= htmlspecialchars($book['author']) ?></span>
                    </div>
                    <div class="detail-meta__item">
                        <i data-lucide="calendar"></i>
                        <span><?= (int) $book['release_year'] ?></span>
                    </div>
                    <?php if ($book['rating'] > 0): ?>
                        <div class="detail-meta__item">
                            <i data-lucide="star"></i>
                            <span class="detail-rating">
                                <?= str_repeat('★', $ratingFull) . str_repeat('☆', $ratingEmpty) ?>
                                <strong><?= number_format($book['rating'], 1) ?></strong>
                                <small>/ 5</small>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty(trim($book['annotation']))): ?>
                    <div class="detail-section">
                        <h2 class="detail-section__heading">Annotation</h2>
                        <div class="detail-annotation">
                            <?= nl2br(htmlspecialchars($book['annotation'])) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="detail-section">
                        <p class="detail-no-annotation">No annotation available for this book.</p>
                    </div>
                <?php endif; ?>

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
