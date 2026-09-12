<?php
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
            <a href="/profile/edit" class="bg-indigo-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Edit Profile</a>
        </div>

        <div class="flex items-start space-x-6 mb-6">
            <div class="flex-shrink-0">
                <?php if ($user['profile_image']): ?>
                    <img src="/image/<?= sanitizeOutput($user['profile_image']) ?>" alt="Profile"
                        class="w-24 h-24 rounded-full object-cover border-2 border-indigo-200">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-3xl font-bold">
                        <?= sanitizeOutput(strtoupper(substr($user['username'], 0, 1))) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-800"><?= sanitizeOutput($user['username']) ?></h2>
                <p class="text-gray-500">ID: <?= (int)$user['id'] ?></p>
                <?php if ($user['full_name']): ?>
                    <p class="text-gray-600 mt-1"><?= sanitizeOutput($user['full_name']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Email</p>
                <p class="font-medium text-gray-800"><?= sanitizeOutput($user['email']) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Balance</p>
                <p class="font-medium text-indigo-700">Rs. <?= sanitizeOutput(number_format(floatval($user['balance']), 2)) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Member Since</p>
                <p class="font-medium text-gray-800"><?= sanitizeOutput(date('F j, Y', strtotime($user['created_at']))) ?></p>
            </div>
        </div>

        <?php if (!empty($user['biography'])): ?>
            <div class="border-t pt-4">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">Biography</h3>
                <div class="text-gray-700 whitespace-pre-wrap break-words"><?= sanitizeOutput($user['biography']) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('My Profile', $content);
