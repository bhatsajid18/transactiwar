<?php
/**
 * Rate limiting middleware.
 * Tracks and enforces request rate limits using atomic DB operations.
 */

function checkRateLimit(string $identifier, string $action, int $maxAttempts = RATE_LIMIT_MAX_REQUESTS, int $window = RATE_LIMIT_WINDOW): bool {
    try {
        $db = Database::getConnection();

        // Atomic upsert + check in a single query to prevent race conditions
        $stmt = $db->prepare(
            "INSERT INTO rate_limits (identifier, action, attempts, window_start)
             VALUES (:identifier, :action, 1, CURRENT_TIMESTAMP)
             ON CONFLICT (identifier, action) DO UPDATE SET
                attempts = CASE
                    WHEN rate_limits.window_start < :cutoff THEN 1
                    ELSE rate_limits.attempts + 1
                END,
                window_start = CASE
                    WHEN rate_limits.window_start < :cutoff THEN CURRENT_TIMESTAMP
                    ELSE rate_limits.window_start
                END
             RETURNING attempts"
        );
        $cutoff = date('Y-m-d H:i:s', time() - $window);
        $stmt->execute([
            ':identifier' => $identifier,
            ':action' => $action,
            ':cutoff' => $cutoff,
        ]);
        $row = $stmt->fetch();

        // Clean up old entries periodically (non-blocking)
        if (random_int(1, 100) <= 5) {
            $cleanup = $db->prepare('DELETE FROM rate_limits WHERE window_start < :cutoff');
            $cleanup->execute([':cutoff' => $cutoff]);
        }

        return ($row['attempts'] <= $maxAttempts);
    } catch (Exception $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        return false; // Fail closed — block request on DB error
    }
}

function isAccountLocked(string $username, ?string $ip = null): bool {
    try {
        $db = Database::getConnection();
        $ip = $ip ?? getClientIp();

        // Count recent failed attempts from this specific IP for this username
        $stmt = $db->prepare(
            'SELECT COUNT(*) as cnt FROM failed_logins
             WHERE username = :username AND ip_address = :ip AND attempted_at > :cutoff'
        );
        $stmt->execute([
            ':username' => $username,
            ':ip'       => $ip,
            ':cutoff'   => date('Y-m-d H:i:s', time() - LOCKOUT_DURATION),
        ]);
        $result = $stmt->fetch();

        if ($result['cnt'] >= MAX_LOGIN_ATTEMPTS) {
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log('Account lock check failed: ' . $e->getMessage());
        return false;
    }
}
