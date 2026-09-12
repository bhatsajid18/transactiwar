<?php
/**
 * Logging middleware.
 * Logs all user activity and security events to the database.
 */

function logActivity(string $page, string $action = ''): void {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO activity_logs (user_id, username, page, action, ip_address, user_agent)
             VALUES (:user_id, :username, :page, :action, :ip_address, :user_agent)'
        );
        $stmt->execute([
            ':user_id'    => getCurrentUserId(),
            ':username'   => getCurrentUsername() ?? 'anonymous',
            ':page'       => mb_substr($page, 0, 255),
            ':action'     => mb_substr($action, 0, 255),
            ':ip_address' => getClientIp(),
            ':user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (Exception $e) {
        error_log('Activity logging failed: ' . $e->getMessage());
    }
}

function logAttackEvent(
    ?int $userId,
    string $username,
    string $eventType,
    string $description,
    string $severity = 'medium'
): void {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO attack_logs (user_id, username, event_type, description, ip_address, user_agent, severity)
             VALUES (:user_id, :username, :event_type, :description, :ip_address, :user_agent, :severity)'
        );
        $stmt->execute([
            ':user_id'     => $userId,
            ':username'    => mb_substr($username, 0, 50),
            ':event_type'  => mb_substr($eventType, 0, 100),
            ':description' => mb_substr($description, 0, 1000),
            ':ip_address'  => getClientIp(),
            ':user_agent'  => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ':severity'    => $severity,
        ]);
    } catch (Exception $e) {
        error_log('Attack logging failed: ' . $e->getMessage());
    }
}

function logFailedLogin(string $username): void {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO failed_logins (username, ip_address, user_agent)
             VALUES (:username, :ip_address, :user_agent)'
        );
        $stmt->execute([
            ':username'   => mb_substr($username, 0, 50),
            ':ip_address' => getClientIp(),
            ':user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (Exception $e) {
        error_log('Failed login logging failed: ' . $e->getMessage());
    }
}
