<?php
/**
 * Input validation helpers.
 * Whitelist approach with strict type checking.
 */

function sanitizeOutput(string $data): string {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function validateUsername(string $username): array {
    $errors = [];
    $username = trim($username);

    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = 'Username must be between 3 and 30 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }

    return $errors;
}

function validateEmail(string $email): array {
    $errors = [];
    $email = trim($email);

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (strlen($email) > 255) {
        $errors[] = 'Email is too long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    return $errors;
}

function validatePassword(string $password): array {
    $errors = [];

    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }

    return $errors;
}

function validateTransferAmount(string $amount): array {
    $errors = [];

    if (empty($amount)) {
        $errors[] = 'Amount is required.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
        $errors[] = 'Amount must be a valid number.';
    } else {
        $num = floatval($amount);
        if ($num <= 0) {
            $errors[] = 'Amount must be greater than zero.';
        }
        if ($num > 999999.99) {
            $errors[] = 'Amount exceeds maximum limit.';
        }
        // Prevent more than 2 decimal places
        if (preg_match('/\.\d{3,}/', $amount)) {
            $errors[] = 'Amount cannot have more than 2 decimal places.';
        }
    }

    return $errors;
}

function validateComment(string $comment): array {
    $errors = [];

    if (mb_strlen($comment, 'UTF-8') > MAX_COMMENT_LENGTH) {
        $errors[] = 'Comment must not exceed ' . MAX_COMMENT_LENGTH . ' characters.';
    }

    return $errors;
}

function validateBiography(string $bio): array {
    $errors = [];

    if (mb_strlen($bio, 'UTF-8') > MAX_BIOGRAPHY_LENGTH) {
        $errors[] = 'Biography must not exceed ' . MAX_BIOGRAPHY_LENGTH . ' characters.';
    }

    return $errors;
}

function validateFullName(string $name): array {
    $errors = [];
    $name = trim($name);

    if (strlen($name) > 100) {
        $errors[] = 'Full name must not exceed 100 characters.';
    }
    if (!empty($name) && !preg_match('/^[a-zA-Z\s\-\.\']+$/', $name)) {
        $errors[] = 'Full name contains invalid characters.';
    }

    return $errors;
}

function validateProfileImage(array $file): array {
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'File size exceeds the maximum limit of 2MB.';
        } else {
            $errors[] = 'File upload failed.';
        }
        return $errors;
    }

    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = 'File size exceeds the maximum limit of 2MB.';
    }

    // Validate MIME type using finfo (not relying on client-reported type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
        $errors[] = 'Only JPEG and PNG images are allowed.';
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS, true)) {
        $errors[] = 'Invalid file extension. Only jpg, jpeg, and png are allowed.';
    }

    // Verify it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = 'The uploaded file is not a valid image.';
    } elseif ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
        $errors[] = 'Image dimensions must not exceed 4000x4000 pixels.';
    }

    return $errors;
}
