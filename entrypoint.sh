#!/bin/bash
set -e

# Wait for MySQL to be fully ready (beyond just ping)
echo "Waiting for MySQL to accept connections..."
max_retries=30
count=0
while ! php -r '$h=getenv("database.default.hostname")?:(getenv("database_default_hostname")?:(getenv("DB_HOST")?:(getenv("MYSQLHOST")?:"mysql"))); $pt=getenv("database.default.port")?:(getenv("database_default_port")?:(getenv("DB_PORT")?:(getenv("MYSQLPORT")?:"3306"))); $u=getenv("database.default.username")?:(getenv("database_default_username")?:(getenv("DB_USER")?:(getenv("MYSQLUSER")?:"root"))); $p=getenv("database.default.password")?:(getenv("database_default_password")?:(getenv("DB_PASS")?:(getenv("MYSQLPASSWORD")?:"root_password"))); new PDO("mysql:host={$h};port={$pt}", $u, $p);' 2>/dev/null; do
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
CI_ENVIRONMENT=development php spark migrate -n || { echo "FATAL ERROR: Migrations failed. See above for details."; exit 1; }
CI_ENVIRONMENT=development php spark db:seed SystemDefaultSeeder || echo "WARNING: System default seeding skipped."

# Setup container cron jobs
echo "Configuring container cron schedules..."
cat << 'EOF' > /etc/cron.d/photos-cron
# Run master task scheduler every minute
* * * * * php /var/www/html/spark cron:run >> /var/log/cron-run.log 2>&1
EOF

chmod 0644 /etc/cron.d/photos-cron
crontab /etc/cron.d/photos-cron

# Start the cron service
echo "Starting cron service..."
service cron start || cron || echo "WARNING: Could not start cron daemon."

echo "Migrations complete. Starting Apache..."
exec apache2-foreground
