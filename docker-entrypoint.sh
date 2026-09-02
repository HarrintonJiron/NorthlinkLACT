#!/bin/sh

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL..."
while ! nc -z postgres 5432; do
  sleep 1
done
echo "PostgreSQL is ready!"

# Wait for Redis to be ready
echo "Waiting for Redis..."
while ! nc -z redis 6379; do
  sleep 1
done
echo "Redis is ready!"

if [ "${USE_VITE_DEV_SERVER:-false}" != "true" ]; then
  rm -f public/hot
fi

# Run migrations
php artisan migrate --force --no-interaction

# Start server
exec php artisan serve --host=0.0.0.0 --port=8000
