<?php
/**
 * Main layout template.
 * All views are rendered within this layout.
 */

function renderLayout(string $title, string $content, bool $showNav = true): void {
    $flashError   = $_SESSION['flash_error'] ?? null;
    $flashSuccess = $_SESSION['flash_success'] ?? null;
    unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= sanitizeOutput($title) ?> - TransactiWar</title>
    <script src="/assets/tailwind.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
<?php if ($showNav && isLoggedIn()): ?>
    <nav class="bg-indigo-700 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-xl font-bold tracking-tight">TransactiWar</a>
                    <div class="hidden md:flex space-x-1">
                        <a href="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600 transition">Dashboard</a>
                        <a href="/search" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600 transition">Search</a>
                        <a href="/transfer" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600 transition">Transfer</a>
                        <a href="/transactions" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600 transition">History</a>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="/profile" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600 transition">
                        <?= sanitizeOutput(getCurrentUsername()) ?>
                    </a>
                    <form method="POST" action="/logout" class="inline">
                        <?= getCsrfTokenField() ?>
                        <button type="submit" class="bg-indigo-800 px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-900 transition">Logout</button>
                    </form>
                </div>
            </div>
            <!-- Mobile menu -->
            <div class="md:hidden pb-3 flex space-x-2">
                <a href="/dashboard" class="px-3 py-1 rounded text-sm hover:bg-indigo-600">Dashboard</a>
                <a href="/search" class="px-3 py-1 rounded text-sm hover:bg-indigo-600">Search</a>
                <a href="/transfer" class="px-3 py-1 rounded text-sm hover:bg-indigo-600">Transfer</a>
                <a href="/transactions" class="px-3 py-1 rounded text-sm hover:bg-indigo-600">History</a>
            </div>
        </div>
    </nav>
<?php endif; ?>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <?php if ($flashError): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <?= implode('<br>', array_map('htmlspecialchars', explode('<br>', $flashError))) ?>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                <?= sanitizeOutput($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>


</body>
</html>
<?php
}
