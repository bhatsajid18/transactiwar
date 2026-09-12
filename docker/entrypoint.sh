#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for database..."
until php -r "
    \$dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME');
    try { new PDO(\$dsn, getenv('DB_USER'), getenv('DB_PASS')); echo 'ok'; }
    catch (Exception \$e) { exit(1); }
" 2>/dev/null; do
    sleep 2
done
echo "Database is ready!"

# Create test accounts with proper bcrypt hashes
echo "Creating test accounts..."
php /var/www/create_accounts.php || true

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
