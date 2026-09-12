<?php
ob_start();
?>

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Transfer Money</h1>
        <p class="text-gray-500 mb-6">Your balance: <span class="font-semibold text-indigo-700">Rs. <?= sanitizeOutput(number_format($balance, 2)) ?></span></p>

        <form method="POST" action="/transfer" autocomplete="off">
            <?= $csrfField ?>

            <div class="mb-4">
                <label for="receiver_id" class="block text-sm font-medium text-gray-700 mb-1">Receiver User ID</label>
                <input type="number" id="receiver_id" name="receiver_id" required min="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    value="<?= $receiverUser ? (int)$receiverUser['id'] : '' ?>">
                <?php if ($receiverUser): ?>
                    <p class="text-sm text-green-600 mt-1">Sending to: <?= sanitizeOutput($receiverUser['username']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (Rs.)</label>
                <input type="text" id="amount" name="amount" required
                    pattern="^\d+(\.\d{1,2})?$"
                    title="Enter a valid amount (e.g., 10 or 10.50)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="0.00">
            </div>

            <div class="mb-6">
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Comment (optional)</label>
                <textarea id="comment" name="comment" rows="3" maxlength="<?= MAX_COMMENT_LENGTH ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-y"
                    placeholder="Add a note for the receiver..."></textarea>
                <p class="text-xs text-gray-400 mt-1">Max <?= MAX_COMMENT_LENGTH ?> characters. Visible to receiver only.</p>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                Send Money
            </button>
        </form>

        <p class="text-center text-sm text-gray-400 mt-4">
            <a href="/search" class="text-indigo-600 hover:underline">Find a user</a> to send money to
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Transfer Money', $content);
