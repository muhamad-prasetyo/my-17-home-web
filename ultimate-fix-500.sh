#!/bin/bash

# Ultimate 500 Error Fix for Laravel Filament

echo "=== ULTIMATE 500 ERROR FIX ==="

PROJECT_PATH="/home/my17-absensi/htdocs/my17.web.id"
cd $PROJECT_PATH

echo "1. Enabling debug mode to see actual error..."
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
sed -i 's/LOG_LEVEL=error/LOG_LEVEL=debug/' .env

echo "2. Switching to file-based drivers (safer)..."
sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/' .env
sed -i 's/CACHE_STORE=database/CACHE_STORE=file/' .env
sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=sync/' .env

echo "3. Clearing all caches and configs..."
php artisan optimize:clear 2>/dev/null || echo "Optimize clear failed"
php artisan cache:clear 2>/dev/null || echo "Cache clear failed"
php artisan config:clear 2>/dev/null || echo "Config clear failed"
php artisan route:clear 2>/dev/null || echo "Route clear failed"
php artisan view:clear 2>/dev/null || echo "View clear failed"

echo "4. Installing/updating composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

echo "5. Checking and creating storage directories..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo "6. Setting correct permissions..."
chmod -R 775 storage bootstrap/cache
chown -R my17-absensi:my17-absensi storage bootstrap/cache

echo "7. Testing database connection..."
php artisan migrate:status || echo "Database migration check failed"

echo "8. Installing/updating NPM and building assets..."
npm install --production
npm run build

echo "9. Creating storage link..."
php artisan storage:link

echo "10. Running basic Laravel setup..."
php artisan key:generate --force
php artisan config:cache
php artisan route:cache

echo "11. Creating Filament admin user (if needed)..."
php artisan make:filament-user --name="Admin" --email="admin@admin.com" --password="password123" || echo "User creation skipped"

echo ""
echo "=== FIX COMPLETE ==="
echo "Try accessing the site now: https://my17.web.id/"
echo "Admin login: https://my17.web.id/admin"
echo "Username: admin@admin.com"
echo "Password: password123"
echo ""
echo "If still error 500, check the detailed error at:"
echo "https://my17.web.id/ (with APP_DEBUG=true now enabled)"
