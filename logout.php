<?php
/**
 * Logout — destroys session and redirects to login.
 */
require_once __DIR__ . '/includes/bootstrap.php';

logout();

header('Location: login.php');
exit;
