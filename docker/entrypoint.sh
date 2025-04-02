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

echo "🕒 Cấu hình cron job..."
mkdir -p /var/log/cron
touch /var/log/cron/cron.log
chown www-data:www-data /var/log/cron/cron.log

echo "🚀 Khởi động cron service..."
service cron start
echo "* * * * * www-data cd /var/www && php artisan schedule:run >> /var/log/cron/cron.log 2>&1" | tee /etc/cron.d/laravel > /dev/null
crontab /etc/cron.d/laravel

echo "🌐 Khởi động Nginx và PHP-FPM..."
service nginx start
php-fpm -D

# 8. GIỮ CONTAINER HOẠT ĐỘNG
echo "🐋 Container đã sẵn sàng!"
tail -f /var/log/cron/cron.log /var/log/nginx/error.log
