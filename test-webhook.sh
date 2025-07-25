#!/bin/bash

# Script untuk test dan trigger webhook secara manual
# Jalankan ini setelah upload webhook.php ke server

echo "Testing webhook endpoint..."

# Test apakah webhook.php dapat diakses
curl -I https://my17.web.id/webhook.php

echo ""
echo "If you see 200 OK above, webhook file is accessible."
echo ""

# Manual deployment commands (jalankan di server via SSH)
echo "Manual deployment commands:"
echo "cd /home/my17-absensi/htdocs/my17.web.id"
echo "git pull origin main"
echo "composer install --no-dev --optimize-autoloader"
echo "php artisan config:cache"
echo "php artisan route:cache"
echo "php artisan view:cache"
echo "chmod -R 775 storage bootstrap/cache"
echo "chown -R my17-absensi:my17-absensi ."
