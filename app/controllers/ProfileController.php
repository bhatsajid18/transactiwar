<?php
/**
 * Profile controller.
 * Handles profile viewing, editing, and image uploads securely.
 */

class ProfileController {

    public function showProfile(): void {
        requireAuth();
        $userId = getCurrentUserId();
        $user = User::findById($userId);

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            header('Location: /dashboard');
            exit;
        }

        logActivity('/profile', 'view_own_profile');
        $csrfField = getCsrfTokenField();
        $isOwnProfile = true;
        require __DIR__ . '/../views/profile.php';
    }

    public function viewProfile(int $id): void {
        requireAuth();

        // IDOR protection: validate the requested user exists
        $user = User::findById($id);
        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            header('Location: /search');
            exit;
        }

        logActivity('/profile/view/' . $id, 'view_user_profile: ' . $user['username']);
        $isOwnProfile = ($id === getCurrentUserId());
        $csrfField = getCsrfTokenField();
        require __DIR__ . '/../views/view_profile.php';
    }

    public function showEditProfile(): void {
        requireAuth();
        $userId = getCurrentUserId();
        $user = User::findById($userId);

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            header('Location: /dashboard');
            exit;
        }

        logActivity('/profile/edit', 'view_edit_profile');
        $csrfField = getCsrfTokenField();
        require __DIR__ . '/../views/edit_profile.php';
    }

    public function updateProfile(): void {
        requireAuth();

        if (!validateCsrfToken()) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'csrf_violation', 'CSRF token mismatch on profile update', 'high');
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /profile/edit');
            exit;
        }

        $userId = getCurrentUserId();
        $email     = trim($_POST['email'] ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $biography = $_POST['biography'] ?? '';

        $errors = [];
        $errors = array_merge($errors, validateEmail($email));
        $errors = array_merge($errors, validateFullName($fullName));
        $errors = array_merge($errors, validateBiography($biography));

        // Check email uniqueness (exclude current user)
        if (empty($errors)) {
            $existingUser = User::findByEmail($email);
            if ($existingUser && (int)$existingUser['id'] !== $userId) {
                $errors[] = 'Email is already in use by another account.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('<br>', array_map('htmlspecialchars', $errors));
            header('Location: /profile/edit');
            exit;
        }

        User::updateProfile($userId, $email, $fullName, $biography);
        logActivity('/profile/edit', 'profile_updated');
        $_SESSION['flash_success'] = 'Profile updated successfully.';
        header('Location: /profile');
        exit;
    }

    public function uploadImage(): void {
        requireAuth();

        if (!validateCsrfToken()) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'csrf_violation', 'CSRF token mismatch on image upload', 'high');
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /profile/edit');
            exit;
        }

        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['flash_error'] = 'No file selected.';
            header('Location: /profile/edit');
            exit;
        }

        $file = $_FILES['profile_image'];
        $errors = validateProfileImage($file);

        if (!empty($errors)) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'malicious_upload', implode('; ', $errors), 'high');
            $_SESSION['flash_error'] = implode('<br>', array_map('htmlspecialchars', $errors));
            header('Location: /profile/edit');
            exit;
        }

        // Re-encode image to strip metadata and embedded payloads
        $tmpPath = $file['tmp_name'];
        $mimeType = mime_content_type($tmpPath);
        $srcImage = null;

        if ($mimeType === 'image/jpeg') {
            $srcImage = @imagecreatefromjpeg($tmpPath);
        } elseif ($mimeType === 'image/png') {
            $srcImage = @imagecreatefrompng($tmpPath);
        }

        if (!$srcImage) {
            $_SESSION['flash_error'] = 'Failed to process image. The file may be corrupted.';
            header('Location: /profile/edit');
            exit;
        }

        // Generate random filename
        $ext = ($mimeType === 'image/png') ? 'png' : 'jpg';
        $newFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = UPLOAD_DIR . $newFilename;

        // Save re-encoded image (strips all metadata/payloads)
        $saved = false;
        if ($ext === 'png') {
            $saved = imagepng($srcImage, $destination, 6);
        } else {
            $saved = imagejpeg($srcImage, $destination, 85);
        }
        imagedestroy($srcImage);

        if (!$saved) {
            $_SESSION['flash_error'] = 'Failed to save image. Please try again.';
            header('Location: /profile/edit');
            exit;
        }

        // Remove old profile image if exists
        $userId = getCurrentUserId();
        $currentUser = User::findById($userId);
        $img = $currentUser['profile_image'] ?? null;
        if ($img && preg_match('/^[a-f0-9]+\.(jpg|jpeg|png)$/i', $img)) {
            $oldFile = UPLOAD_DIR . $img;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        User::updateProfileImage($userId, $newFilename);
        logActivity('/profile/upload-image', 'profile_image_uploaded');
        $_SESSION['flash_success'] = 'Profile image updated successfully.';
        header('Location: /profile');
        exit;
    }

    /**
     * Serve profile images securely through PHP (not direct file access).
     */
    public function serveImage(string $filename): void {
        requireAuth();

        // Sanitize filename — only allow alphanumeric, dots, and hyphens
        $filename = basename($filename);
        if (!preg_match('/^[a-f0-9]+\.(jpg|jpeg|png)$/i', $filename)) {
            http_response_code(404);
            exit;
        }

        $filepath = UPLOAD_DIR . $filename;

        if (!file_exists($filepath)) {
            http_response_code(404);
            exit;
        }

        // Verify MIME type before serving
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filepath);

        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
            http_response_code(403);
            exit;
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($filepath);
        exit;
    }

    public function changePassword(): void {
        requireAuth();

        if (!validateCsrfToken()) {
            logAttackEvent(getCurrentUserId(), getCurrentUsername(), 'csrf_violation', 'CSRF token mismatch on password change', 'high');
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
            header('Location: /profile/edit');
            exit;
        }

        // Rate limit password change attempts
        $ip = getClientIp();
        if (!checkRateLimit($ip . ':' . getCurrentUserId(), 'password_change', 5, 60)) {
            $_SESSION['flash_error'] = 'Too many password change attempts. Please try again later.';
            header('Location: /profile/edit');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $_SESSION['flash_error'] = 'Current password is required.';
            header('Location: /profile/edit');
            exit;
        }

        // Verify current password
        $userId = getCurrentUserId();
        $hash = User::getPasswordHash($userId);
        if (!$hash || !password_verify($currentPassword, $hash)) {
            logActivity('/profile/change-password', 'failed_password_change');
            $_SESSION['flash_error'] = 'Current password is incorrect.';
            header('Location: /profile/edit');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = 'New passwords do not match.';
            header('Location: /profile/edit');
            exit;
        }

        $errors = validatePassword($newPassword);
        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('<br>', array_map('htmlspecialchars', $errors));
            header('Location: /profile/edit');
            exit;
        }

        User::updatePassword($userId, $newPassword);
        logActivity('/profile/change-password', 'password_changed');

        // Regenerate session after password change
        session_regenerate_id(true);

        $_SESSION['flash_success'] = 'Password changed successfully.';
        header('Location: /profile');
        exit;
    }
}
