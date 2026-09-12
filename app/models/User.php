<?php
/**
 * User model — handles all user-related database operations.
 * Uses prepared statements exclusively to prevent SQL injection.
 */

class User {

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, username, email, full_name, biography, profile_image, balance, created_at FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByUsername(string $username): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $username, string $email, string $password, string $fullName = ''): ?int {
        $db = Database::getConnection();
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, balance)
             VALUES (:username, :email, :password_hash, :full_name, 100.00)
             RETURNING id'
        );
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
            ':full_name'     => $fullName,
        ]);

        $result = $stmt->fetch();
        return $result ? (int)$result['id'] : null;
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function updateProfile(int $id, string $email, string $fullName, string $biography): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE users SET email = :email, full_name = :full_name, biography = :biography, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        return $stmt->execute([
            ':email'     => $email,
            ':full_name' => $fullName,
            ':biography' => $biography,
            ':id'        => $id,
        ]);
    }

    public static function updatePassword(int $id, string $newPassword): bool {
        $db = Database::getConnection();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        return $stmt->execute([':hash' => $hash, ':id' => $id]);
    }

    public static function updateProfileImage(int $id, string $filename): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE users SET profile_image = :image, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        return $stmt->execute([':image' => $filename, ':id' => $id]);
    }

    public static function getBalance(int $id): float {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT balance FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ? floatval($result['balance']) : 0.0;
    }

    public static function search(string $query, int $limit = 20): array {
        $db = Database::getConnection();
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $query);
        $searchTerm = '%' . $escaped . '%';

        // Search by username or ID
        if (is_numeric($query)) {
            $stmt = $db->prepare(
                'SELECT id, username, full_name, profile_image FROM users
                 WHERE id = :exact_id OR username ILIKE :term
                 ORDER BY username ASC LIMIT :lim'
            );
            $stmt->bindValue(':exact_id', (int)$query, PDO::PARAM_INT);
            $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        } else {
            $stmt = $db->prepare(
                'SELECT id, username, full_name, profile_image FROM users
                 WHERE username ILIKE :term
                 ORDER BY username ASC LIMIT :lim'
            );
            $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getPasswordHash(int $id): ?string {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ? $result['password_hash'] : null;
    }
}
