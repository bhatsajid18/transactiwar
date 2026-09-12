<?php
/**
 * Authentication middleware.
 * Handles session validation, timeout, and access control.
 */

function initSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_start();
}

function requireAuth(): void {
    initSession();

    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: /login');
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        destroySession();
        // Start a new session for the flash message
        session_start();
        $_SESSION['flash_error'] = 'Session expired due to inactivity. Please log in again.';
        header('Location: /login');
        exit;
    }

    // Update last activity
    $_SESSION['last_activity'] = time();

    // Validate session integrity — check user agent
    if (isset($_SESSION['user_agent_hash'])) {
        $currentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        if (!hash_equals($_SESSION['user_agent_hash'], $currentHash)) {
            logAttackEvent(
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'unknown',
                'session_hijack_attempt',
                'User agent mismatch detected',
                'high'
            );
            destroySession();
            session_start();
            $_SESSION['flash_error'] = 'Session invalidated for security reasons.';
            header('Location: /login');
            exit;
        }
    }

    // Validate session integrity — check IP address
    if (isset($_SESSION['ip_address'])) {
        $currentIp = getClientIp();
        if ($_SESSION['ip_address'] !== $currentIp) {
            logAttackEvent(
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'unknown',
                'session_hijack_attempt',
                'IP address mismatch: expected ' . $_SESSION['ip_address'] . ', got ' . $currentIp,
                'high'
            );
            destroySession();
            session_start();
            $_SESSION['flash_error'] = 'Session invalidated for security reasons.';
            header('Location: /login');
            exit;
        }
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

function destroySession(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function createAuthSession(int $userId, string $username): void {
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at'] = time();
    $_SESSION['user_agent_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $_SESSION['ip_address'] = getClientIp();
}

function getClientIp(): string {
    // Only trust REMOTE_ADDR to prevent IP spoofing via headers
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
