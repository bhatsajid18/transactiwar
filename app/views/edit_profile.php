<?php
ob_start();
?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Edit Profile Info -->
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Profile</h1>

        <form method="POST" action="/profile/update">
            <?= $csrfField ?>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" disabled
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed"
                    value="<?= sanitizeOutput($user['username']) ?>">
                <p class="text-xs text-gray-400 mt-1">Username cannot be changed</p>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    value="<?= sanitizeOutput($user['email']) ?>">
            </div>

            <div class="mb-4">
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="full_name" name="full_name" maxlength="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    value="<?= sanitizeOutput($user['full_name']) ?>">
            </div>

            <div class="mb-6">
                <label for="biography" class="block text-sm font-medium text-gray-700 mb-1">Biography</label>
                <textarea id="biography" name="biography" rows="6" maxlength="<?= MAX_BIOGRAPHY_LENGTH ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-y"
                    placeholder="Tell us about yourself..."><?= sanitizeOutput($user['biography']) ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Max <?= number_format(MAX_BIOGRAPHY_LENGTH) ?> characters</p>
            </div>

            <button type="submit"
                class="bg-indigo-600 text-white py-2 px-6 rounded-lg font-medium hover:bg-indigo-700 transition">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Profile Image Upload -->
    <div class="bg-white rounded-xl shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Profile Image</h2>

        <div class="flex items-center space-x-4 mb-4">
            <?php if ($user['profile_image']): ?>
                <img src="/image/<?= sanitizeOutput($user['profile_image']) ?>" alt="Current profile"
                    class="w-16 h-16 rounded-full object-cover border-2 border-indigo-200">
            <?php else: ?>
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-bold">
                    <?= sanitizeOutput(strtoupper(substr($user['username'], 0, 1))) ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="/profile/upload-image" enctype="multipart/form-data">
            <?= $csrfField ?>

            <div class="mb-4">
                <input type="file" name="profile_image" accept=".jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                <p class="text-xs text-gray-400 mt-1">JPEG or PNG only, max 2MB</p>
            </div>

            <button type="submit"
                class="bg-indigo-600 text-white py-2 px-6 rounded-lg font-medium hover:bg-indigo-700 transition">
                Upload Image
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-xl shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Change Password</h2>

        <form method="POST" action="/profile/change-password">
            <?= $csrfField ?>

            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="10"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <p class="text-xs text-gray-400 mt-1">Min 10 chars, must include uppercase, lowercase, number, and special character</p>
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <button type="submit"
                class="bg-red-600 text-white py-2 px-6 rounded-lg font-medium hover:bg-red-700 transition">
                Change Password
            </button>
        </form>
    </div>

    <div class="text-center">
        <a href="/profile" class="text-indigo-600 text-sm hover:underline">&larr; Back to profile</a>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Edit Profile', $content);
