# BookShelf — Book Management System

A modern, responsive, and highly secure PHP-based web application for managing a library collection. Designed with a clean wide-layout UI, fully responsive for desktop and mobile, and localized in both English and Czech.

## Features
- **Public Library View:** Beautiful grid display of all books with cover initials, ratings, and titles.
- **Admin Dashboard:** Secure backend to add, edit, or bulk-import books.
- **Bulk JSON Import:** Upload large lists of books via JSON drag-and-drop. Includes duplicate detection and automatic skipping.
- **Advanced Security:** Rate-limited authentication (bcrypt), strict HttpOnly sessions, CSRF protection, and SQLite parameterized queries to prevent SQL injection.
- **Multilingual Support (i18n):** Seamlessly switch between English and Czech using the animated toggle slider.
- **Dark/Light Mode:** Automatic OS-level theme detection with a manual override toggle.
- **Responsive Design:** Optimized CSS grid and flexbox layouts with fluid truncation logic so UI never breaks on mobile.

## Requirements
- Docker and Docker Compose

## Getting Started

1. **Start the Application**
   Run the following command in the root directory:
   ```bash
   docker-compose up -d
   ```

2. **Access the Web App**
   Open your browser and navigate to:
   [http://localhost:8000](http://localhost:8000)

3. **Admin Login**
   The application automatically initializes a secure SQLite database on the first run.
   - **Username:** `admin`
   - **Password:** `admin123`
   *(It is highly recommended to change this in the Admin Panel after your first login).*

## Local Development (Without Docker)
If you wish to run the app natively using PHP's built-in server:
```bash
php -S localhost:8000
```
*Note: Ensure the `pdo_sqlite` extension is enabled in your `php.ini`.*

## Project Structure
- `index.php` / `detail.php` - Public-facing frontend pages.
- `admin.php` / `login.php` - Protected admin interfaces.
- `assets/` - Contains compiled CSS, client-side JS, and raw SCSS source code.
- `includes/` - Backend logic (auth, database, translations, bootstrap).
- `database/` - Stores the persistent SQLite database (auto-generated).
- `example_data.json` - Dummy book data provided for testing the JSON import feature.
