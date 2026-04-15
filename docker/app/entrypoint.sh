#!/bin/sh

set -e

cd /var/www/html

until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-goy_user}" >/dev/null 2>&1; do
    sleep 2
done

if [ ! -f .env ]; then
    cp .env.example .env
fi

php artisan key:generate --force >/dev/null 2>&1 || true
php artisan migrate --force
php artisan storage:link --force || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
