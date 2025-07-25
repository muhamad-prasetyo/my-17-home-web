#!/bin/bash

# Script untuk memperbaiki permission Laravel di CloudPanel
# Jalankan ini di server melalui SSH atau Terminal CloudPanel

# Set ownership
chown -R my17-absensi:my17-absensi /home/my17-absensi/htdocs/my17.web.id

# Set directory permissions
find /home/my17-absensi/htdocs/my17.web.id -type d -exec chmod 755 {} \;

# Set file permissions
find /home/my17-absensi/htdocs/my17.web.id -type f -exec chmod 644 {} \;

# Set executable permissions for artisan
chmod +x /home/my17-absensi/htdocs/my17.web.id/artisan

# Set writable permissions for storage and cache
chmod -R 775 /home/my17-absensi/htdocs/my17.web.id/storage
chmod -R 775 /home/my17-absensi/htdocs/my17.web.id/bootstrap/cache

# Set permissions for public directory
chmod -R 755 /home/my17-absensi/htdocs/my17.web.id/public

echo "Permissions fixed!"
