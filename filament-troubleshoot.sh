#!/bin/bash

# Laravel Filament Troubleshooting Script untuk CloudPanel
# Jalankan jika ada masalah dengan Filament admin panel

echo "=== LARAVEL FILAMENT TROUBLESHOOTING ==="
echo ""

PROJECT_PATH="/home/my17-absensi/htdocs/my17.web.id"
cd $PROJECT_PATH

echo "1. Checking Filament installation..."
php artisan about | grep -i filament

echo ""
echo "2. Clearing all caches..."
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "3. Rebuilding Filament components..."
php artisan filament:cache-components

echo ""
echo "4. Re-optimizing for production..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "5. Checking storage permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
ls -la storage/

echo ""
echo "6. Checking public assets..."
ls -la public/build/

echo ""
echo "7. Testing Filament routes..."
php artisan route:list | grep -i filament

echo ""
echo "8. Checking for Filament admin user..."
php artisan tinker --execute="echo 'User count: ' . App\Models\User::count();"

echo ""
echo "=== TROUBLESHOOTING COMPLETE ==="
echo ""
echo "Common Filament issues and solutions:"
echo "1. 404 on /admin - Check if routes are cached properly"
echo "2. Missing CSS/JS - Run 'npm run build' to compile assets"
echo "3. Permission denied - Check storage and cache permissions"
echo "4. No admin user - Create user with: php artisan make:filament-user"
echo ""
echo "Access your Filament admin at: https://my17.web.id/admin"
