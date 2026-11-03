#!/bin/sh

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL..."
while ! nc -z "${DB_HOST:-postgres}" "${DB_PORT:-5432}"; do
  sleep 1
done
echo "PostgreSQL is ready!"

# Wait for Redis to be ready
echo "Waiting for Redis..."
while ! nc -z "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}"; do
  sleep 1
done
echo "Redis is ready!"

if [ "${USE_VITE_DEV_SERVER:-false}" != "true" ]; then
  rm -f public/hot
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

case "${APP_ROLE:-web}" in
  web)
    php artisan migrate --force --no-interaction
    exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
    ;;
  worker)
    exec php artisan queue:work --sleep=2 --tries=3 --timeout=120 --max-time=3600
    ;;
  scheduler)
    exec php artisan schedule:work
    ;;
  *)
    echo "Unknown APP_ROLE: ${APP_ROLE}"
    exit 1
    ;;
esac
