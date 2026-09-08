<?php

/**
 * Authentication & Security Helpers
 *
 * Provides:
 *  - CSRF token generation & validation
 *  - Session security (fixation protection, cookie hardening)
 *  - Brute-force rate limiting (IP + username, stored in SQLite)
 *  - Login / logout / guard helpers
 */

// ─── Session hardening ──────────────────────────────────────────

/**
 * Configure secure session parameters.
 * Call BEFORE session_start().
 */
function configureSession(): void
{
    // Prevent JavaScript access to session cookie
    ini_set('session.cookie_httponly', '1');
    // SameSite=Strict prevents CSRF via cross-origin requests
    ini_set('session.cookie_samesite', 'Strict');
    // Use cookies only (no URL-based session IDs)
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    // If running behind HTTPS, enforce Secure flag
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
}

// ─── CSRF Protection ────────────────────────────────────────────

/**
 * Generate a CSRF token and store in session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate a submitted CSRF token.
 * Uses hash_equals to prevent timing attacks.
 */
function validateCsrf(string $submittedToken): bool
{
    if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/**
 * Regenerate the CSRF token (call after successful form submission).
 */
function rotateCsrfToken(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Rate Limiting ──────────────────────────────────────────────

const MAX_LOGIN_ATTEMPTS  = 5;
const LOCKOUT_MINUTES     = 15;

/**
 * Ensure the login_attempts table exists.
 */
function ensureRateLimitTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT    NOT NULL,
            ip_address TEXT    NOT NULL,
            attempted_at TEXT  DEFAULT (datetime('now')),
            success    INTEGER DEFAULT 0
        )
    ");
}

/**
 * Record a login attempt (successful or failed).
 */
function recordLoginAttempt(PDO $pdo, string $username, bool $success): void
{
    ensureRateLimitTable($pdo);

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (username, ip_address, success)
        VALUES (:u, :ip, :s)
    ");
    $stmt->execute([
        ':u'  => $username,
        ':ip' => $ip,
        ':s'  => $success ? 1 : 0,
    ]);

    // Purge attempts older than the lockout window (housekeeping)
    $pdo->exec("
        DELETE FROM login_attempts
        WHERE attempted_at < datetime('now', '-" . (LOCKOUT_MINUTES * 2) . " minutes')
    ");
}

/**
 * Check whether a username/IP combo is rate-limited.
 */
function isRateLimited(PDO $pdo, string $username): bool
{
    ensureRateLimitTable($pdo);

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE (username = :u OR ip_address = :ip)
          AND success = 0
          AND attempted_at > datetime('now', '-' || :mins || ' minutes')
    ");
    $stmt->execute([
        ':u'    => $username,
        ':ip'   => $ip,
        ':mins' => LOCKOUT_MINUTES,
    ]);

    return (int) $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Clear failed attempts after a successful login.
 */
function clearLoginAttempts(PDO $pdo, string $username): void
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("
        DELETE FROM login_attempts
        WHERE (username = :u OR ip_address = :ip)
          AND success = 0
    ");
    $stmt->execute([':u' => $username, ':ip' => $ip]);
}

// ─── Authentication ─────────────────────────────────────────────

/**
 * Attempt to authenticate a user.
 * Returns [bool $success, string $error].
 */
function attemptLogin(PDO $pdo, string $username, string $password): array
{
    $username = trim($username);

    if ($username === '' || $password === '') {
        return [false, 'Username and password are required.'];
    }

    if (isRateLimited($pdo, $username)) {
        return [false, 'Too many failed attempts. Please try again in ' . LOCKOUT_MINUTES . ' minutes.'];
    }

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    // Constant-time check even when user doesn't exist (prevent enumeration)
    $hash = $user ? $user['password'] : '$2y$10$dummyhashtopreventtimingleakattacks000000000000000000';
    $valid = password_verify($password, $hash);

    if (!$user || !$valid) {
        recordLoginAttempt($pdo, $username, false);
        return [false, 'Invalid username or password.'];
    }

    // Success — regenerate session to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_name']     = $user['username'];
    $_SESSION['user_ip']       = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['login_time']    = time();

    recordLoginAttempt($pdo, $username, true);
    clearLoginAttempts($pdo, $username);
    rotateCsrfToken();

    return [true, ''];
}

/**
 * Check if the current session is authenticated.
 * Also validates session fingerprint (IP + user agent).
 */
function isLoggedIn(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    // Session fingerprint check — detect hijacking
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (($_SESSION['user_ip'] ?? '') !== $currentIp) {
        return false;
    }
    if (($_SESSION['user_agent'] ?? '') !== $currentUa) {
        return false;
    }

    return true;
}

/**
 * Guard: redirect to login page if not authenticated.
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        // Clear any stale session data
        $_SESSION = [];
        header('Location: login.php');
        exit;
    }
}

/**
 * Destroy the session securely.
 */
function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
