<?php
/**
 * Transaction model — handles money transfers with DB transactions.
 * Uses SELECT ... FOR UPDATE to prevent race conditions.
 */

class Transaction {

    /**
     * Transfer money between users atomically.
     * Uses row-level locking to prevent race conditions.
     */
    public static function transfer(int $senderId, int $receiverId, string|float $amount, string $comment = ''): array {
        $amount = number_format(floatval($amount), 2, '.', '');
        $db = Database::getConnection();

        // Prevent self-transfer
        if ($senderId === $receiverId) {
            return ['success' => false, 'error' => 'Cannot transfer money to yourself.'];
        }

        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than zero.'];
        }

        try {
            $db->beginTransaction();

            // Lock both rows in consistent order to prevent deadlocks
            $firstId = min($senderId, $receiverId);
            $secondId = max($senderId, $receiverId);

            $stmt = $db->prepare('SELECT id, balance FROM users WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $firstId]);
            $first = $stmt->fetch();

            $stmt->execute([':id' => $secondId]);
            $second = $stmt->fetch();

            if (!$first || !$second) {
                $db->rollBack();
                return ['success' => false, 'error' => 'User not found.'];
            }

            // Determine sender/receiver from locked rows
            $senderBalance = ($firstId === $senderId) ? floatval($first['balance']) : floatval($second['balance']);

            // Check sufficient balance
            if ($senderBalance < $amount) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Insufficient balance.'];
            }

            // Deduct from sender
            $stmt = $db->prepare('UPDATE users SET balance = balance - :amount WHERE id = :id AND balance >= :amount');
            $stmt->execute([':amount' => $amount, ':id' => $senderId]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return ['success' => false, 'error' => 'Insufficient balance (concurrent modification).'];
            }

            // Add to receiver
            $stmt = $db->prepare('UPDATE users SET balance = balance + :amount WHERE id = :id');
            $stmt->execute([':amount' => $amount, ':id' => $receiverId]);

            // Record transaction
            $stmt = $db->prepare(
                'INSERT INTO transactions (sender_id, receiver_id, amount, comment)
                 VALUES (:sender_id, :receiver_id, :amount, :comment)'
            );
            $stmt->execute([
                ':sender_id'   => $senderId,
                ':receiver_id' => $receiverId,
                ':amount'      => $amount,
                ':comment'     => mb_substr($comment, 0, MAX_COMMENT_LENGTH),
            ]);

            $db->commit();
            return ['success' => true, 'message' => 'Transfer completed successfully.'];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Transfer failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Transfer failed. Please try again.'];
        }
    }

    /**
     * Get transaction history for a user (both sent and received).
     */
    public static function getHistory(int $userId, int $limit = 50, int $offset = 0): array {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT t.id, t.sender_id, t.receiver_id, t.amount, t.comment, t.created_at,
                    s.username as sender_username, r.username as receiver_username
             FROM transactions t
             JOIN users s ON t.sender_id = s.id
             JOIN users r ON t.receiver_id = r.id
             WHERE t.sender_id = :uid1 OR t.receiver_id = :uid2
             ORDER BY t.created_at DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count total transactions for a user.
     */
    public static function countForUser(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) as cnt FROM transactions WHERE sender_id = :uid1 OR receiver_id = :uid2'
        );
        $stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
        $result = $stmt->fetch();
        return (int)$result['cnt'];
    }
}
