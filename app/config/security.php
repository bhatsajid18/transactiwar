<?php
/**
 * Security constants and configuration.
 */

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Brute force protection
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 600); // 10 minutes in seconds

// Password policy
define('MIN_PASSWORD_LENGTH', 10);

// File upload
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('UPLOAD_DIR', '/var/uploads/');

// CSRF token lifetime (1 hour)
define('CSRF_TOKEN_LIFETIME', 3600);

// Rate limiting
define('RATE_LIMIT_WINDOW', 600); // 10 minutes
define('RATE_LIMIT_MAX_REQUESTS', 100);

// Biography max length
define('MAX_BIOGRAPHY_LENGTH', 10000);

// Transfer comment max length
define('MAX_COMMENT_LENGTH', 500);
