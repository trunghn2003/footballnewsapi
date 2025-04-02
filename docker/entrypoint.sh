#!/bin/bash
set -e

if [ ! -f "vendor/autoload.php" ]; then
    echo "Cài đặt Composer dependencies..."
    composer install --no-progress --no-interaction --optimize-autoloader
fi

if [ ! -f ".env" ]; then
    echo "🔄 Tạo file .env từ .env.example..."
    cp .env.example .env
    php artisan key:generate
else
    echo "✅ File .env đã tồn tại"
fi

echo "🛠️ Tối ưu framework..."
php artisan optimize:clear > /dev/null 2>&1
php artisan jwt:secret --no-interaction

chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

cron -f & php-fpm -D
nginx -g "daemon off;"
echo "🚀 Bắt đầu chạy cronjob và nginx..."
tail -f /var/log/cron/cron.log /var/log/nginx/error.log
