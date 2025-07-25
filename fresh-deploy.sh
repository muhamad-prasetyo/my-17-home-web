#!/bin/bash

# Script untuk Fresh Deployment Laravel MY17 di CloudPanel
# Jalankan script ini via SSH Terminal di CloudPanel

echo "=== FRESH DEPLOYMENT LARAVEL MY17 ==="
echo ""

# 1. Backup database (optional tapi recommended)
echo "1. Backup database (optional)..."
# mysqldump -u absensimy17 -p my17absensi > /home/my17-absensi/backup-$(date +%Y%m%d-%H%M%S).sql

# 2. Hapus project lama
echo "2. Removing old project..."
cd /home/my17-absensi/htdocs/
rm -rf my17.web.id

# 3. Clone fresh dari GitHub
echo "3. Cloning fresh from GitHub..."
git clone https://github.com/muhamad-prasetyo/laravel-my17.git my17.web.id
cd my17.web.id

# 4. Install dependencies
echo "4. Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# 5. Copy environment file
echo "5. Setting up environment..."
cp .env.example .env

echo "6. MANUAL STEP: Edit .env file with your database credentials"
echo "   Use: nano .env"
echo "   Set your database credentials:"
echo "   DB_DATABASE=my17absensi"
echo "   DB_USERNAME=absensimy17"
echo "   DB_PASSWORD=your_password"
echo ""
read -p "Press Enter after you've edited .env file..."

# 7. Generate application key
echo "7. Generating application key..."
php artisan key:generate

# 8. Run migrations
echo "8. Running database migrations..."
php artisan migrate --force

# 9. Set permissions
echo "9. Setting correct permissions..."
chown -R my17-absensi:my17-absensi .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod +x artisan

# 10. Create storage link
echo "10. Creating storage link..."
php artisan storage:link

# 11. Cache optimization for Laravel Filament
echo "11. Optimizing cache for Laravel Filament..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# 12. Build assets (required for Filament)
echo "12. Building assets with Vite..."
if command -v npm &> /dev/null; then
    npm ci --production
    npm run build
else
    echo "npm not found, please install Node.js and run:"
    echo "npm ci --production && npm run build"
fi

echo ""
echo "=== DEPLOYMENT COMPLETE ==="
echo "Your Laravel application should now be accessible at https://my17.web.id"
echo ""
echo "Next steps:"
echo "1. Test the application in browser"
echo "2. Upload webhook.php to root directory for auto-deployment"
echo "3. Configure GitHub webhook URL: https://my17.web.id/webhook.php"
