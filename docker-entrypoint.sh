#!/bin/sh

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
while ! nc -z mysql 3306; do
  sleep 1
done
echo "MySQL is ready!"

# Wait for Redis to be ready
echo "Waiting for Redis..."
while ! nc -z redis 6379; do
  sleep 1
done
echo "Redis is ready!"

# Generate key
php artisan key:generate --ansi

# Run migrations
php artisan migrate:fresh --force

# Start server
php artisan serve --host=0.0.0.0 --port=8000
