<?php
$supportedLangs = ['en', 'cs'];

if (isset($_GET['set_lang']) && in_array($_GET['set_lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['set_lang'];
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    // Prevent redirect loops if HTTP_REFERER has set_lang in it
    $redirect = preg_replace('/([&?])set_lang=[a-z]{2}/i', '$1', $redirect);
    $redirect = rtrim(str_replace('?&', '?', $redirect), '?&');
    header("Location: " . $redirect);
    exit;
}

$currentLang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'nav_library' => 'Library',
        'nav_admin' => 'Admin',
        'nav_logout' => 'Logout',
        'book_collection' => 'Book Collection',
        'no_books' => 'No books in the collection yet.',
        'add_books' => 'Add Books',
        'print_list' => 'Print List',
        'admin_panel' => 'Admin Panel',
        'books_in_collection' => 'Books in Collection',
        'logged_in_as' => 'Logged in as',
        'add_new_book' => 'Add New Book',
        'title' => 'Title',
        'author' => 'Author',
        'release_year' => 'Release Year',
        'rating' => 'Rating',
        'annotation' => 'Annotation',
        'add_book' => 'Add Book',
        'import_json' => 'Import from JSON',
        'import_desc' => 'Upload a JSON file containing an array of books to bulk add them to the database.',
        'select_json' => 'Select JSON File',
        'import_books' => 'Import Books',
        'change_password' => 'Change Password',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm New Password',
        'update_password' => 'Update Password',
        'back_to_library' => 'Back to Library',
        'login_title' => 'Admin Login',
        'username' => 'Username',
        'password' => 'Password',
        'login' => 'Login',
        'drag_drop' => 'Click to upload or drag and drop',
        'json_only' => 'JSON files only (.json)',
        'delete_confirm' => 'Are you sure you want to delete this book?',
        'optional' => '(optional)',
        'eg_rating' => 'e.g. 4.5'
    ],
    'cs' => [
        'nav_library' => 'Knihovna',
        'nav_admin' => 'Administrace',
        'nav_logout' => 'Odhlásit se',
        'book_collection' => 'Knižní sbírka',
        'no_books' => 'Ve sbírce zatím nejsou žádné knihy.',
        'add_books' => 'Přidat knihy',
        'print_list' => 'Vytisknout seznam',
        'admin_panel' => 'Administrační panel',
        'books_in_collection' => 'Knih ve sbírce',
        'logged_in_as' => 'Přihlášen jako',
        'add_new_book' => 'Přidat novou knihu',
        'title' => 'Název',
        'author' => 'Autor',
        'release_year' => 'Rok vydání',
        'rating' => 'Hodnocení',
        'annotation' => 'Anotace',
        'add_book' => 'Přidat knihu',
        'import_json' => 'Importovat z JSON',
        'import_desc' => 'Nahrajte JSON soubor obsahující pole knih pro hromadné přidání do databáze.',
        'select_json' => 'Vyberte JSON soubor',
        'import_books' => 'Importovat knihy',
        'change_password' => 'Změnit heslo',
        'current_password' => 'Aktuální heslo',
        'new_password' => 'Nové heslo',
        'confirm_password' => 'Potvrdit nové heslo',
        'update_password' => 'Aktualizovat heslo',
        'back_to_library' => 'Zpět do knihovny',
        'login_title' => 'Přihlášení správce',
        'username' => 'Uživatelské jméno',
        'password' => 'Heslo',
        'login' => 'Přihlásit se',
        'drag_drop' => 'Klikněte pro nahrání nebo přetáhněte soubor',
        'json_only' => 'Pouze JSON soubory (.json)',
        'delete_confirm' => 'Opravdu chcete tuto knihu smazat?',
        'optional' => '(volitelné)',
        'eg_rating' => 'např. 4.5'
    ]
];

function __($key) {
    global $translations, $currentLang;
    return $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $key;
}
