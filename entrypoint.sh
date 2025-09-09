#!/bin/sh
set -e

# Удаляем расширение psr
apk del --no-cache php83-pecl-psr || true

# Чиним права
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Запускаем стандартный supervisord
exec /root/run.sh
