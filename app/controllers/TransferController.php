<?php
/**
 * Transfer controller.
 * Handles money transfers and transaction history.
 */

class TransferController {

    public function showTransferForm(): void {
        requireAuth();
        logActivity('/transfer', 'view_transfer_page');

        $userId = getCurrentUserId();
        $balance = User::getBalance($userId);
        $csrfField = getCsrfTokenField();

        // Pre-fill receiver from query string
        $receiverId = isset($_GET['to']) && is_scalar($_GET['to']) ? (int)$_GET['to'] : null;
        $receiverUser = null;
        if ($receiverId && $receiverId !== $userId) {
            $receiverUser = User::findById($receiverId);
        }

        require __DIR__ . '/../views/transfer.php';
    }

    public function processTransfer(): void {
        requireAuth();

        if (!validateCsrfToken()) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'csrf_violation', 'CSRF token mismatch on transfer', 'high');
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /transfer');
            exit;
        }

        // Rate limit transfers
        $ip = getClientIp();
        if (!checkRateLimit($ip . ':' . getCurrentUserId(), 'transfer', 30, 60)) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'rate_limit_transfer', 'Transfer rate limit exceeded', 'medium');
            $_SESSION['flash_error'] = 'Too many transfer attempts. Please slow down.';
            header('Location: /transfer');
            exit;
        }

        $senderId    = getCurrentUserId();
        $receiverId  = isset($_POST['receiver_id']) && is_scalar($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
        $amount      = trim($_POST['amount'] ?? '');
        $comment     = trim($_POST['comment'] ?? '');

        // Validate receiver ID
        if ($receiverId <= 0) {
            $_SESSION['flash_error'] = 'Invalid receiver.';
            header('Location: /transfer');
            exit;
        }

        // Prevent self-transfer
        if ($receiverId === $senderId) {
            logAttackEvent($senderId, getCurrentUsername(), 'self_transfer', 'Attempted self-transfer', 'low');
            $_SESSION['flash_error'] = 'You cannot transfer money to yourself.';
            header('Location: /transfer');
            exit;
        }

        // Validate receiver exists
        $receiver = User::findById($receiverId);
        if (!$receiver) {
            $_SESSION['flash_error'] = 'Receiver not found.';
            header('Location: /transfer');
            exit;
        }

        // Validate amount
        $amountErrors = validateTransferAmount($amount);
        if (!empty($amountErrors)) {
            $_SESSION['flash_error'] = implode('<br>', array_map('htmlspecialchars', $amountErrors));
            header('Location: /transfer?to=' . $receiverId);
            exit;
        }

        // Validate comment
        $commentErrors = validateComment($comment);
        if (!empty($commentErrors)) {
            $_SESSION['flash_error'] = implode('<br>', array_map('htmlspecialchars', $commentErrors));
            header('Location: /transfer?to=' . $receiverId);
            exit;
        }

        $amountFloat = number_format(floatval($amount), 2, '.', '');

        // Perform transfer
        $result = Transaction::transfer($senderId, $receiverId, $amountFloat, $comment);

        if ($result['success']) {
            logActivity('/transfer', 'transfer_success: ' . $amountFloat . ' to user ' . $receiverId);
            $_SESSION['flash_success'] = sanitizeOutput($result['message']) .
                ' Sent Rs.' . number_format($amountFloat, 2) . ' to ' . sanitizeOutput($receiver['username']) . '.';
        } else {
            logActivity('/transfer', 'transfer_failed: ' . ($result['error'] ?? 'unknown'));
            $_SESSION['flash_error'] = sanitizeOutput($result['error']);
        }

        header('Location: /transactions');
        exit;
    }

    public function showHistory(): void {
        requireAuth();
        logActivity('/transactions', 'view_transaction_history');

        $userId = getCurrentUserId();
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $transactions = Transaction::getHistory($userId, $perPage, $offset);
        $totalCount = Transaction::countForUser($userId);
        $totalPages = max(1, ceil($totalCount / $perPage));
        $balance = User::getBalance($userId);

        require __DIR__ . '/../views/transactions.php';
    }
}
