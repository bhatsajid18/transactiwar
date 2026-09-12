<?php
/**
 * Dashboard controller.
 */

class DashboardController {

    public function index(): void {
        requireAuth();
        logActivity('/dashboard', 'view_dashboard');

        $userId = getCurrentUserId();
        $user = User::findById($userId);
        $recentTransactions = Transaction::getHistory($userId, 5);

        require __DIR__ . '/../views/dashboard.php';
    }
}
