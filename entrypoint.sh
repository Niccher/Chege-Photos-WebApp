#!/bin/bash
set -e

# Wait for MySQL to be fully ready (beyond just ping)
echo "Waiting for MySQL to accept connections..."
max_retries=30
count=0
while ! php -r "new PDO('mysql:host=mysql;port=3306', 'root', 'root_password');" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_retries ]; then
        echo "ERROR: MySQL did not become ready in time. Starting Apache without migrations."
        exec apache2-foreground
    fi
    echo "MySQL not ready yet... retrying ($count/$max_retries)"
    sleep 2
done

echo "MySQL is ready!"

# Run migrations (safe to run multiple times — only applies pending migrations)
echo "Running database migrations..."
cd /var/www/html
php spark migrate --all 2>&1 || echo "WARNING: Migration encountered an issue. Check logs."

echo "Migrations complete. Starting Apache..."
exec apache2-foreground
