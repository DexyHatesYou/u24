<?php
/**
 * Public — Book List
 */
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_book' && isLoggedIn()) {
    if (validateCsrf($_POST['csrf_token'] ?? '')) {
        $deleteId = (int)($_POST['book_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
        rotateCsrfToken();
        header("Location: index.php?msg=deleted");
        exit;
    }
}

$books = $pdo->query("SELECT id, title, author, release_year, rating FROM books ORDER BY title ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookShelf — Library</title>
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
            <span class="logo-text">Book<span>Shelf</span></span>
        </a>
        <nav class="header-nav">
            <a href="index.php" class="nav-link active">
                <i data-lucide="library"></i> Library
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="admin.php" class="nav-link">
                    <i data-lucide="settings"></i> Admin
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    <i data-lucide="lock"></i> Admin
                </a>
            <?php endif; ?>
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
                <i data-lucide="library"></i>
                Book Collection
            </h1>
            <div class="actions no-print">
                <button id="btn-print" class="btn btn-outline" title="Print book list">
                    <i data-lucide="printer"></i>
                    Print List
                </button>
            </div>
        </div>

        <?php if (empty($books)): ?>
            <div class="empty-state">
                <i data-lucide="book-open"></i>
                <p>No books in the collection yet.</p>
                <a href="login.php" class="btn btn-primary">Add Books</a>
            </div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card card">
                        <a href="detail.php?id=<?= (int)$book['id'] ?>" class="book-card__link">
                            <div class="book-card__cover">
                                <span class="book-card__initial"><?= htmlspecialchars(mb_substr($book['title'], 0, 1)) ?></span>
                            </div>
                            <div class="book-card__body">
                                <h3 class="book-card__title"><?= htmlspecialchars($book['title']) ?></h3>
                                <p class="book-card__author"><?= htmlspecialchars($book['author']) ?></p>
                                <div class="book-card__meta">
                                    <span class="badge"><?= (int)$book['release_year'] ?></span>
                                    <?php if ($book['rating'] > 0): ?>
                                        <span class="book-card__rating">
                                            <?= str_repeat('★', (int)round($book['rating'])) . str_repeat('☆', 5 - (int)round($book['rating'])) ?>
                                            <small><?= number_format($book['rating'], 1) ?></small>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                        <?php if (isLoggedIn()): ?>
                            <form method="POST" action="index.php" class="book-card__delete-form" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_book">
                                <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                                <button type="submit" class="btn-delete" title="Delete book">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Print-only table -->
            <div class="print-only">
                <h2>Book Collection</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $i => $book): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= (int)$book['release_year'] ?></td>
                                <td><?= number_format($book['rating'], 1) ?> / 5</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

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
