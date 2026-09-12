<?php
ob_start();
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Balance</h3>
        <p class="text-3xl font-bold text-indigo-700 mt-2">Rs. <?= sanitizeOutput(number_format(floatval($user['balance']), 2)) ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Username</h3>
        <p class="text-xl font-semibold text-gray-800 mt-2"><?= sanitizeOutput($user['username']) ?></p>
        <p class="text-sm text-gray-400">ID: <?= (int)$user['id'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Quick Actions</h3>
        <div class="mt-3 flex flex-col space-y-2">
            <a href="/transfer" class="bg-indigo-600 text-white text-center py-2 px-4 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Send Money</a>
            <a href="/search" class="bg-gray-100 text-gray-700 text-center py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Find Users</a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Recent Transactions</h2>
        <a href="/transactions" class="text-indigo-600 text-sm hover:underline">View All</a>
    </div>

    <?php if (empty($recentTransactions)): ?>
        <p class="text-gray-400 text-center py-8">No transactions yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="pb-2 font-medium">Type</th>
                        <th class="pb-2 font-medium">User</th>
                        <th class="pb-2 font-medium">Amount</th>
                        <th class="pb-2 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentTransactions as $tx): ?>
                    <?php
                    $isSender = ((int)$tx['sender_id'] === getCurrentUserId());
                    $otherUser = $isSender ? $tx['receiver_username'] : $tx['sender_username'];
                    ?>
                    <tr class="border-b border-gray-50">
                        <td class="py-3">
                            <?php if ($isSender): ?>
                                <span class="text-red-600 font-medium">Sent</span>
                            <?php else: ?>
                                <span class="text-green-600 font-medium">Received</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3"><?= sanitizeOutput($otherUser) ?></td>
                        <td class="py-3 font-medium <?= $isSender ? 'text-red-600' : 'text-green-600' ?>">
                            <?= $isSender ? '-' : '+' ?>Rs. <?= sanitizeOutput(number_format(floatval($tx['amount']), 2)) ?>
                        </td>
                        <td class="py-3 text-gray-400"><?= sanitizeOutput(date('M j, g:i A', strtotime($tx['created_at'] . ' UTC'))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
renderLayout('Dashboard', $content);
