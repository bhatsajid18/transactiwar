<?php
/**
 * Search controller.
 * Handles user search functionality.
 */

class SearchController {

    public function showSearch(): void {
        requireAuth();
        logActivity('/search', 'view_search_page');

        $query = trim($_GET['q'] ?? '');
        $results = [];

        if (!empty($query)) {
            // Rate limit search by IP
            $ip = getClientIp();
            if (!checkRateLimit($ip, 'search', 30, 60)) {
                $_SESSION['flash_error'] = 'Too many search requests. Please slow down.';
                $csrfField = getCsrfTokenField();
                require __DIR__ . '/../views/search.php';
                return;
            }

            // Limit query length to prevent abuse
            if (strlen($query) > 100) {
                $query = substr($query, 0, 100);
            }

            $results = User::search($query);
            logActivity('/search', 'search_query: ' . mb_substr($query, 0, 50));
        }

        $csrfField = getCsrfTokenField();
        require __DIR__ . '/../views/search.php';
    }
}
