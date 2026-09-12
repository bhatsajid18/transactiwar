<?php
ob_start();
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-indigo-700">TransactiWar</h1>
            <p class="text-gray-500 mt-1">Sign in to your account</p>
        </div>

        <form method="POST" action="/login" autocomplete="off">
            <?= $csrfField ?>

            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" required maxlength="30"
                    pattern="[a-zA-Z0-9_]+"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    value="<?= sanitizeOutput($formData['username'] ?? '') ?>">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                Sign In
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-3">
            Don't have an account? <a href="/register" class="text-indigo-600 hover:underline">Register here</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Login', $content, false);


