#!/bin/bash

# Quick Fix untuk 500 Error Laravel Filament

echo "=== QUICK FIX FOR 500 ERROR ==="

PROJECT_PATH="/home/my17-absensi/htdocs/my17.web.id"
cd $PROJECT_PATH

echo "1. Setting safe .env configuration..."
# Backup original .env
cp .env .env.backup.$(date +%Y%m%d-%H%M%S)

# Create safe .env
cat > .env << 'EOF'
APP_NAME="MY 17"
APP_ENV=production
APP_KEY=base64:642AC+YRqnM49ERIWDVWQ+tFhvLkmncUfK85IZquTmg=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://my17.web.id

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my17absensi
DB_USERNAME=absensimy17
DB_PASSWORD=yIiNcnzu96gRU8x39I

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync

CACHE_STORE=file
CACHE_PREFIX=

VITE_APP_NAME="${APP_NAME}"
EOF

echo "2. Clearing all caches and config..."
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "3. Testing database connection..."
php artisan migrate:status

echo "4. Regenerating optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "5. Setting correct permissions..."
chmod -R 775 storage bootstrap/cache
chown -R my17-absensi:my17-absensi storage bootstrap/cache

echo "6. Testing basic functionality..."
php artisan about

echo ""
echo "=== QUICK FIX COMPLETE ==="
echo "Try accessing https://my17.web.id/admin now"
echo ""
echo "If still getting 500 error, check storage/logs/laravel.log for details"
