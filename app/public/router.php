<?php
/**
 * Router script for PHP built-in development server.
 * PHP's built-in server doesn't support .htaccess, so this handles URL rewriting.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve existing static files directly
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
    return false; // Let PHP's built-in server handle static files
}

// Route everything else through index.php
require __DIR__ . '/index.php';
