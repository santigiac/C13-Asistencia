<?php
/**
 * Logout
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/classes/Auth.php';

Auth::logout();
header('Location: ' . BASE_URL . '/index.php');
exit;
