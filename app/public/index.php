<?php
/**
 * TransactiWar — Main Entry Point
 * All requests are routed through this file.
 *
 * Security: This is the ONLY publicly accessible PHP file.
 * All other files are outside the web root or access-denied by Apache.
 */

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Error handling — never expose details to users
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

// Load middleware
require_once __DIR__ . '/../middleware/security_headers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/csrf.php';
require_once __DIR__ . '/../middleware/logging.php';
require_once __DIR__ . '/../middleware/rate_limit.php';
require_once __DIR__ . '/../middleware/validation.php';

// Load models
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Transaction.php';

// Load controllers
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../controllers/TransferController.php';
require_once __DIR__ . '/../controllers/SearchController.php';

// Load view layout
require_once __DIR__ . '/../views/layout.php';

// Set security headers on every response
setSecurityHeaders();

// Initialize session
initSession();

// Simple router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Route matching
$auth       = new AuthController();
$dashboard  = new DashboardController();
$profile    = new ProfileController();
$transfer   = new TransferController();
$search     = new SearchController();

switch (true) {
    // Home — redirect to dashboard or login
    case $requestUri === '/' || $requestUri === '':
        if (isLoggedIn()) {
            header('Location: /dashboard');
        } else {
            header('Location: /login');
        }
        exit;

    // Auth routes
    case $requestUri === '/register' && $method === 'GET':
        $auth->showRegister();
        break;

    case $requestUri === '/register' && $method === 'POST':
        $auth->register();
        break;

    case $requestUri === '/login' && $method === 'GET':
        $auth->showLogin();
        break;

    case $requestUri === '/login' && $method === 'POST':
        $auth->login();
        break;

    case $requestUri === '/logout' && $method === 'POST':
        $auth->logout();
        break;

    // Dashboard
    case $requestUri === '/dashboard':
        $dashboard->index();
        break;

    // Profile routes
    case $requestUri === '/profile' && $method === 'GET':
        $profile->showProfile();
        break;

    case $requestUri === '/profile/edit' && $method === 'GET':
        $profile->showEditProfile();
        break;

    case $requestUri === '/profile/update' && $method === 'POST':
        $profile->updateProfile();
        break;

    case $requestUri === '/profile/upload-image' && $method === 'POST':
        $profile->uploadImage();
        break;

    case $requestUri === '/profile/change-password' && $method === 'POST':
        $profile->changePassword();
        break;

    // View other user profile: /user/{id}
    case preg_match('#^/user/(\d+)$#', $requestUri, $matches) === 1:
        $profile->viewProfile((int)$matches[1]);
        break;

    // Serve profile images securely: /image/{filename}
    case preg_match('#^/image/([a-f0-9]+\.(jpg|jpeg|png))$#i', $requestUri, $matches) === 1:
        $profile->serveImage($matches[1]);
        break;

    // Transfer routes
    case $requestUri === '/transfer' && $method === 'GET':
        $transfer->showTransferForm();
        break;

    case $requestUri === '/transfer' && $method === 'POST':
        $transfer->processTransfer();
        break;

    // Transaction history
    case $requestUri === '/transactions':
        $transfer->showHistory();
        break;

    // Search
    case $requestUri === '/search':
        $search->showSearch();
        break;

    // 404 — log suspicious access
    default:
        logActivity($requestUri, '404_not_found');

        // Detect path traversal attempts
        if (preg_match('/\.\.|%2e%2e/i', $requestUri)) {
            logAttackEvent(
                getCurrentUserId(),
                getCurrentUsername() ?? 'anonymous',
                'path_traversal',
                'Path traversal attempt: ' . mb_substr($requestUri, 0, 200),
                'high'
            );
        }

        http_response_code(404);
        ob_start();
        echo '<div class="text-center py-20">';
        echo '<h1 class="text-6xl font-bold text-gray-300">404</h1>';
        echo '<p class="text-gray-500 mt-4">Page not found.</p>';
        echo '<a href="/dashboard" class="text-indigo-600 hover:underline mt-2 inline-block">Go to Dashboard</a>';
        echo '</div>';
        $content = ob_get_clean();
        renderLayout('Not Found', $content, isLoggedIn());
        break;
}
