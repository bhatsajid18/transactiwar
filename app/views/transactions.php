<?php
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Transaction History</h1>
                <p class="text-gray-500">Balance: <span class="font-semibold text-indigo-700">Rs. <?= sanitizeOutput(number_format($balance, 2)) ?></span></p>
            </div>
            <a href="/transfer" class="bg-indigo-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">New Transfer</a>
        </div>

        <?php if (empty($transactions)): ?>
            <p class="text-gray-400 text-center py-12">No transactions yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="pb-3 font-medium">ID</th>
                            <th class="pb-3 font-medium">Type</th>
                            <th class="pb-3 font-medium">User</th>
                            <th class="pb-3 font-medium">Amount</th>
                            <th class="pb-3 font-medium">Comment</th>
                            <th class="pb-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $isSender = ((int)$tx['sender_id'] === getCurrentUserId());
                        $otherUser = $isSender ? $tx['receiver_username'] : $tx['sender_username'];
                        $otherId = $isSender ? (int)$tx['receiver_id'] : (int)$tx['sender_id'];
                        // Comments visible to both sender and receiver
                        $showComment = !empty($tx['comment']);
                        ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="py-3 text-gray-400">#<?= (int)$tx['id'] ?></td>
                            <td class="py-3">
                                <?php if ($isSender): ?>
                                    <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded">Sent</span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">Received</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3">
                                <a href="/user/<?= $otherId ?>" class="text-indigo-600 hover:underline"><?= sanitizeOutput($otherUser) ?></a>
                            </td>
                            <td class="py-3 font-medium <?= $isSender ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $isSender ? '-' : '+' ?>Rs. <?= sanitizeOutput(number_format(floatval($tx['amount']), 2)) ?>
                            </td>
                            <td class="py-3 text-gray-500 max-w-xs truncate">
                                <?php if ($showComment): ?>
                                    <?= sanitizeOutput($tx['comment']) ?>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-gray-400 whitespace-nowrap"><?= sanitizeOutput(date('M j, Y g:i A', strtotime($tx['created_at'] . ' UTC'))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center space-x-2 mt-6">
                    <?php if ($page > 1): ?>
                        <a href="/transactions?page=<?= $page - 1 ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="px-3 py-1 bg-indigo-600 text-white rounded text-sm"><?= $i ?></span>
                        <?php else: ?>
                            <a href="/transactions?page=<?= $i ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="/transactions?page=<?= $page + 1 ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Transaction History', $content);
