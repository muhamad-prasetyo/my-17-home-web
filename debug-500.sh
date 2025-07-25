#!/bin/bash

# Debug 500 Error untuk Laravel Filament
# Jalankan script ini di server untuk mencari penyebab error 500

echo "=== DEBUGGING 500 SERVER ERROR ==="
echo ""

PROJECT_PATH="/home/my17-absensi/htdocs/my17.web.id"
cd $PROJECT_PATH

echo "1. Checking Laravel logs..."
echo "Recent errors in storage/logs/laravel.log:"
if [ -f "storage/logs/laravel.log" ]; then
    tail -20 storage/logs/laravel.log
else
    echo "No laravel.log found"
fi

echo ""
echo "2. Testing database connection..."
php artisan tinker --execute="
try {
    \DB::connection()->getPdo();
    echo 'Database connection: SUCCESS\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "3. Checking key configuration..."
php artisan about | grep -E "(Environment|Debug Mode|URL|Key)"

echo ""
echo "4. Testing basic Laravel functionality..."
php artisan route:list --compact | head -10

echo ""
echo "5. Checking storage permissions..."
ls -la storage/
ls -la storage/logs/
ls -la bootstrap/cache/

echo ""
echo "6. Checking composer dependencies..."
composer check-platform-reqs

echo ""
echo "7. Checking Filament installation..."
php artisan about | grep -i filament
php artisan package:discover

echo ""
echo "8. Clearing all caches..."
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "9. Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "10. Testing artisan commands..."
php artisan --version

echo ""
echo "=== DEBUG COMPLETE ==="
echo ""
echo "Common 500 error solutions:"
echo "1. Database connection issues - Check .env DB settings"
echo "2. Missing APP_KEY - Run: php artisan key:generate"
echo "3. Permission issues - Run: chmod -R 775 storage bootstrap/cache"
echo "4. Cache corruption - Run: php artisan optimize:clear"
echo "5. Missing dependencies - Run: composer install"
