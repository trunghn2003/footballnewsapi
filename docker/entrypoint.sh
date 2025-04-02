#!/bin/bash

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-progress --no-interaction
fi

if [ ! -f ".env" ]; then
    echo "Creating env file for env $APP_ENV"
    cp .env.example .env
else
    echo "env file exists."
fi
php artisan optimize:clear
php artisan jwt:secret
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chmod -R 777 public/index.php
chmod -R 777 public

# Start cron service properly
mkdir -p /var/run
touch /var/run/crond.pid
chmod 0644 /var/run/crond.pid
cron -f

php-fpm -D
nginx -g "daemon off;"
