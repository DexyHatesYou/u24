<?php
/**
 * Admin Panel — Protected
 *
 * Features:
 *  - Dashboard stats
 *  - Add new book (with validation)
 *  - JSON import (Step 6 placeholder)
 */
require_once __DIR__ . '/includes/bootstrap.php';

requireAuth();

$pdo       = getDB();
$errors     = [];
$success    = '';
$pwdErrors     = [];
$pwdSuccess    = '';
$importErrors  = [];
$importSuccess = '';
$formData      = [
    'title'        => '',
    'author'       => '',
    'release_year' => '',
    'annotation'   => '',
    'rating'       => '',
];

// ─── Handle Add Book POST ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_book') {

    // CSRF check
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        // Sanitize inputs
        $formData['title']        = trim($_POST['title'] ?? '');
        $formData['author']       = trim($_POST['author'] ?? '');
        $formData['release_year'] = trim($_POST['release_year'] ?? '');
        $formData['annotation']   = trim($_POST['annotation'] ?? '');
        $formData['rating']       = trim($_POST['rating'] ?? '');

        // ── Validation ──────────────────────────────────────────
        if ($formData['title'] === '') {
            $errors[] = 'Title is required.';
        } elseif (mb_strlen($formData['title']) > 255) {
            $errors[] = 'Title must be 255 characters or fewer.';
        }

        if ($formData['author'] === '') {
            $errors[] = 'Author is required.';
        } elseif (mb_strlen($formData['author']) > 255) {
            $errors[] = 'Author must be 255 characters or fewer.';
        }

        if ($formData['release_year'] === '') {
            $errors[] = 'Release year is required.';
        } elseif (!ctype_digit($formData['release_year']) && !preg_match('/^\d{4}$/', $formData['release_year'])) {
            $errors[] = 'Release year must be a valid 4-digit year.';
        } else {
            $year = (int) $formData['release_year'];
            if ($year < 1000 || $year > (int) date('Y') + 1) {
                $errors[] = 'Release year must be between 1000 and ' . ((int) date('Y') + 1) . '.';
            }
        }

        if ($formData['rating'] === '') {
            $errors[] = 'Rating is required.';
        } elseif (!is_numeric($formData['rating'])) {
            $errors[] = 'Rating must be a number.';
        } else {
            $rating = (float) $formData['rating'];
            if ($rating < 0 || $rating > 5) {
                $errors[] = 'Rating must be between 0 and 5.';
            }
        }

        if (mb_strlen($formData['annotation']) > 5000) {
            $errors[] = 'Annotation must be 5000 characters or fewer.';
        }

        // ── Insert if valid ─────────────────────────────────────
        if (empty($errors)) {
            // Anti-duplicate check (by title)
            $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE LOWER(title) = LOWER(:title)");
            $dupStmt->execute([':title' => $formData['title']]);
            if ($dupStmt->fetchColumn() > 0) {
                $errors[] = 'A book with this title already exists in the database.';
            } else {
                $stmt = $pdo->prepare("
                INSERT INTO books (title, author, release_year, annotation, rating)
                VALUES (:title, :author, :year, :annotation, :rating)
            ");
            $stmt->execute([
                ':title'      => $formData['title'],
                ':author'     => $formData['author'],
                ':year'       => (int) $formData['release_year'],
                ':annotation' => $formData['annotation'],
                ':rating'     => round((float) $formData['rating'], 1),
            ]);

            $success = 'Book "' . $formData['title'] . '" added successfully.';

            // Clear form
            $formData = [
                'title'        => '',
                'author'       => '',
                'release_year' => '',
                'annotation'   => '',
                'rating'       => '',
            ];
            rotateCsrfToken();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'change_password') {
    // CSRF check
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $pwdErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $currentPwd = $_POST['current_password'] ?? '';
        $newPwd     = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
            $pwdErrors[] = 'All password fields are required.';
        } elseif ($newPwd !== $confirmPwd) {
            $pwdErrors[] = 'New password and confirmation do not match.';
        } elseif (strlen($newPwd) < 8) {
            $pwdErrors[] = 'New password must be at least 8 characters long.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($currentPwd, $user['password'])) {
                // Update with safe hashed and salted password (PASSWORD_DEFAULT uses bcrypt with random salt)
                $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id");
                $updateStmt->execute([
                    ':pwd' => $newHash,
                    ':id'  => $_SESSION['user_id']
                ]);
                
                $pwdSuccess = 'Password successfully changed.';
                rotateCsrfToken();
            } else {
                $pwdErrors[] = 'Current password is incorrect.';
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'import_json') {
    // CSRF check
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $importErrors[] = 'Invalid form submission. Please try again.';
    } elseif (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $importErrors[] = 'Please select a JSON file to upload.';
    } elseif ($_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
        $importErrors[] = 'File upload error. Please try again.';
    } else {
        $fileTmpPath = $_FILES['json_file']['tmp_name'];
        $fileName    = $_FILES['json_file']['name'];
        
        // Ensure it has a .json extension
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'json') {
            $importErrors[] = 'Uploaded file must be a JSON file.';
        } else {
            $jsonContent = file_get_contents($fileTmpPath);
            $books = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $importErrors[] = 'Invalid JSON format in the uploaded file.';
            } elseif (!is_array($books)) {
                $importErrors[] = 'JSON file must contain an array of books.';
            } else {
                // Bulk insert via transaction
                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO books (title, author, release_year, annotation, rating)
                        VALUES (:title, :author, :year, :annotation, :rating)
                    ");
                    
                    $importedCount = 0;
                    $skippedCount = 0;
                    
                    $checkDupStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE LOWER(title) = LOWER(:title)");
                    
                    foreach ($books as $index => $book) {
                        // Basic validation for required structure
                        if (empty($book['title']) || empty($book['author'])) {
                            continue; // Skip invalid entries silently, or we could error out
                        }
                        
                        $cleanTitle = mb_substr(trim($book['title']), 0, 255);
                        
                        // Check if duplicate
                        $checkDupStmt->execute([':title' => $cleanTitle]);
                        if ($checkDupStmt->fetchColumn() > 0) {
                            $skippedCount++;
                            continue;
                        }
                        
                        $stmt->execute([
                            ':title'      => $cleanTitle,
                            ':author'     => mb_substr(trim($book['author']), 0, 255),
                            ':year'       => isset($book['release_year']) ? (int) $book['release_year'] : null,
                            ':annotation' => isset($book['annotation']) ? mb_substr(trim($book['annotation']), 0, 5000) : '',
                            ':rating'     => isset($book['rating']) ? round((float) $book['rating'], 1) : 0.0,
                        ]);
                        $importedCount++;
                    }
                    
                    $pdo->commit();
                    $msg = "Successfully imported $importedCount books.";
                    if ($skippedCount > 0) {
                        $msg .= " Skipped $skippedCount duplicates.";
                    }
                    $importSuccess = $msg;
                    rotateCsrfToken();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $importErrors[] = 'Database error during import: ' . $e->getMessage();
                }
            }
        }
    }
}

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
            <span class="logo-text">Book<span>Shelf</span></span>
        </a>
        <nav class="header-nav">
            <a href="index.php" class="nav-link">
                <i data-lucide="library"></i> Library
            </a>
            <a href="admin.php" class="nav-link active">
                <i data-lucide="settings"></i> Admin
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
                    <a href="logout.php" class="btn btn-outline" style="margin-top: 12px; font-size: 13px; padding: 4px 10px;">
                        <i data-lucide="log-out" style="width: 14px; height: 14px; margin:0;"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- ─── Add New Book ─────────────────────────────────────── -->
        <div class="admin-sections">
            <div class="admin-section card" id="add-book">
                <h2 class="admin-section__title">
                    <i data-lucide="plus-circle"></i>
                    Add New Book
                </h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul class="error-list">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin.php#add-book" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="add_book">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Title <span class="required">*</span></label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control"
                                placeholder="e.g. The Pragmatic Programmer"
                                required
                                maxlength="255"
                                value="<?= htmlspecialchars($formData['title']) ?>"
                            >
                        </div>
                        <div class="form-group">
                            <label for="author">Author <span class="required">*</span></label>
                            <input
                                type="text"
                                id="author"
                                name="author"
                                class="form-control"
                                placeholder="e.g. David Thomas"
                                required
                                maxlength="255"
                                value="<?= htmlspecialchars($formData['author']) ?>"
                            >
                        </div>
                    </div>

                    <div class="form-row form-row--narrow">
                        <div class="form-group">
                            <label for="release_year">Release Year <span class="required">*</span></label>
                            <input
                                type="number"
                                id="release_year"
                                name="release_year"
                                class="form-control"
                                placeholder="e.g. 2019"
                                required
                                min="1000"
                                max="<?= (int) date('Y') + 1 ?>"
                                value="<?= htmlspecialchars($formData['release_year']) ?>"
                            >
                        </div>
                        <div class="form-group">
                            <label for="rating">Rating (0–5) <span class="required">*</span></label>
                            <input
                                type="number"
                                id="rating"
                                name="rating"
                                class="form-control"
                                placeholder="e.g. 4.5"
                                required
                                min="0"
                                max="5"
                                step="0.1"
                                value="<?= htmlspecialchars($formData['rating']) ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="annotation">Annotation</label>
                        <textarea
                            id="annotation"
                            name="annotation"
                            class="form-control"
                            placeholder="Brief description of the book (optional)"
                            maxlength="5000"
                        ><?= htmlspecialchars($formData['annotation']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        Add Book
                    </button>
                </form>

            </div>

            <!-- ─── JSON Import ─────────────────────────────────────────── -->
            <div class="admin-section card" id="import-json">
                <h2 class="admin-section__title">
                    <i data-lucide="upload"></i>
                    Import from JSON
                </h2>
                
                <?php if ($importSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($importSuccess) ?></div>
                <?php endif; ?>

                <?php if (!empty($importErrors)): ?>
                    <div class="alert alert-error">
                        <ul class="error-list">
                            <?php foreach ($importErrors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <p class="admin-section__desc" style="margin-bottom: 1rem;">Upload a JSON file containing an array of books to bulk add them to the database.</p>

                <form method="POST" action="admin.php#import-json" enctype="multipart/form-data" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="import_json">
                    
                    <div class="form-group">
                        <label>Select JSON File <span class="required">*</span></label>
                        <div class="file-upload-wrapper">
                            <i data-lucide="upload-cloud"></i>
                            <span class="file-upload-text">Click to upload or drag and drop</span>
                            <span class="file-upload-subtext">JSON files only (.json)</span>
                            <input type="file" name="json_file" id="json_file" accept=".json" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="upload-cloud"></i>
                        Import Books
                    </button>
                </form>
            </div>

            <!-- ─── Change Password ─────────────────────────────────────── -->
            <div class="admin-section card" id="change-password">
                <h2 class="admin-section__title">
                    <i data-lucide="key"></i>
                    Change Password
                </h2>

                <?php if ($pwdSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($pwdSuccess) ?></div>
                <?php endif; ?>

                <?php if (!empty($pwdErrors)): ?>
                    <div class="alert alert-error">
                        <ul class="error-list">
                            <?php foreach ($pwdErrors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin.php#change-password" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password <span class="required">*</span></label>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="check-circle"></i>
                        Update Password
                    </button>
                </form>
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
