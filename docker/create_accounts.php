<?php
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$name = getenv('DB_NAME') ?: 'transactiwar';
$user = getenv('DB_USER') ?: 'twuser';
$pass = getenv('DB_PASS') ?: 'MySecureDBPass@2026!';   // ← YOUR PASSWORD HERE

$dsn = "pgsql:host={$host};port={$port};dbname={$name}";

try {
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $password = 'Test@12345678';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $accounts = [
        ['alice',   'alice@transactiwar.local',   'Alice Johnson'],
        ['bob',     'bob@transactiwar.local',     'Bob Smith'],
        ['charlie', 'charlie@transactiwar.local', 'Charlie Brown'],
        ['dave',    'dave@transactiwar.local',    'Dave Wilson'],
        ['eve',     'eve@transactiwar.local',     'Eve Davis'],
    ];

    $stmt = $db->prepare(
        'INSERT INTO users (username, email, password_hash, full_name, biography, balance)
         VALUES (:username, :email, :hash, :full_name, :bio, 100.00)
         ON CONFLICT (username) DO NOTHING'
    );

    foreach ($accounts as $account) {
        $stmt->execute([
            ':username'  => $account[0],
            ':email'     => $account[1],
            ':hash'      => $hash,
            ':full_name' => $account[2],
            ':bio'       => 'Test account for ' . $account[2] . '.',
        ]);
        echo "Created account: {$account[0]}\n";
    }

    echo "\nAll test accounts created successfully!\n";
    echo "Password for all accounts: Test@12345678\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}