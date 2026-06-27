#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Ensure Laravel can write .env (keys, jwt secret)
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction
fi

if ! grep -q '^JWT_SECRET=' .env 2>/dev/null || grep -q '^JWT_SECRET=$' .env; then
  php artisan jwt:secret --force --no-interaction
fi

echo "Running database migrations..."
php artisan migrate --force --no-interaction

if [ ! -f storage/.dev-seeded ]; then
  echo "Seeding database (first run)..."
  php artisan db:seed --force --no-interaction
  touch storage/.dev-seeded
fi

echo "Development API ready on port 8080"
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
