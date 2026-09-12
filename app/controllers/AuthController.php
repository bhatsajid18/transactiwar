<?php
/**
 * Authentication controller.
 * Handles registration, login, and logout with full security controls.
 */

class AuthController {

    public function showRegister(): void {
        initSession();
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        logActivity('/register', 'view_register_page');
        $csrfField = getCsrfTokenField();
        require __DIR__ . '/../views/register.php';
    }

    public function register(): void {
        initSession();

        if (!validateCsrfToken()) {
            logAttackEvent(null, 'anonymous', 'csrf_violation', 'CSRF token mismatch on registration', 'high');
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /register');
            exit;
        }

        // Rate limit registration by IP
        $ip = getClientIp();
        if (!checkRateLimit($ip, 'register', 10, 3600)) {
            logAttackEvent(null, 'anonymous', 'rate_limit_register', 'Registration rate limit exceeded', 'medium');
            $_SESSION['flash_error'] = 'Too many registration attempts. Please try again later.';
            header('Location: /register');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        $errors = [];
        $errors = array_merge($errors, validateUsername($username));
        $errors = array_merge($errors, validateEmail($email));
        $errors = array_merge($errors, validatePassword($password));
        $errors = array_merge($errors, validateFullName($fullName));

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        // Check uniqueness (generic message to prevent enumeration)
        if (empty($errors)) {
            if (User::findByUsername($username) || User::findByEmail($email)) {
                $errors[] = 'An account with that username or email already exists.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('<br>', array_map('htmlspecialchars', $errors));
            $_SESSION['form_data'] = [
                'username'  => $username,
                'email'     => $email,
                'full_name' => $fullName,
            ];
            header('Location: /register');
            exit;
        }

        $userId = User::create($username, $email, $password, $fullName);

        if ($userId) {
            logActivity('/register', 'user_registered: ' . $username);
            $_SESSION['flash_success'] = 'Registration successful! Please log in.';
            header('Location: /login');
        } else {
            $_SESSION['flash_error'] = 'Registration failed. Please try again.';
            header('Location: /register');
        }
        exit;
    }

    public function showLogin(): void {
        initSession();
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        logActivity('/login', 'view_login_page');
        $csrfField = getCsrfTokenField();
        require __DIR__ . '/../views/login.php';
    }

    public function login(): void {
        initSession();

        if (!validateCsrfToken()) {
            logAttackEvent(null, 'anonymous', 'csrf_violation', 'CSRF token mismatch on login', 'high');
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['flash_error'] = 'Username and password are required.';
            header('Location: /login');
            exit;
        }

        // Check rate limit by IP
        $ip = getClientIp();
        if (!checkRateLimit($ip, 'login', MAX_LOGIN_ATTEMPTS * 3, LOCKOUT_DURATION)) {
            logAttackEvent(null, $username, 'brute_force_ip', 'IP-based login rate limit exceeded', 'high');
            $_SESSION['flash_error'] = 'Too many login attempts from this IP. Please try again later.';
            header('Location: /login');
            exit;
        }

        // Check account lockout
        if (isAccountLocked($username)) {
            logAttackEvent(null, $username, 'locked_account_login', 'Attempt to login to locked account', 'medium');
            $_SESSION['flash_error'] = 'Account is temporarily locked due to too many failed attempts. Please try again in 10 minutes.';
            header('Location: /login');
            exit;
        }

        $user = User::findByUsername($username);

        // Use constant-time check even if user doesn't exist (prevent user enumeration)
        if (!$user) {
            // Hash a dummy password to prevent timing attacks
            password_verify($password, '$2y$12$dummyhashtopreventtimingattacksenumeration..');
            logFailedLogin($username);
            logActivity('/login', 'failed_login: ' . $username);
            $_SESSION['flash_error'] = 'Invalid username or password.';
            header('Location: /login');
            exit;
        }

        if (!User::verifyPassword($password, $user['password_hash'])) {
            logFailedLogin($username);
            logActivity('/login', 'failed_login: ' . $username);
            $_SESSION['flash_error'] = 'Invalid username or password.';
            header('Location: /login');
            exit;
        }

        // Successful login
        createAuthSession((int)$user['id'], $user['username']);
        logActivity('/login', 'successful_login');

        // Clear failed login attempts for this user from this IP
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('DELETE FROM failed_logins WHERE username = :username AND ip_address = :ip');
            $stmt->execute([':username' => $username, ':ip' => $ip]);
        } catch (Exception $e) {
            error_log('Failed to clear login attempts: ' . $e->getMessage());
        }

        header('Location: /dashboard');
        exit;
    }

    public function logout(): void {
        initSession();

        if (!validateCsrfToken()) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'csrf_violation', 'CSRF token mismatch on logout', 'high');
            header('Location: /dashboard');
            exit;
        }

        $username = getCurrentUsername() ?? 'anonymous';
        logActivity('/logout', 'user_logged_out: ' . $username);
        destroySession();
        session_start();
        $_SESSION['flash_success'] = 'You have been logged out successfully.';
        header('Location: /login');
        exit;
    }
}
