<?php
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Search Users</h1>

        <form method="GET" action="/search" class="mb-6" >
            <div class="flex space-x-2">
                <input type="text" name="q" placeholder="Search by username or user ID..."
                    maxlength="100"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    value="<?= sanitizeOutput($query) ?>">
                <button type="submit"
                    class="bg-indigo-600 text-white py-2 px-6 rounded-lg font-medium hover:bg-indigo-700 transition">
                    Search
                </button>
            </div>
        </form>

        <?php if (!empty($query)): ?>
            <?php if (empty($results)): ?>
                <p class="text-gray-400 text-center py-8">No users found for "<?= sanitizeOutput($query) ?>"</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($results as $u): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition">
                            <div class="flex items-center space-x-3">
                                <?php if ($u['profile_image']): ?>
                                    <img src="/image/<?= sanitizeOutput($u['profile_image']) ?>" alt=""
                                        class="w-10 h-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                                        <?= sanitizeOutput(strtoupper(substr($u['username'], 0, 1))) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-800"><?= sanitizeOutput($u['username']) ?></p>
                                    <p class="text-sm text-gray-400">ID: <?= (int)$u['id'] ?>
                                        <?php if ($u['full_name']): ?>
                                            &middot; <?= sanitizeOutput($u['full_name']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="/user/<?= (int)$u['id'] ?>" class="text-indigo-600 text-sm hover:underline">Profile</a>
                                <?php if ((int)$u['id'] !== getCurrentUserId()): ?>
                                    <a href="/transfer?to=<?= (int)$u['id'] ?>" class="text-green-600 text-sm hover:underline">Send</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-sm text-gray-400 mt-4"><?= count($results) ?> result(s) found</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Search Users', $content);
