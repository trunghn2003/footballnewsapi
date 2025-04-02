#!/bin/bash

set -e # Exit immediately if a command exits with a non-zero status.

# Install dependencies if they don't exist
if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-progress --no-interaction --optimize-autoloader
fi

# Handle environment configuration
if [ ! -f ".env" ]; then
    echo "Creating env file for env $APP_ENV"
    cp .env.example .env
else
    echo "env file exists."
fi

php artisan optimize:clear
php artisan jwt:secret

# Set proper permissions - AVOID 777
chmod -R 755 storage
chmod -R 755 bootstrap/cache
# chmod -R 755 public/index.php # Not usually needed
# chmod -R 755 public # Not usually needed.  Consider what needs write access.

# Start cron service properly
mkdir -p /var/run
chown www-data:www-data /var/run #Ensure proper ownership

# This function starts cron and handles potential errors
start_cron() {
  #Try removing the pid file and starting cron
  rm -f /var/run/crond.pid
  cron -f
}

#Attempt to start cron, retry a few times if needed
attempt=0
while ! start_cron; do
  if [ $attempt -ge 3 ]; then
    echo "Failed to start cron after multiple attempts. Exiting."
    exit 1
  fi
  attempt=$((attempt+1))
  echo "Cron failed to start. Retrying in 1 second..."
  sleep 1
done

#Start PHP-FPM and Nginx
php-fpm -D
nginx -g "daemon off;"

#Keep the container running
# tail -f /dev/null
