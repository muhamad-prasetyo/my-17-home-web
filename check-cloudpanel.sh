#!/bin/bash

# Check CloudPanel Directory Setup

echo "=== CLOUDPANEL DIRECTORY CHECK ==="

# Check if we're in the right directory
pwd

echo ""
echo "=== DIRECTORY STRUCTURE ==="
ls -la /home/my17-absensi/htdocs/

echo ""
echo "=== CHECKING my17.web.id FOLDER ==="
if [ -d "/home/my17-absensi/htdocs/my17.web.id" ]; then
    echo "✅ my17.web.id folder exists"
    ls -la /home/my17-absensi/htdocs/my17.web.id/
    
    echo ""
    echo "=== CHECKING PUBLIC FOLDER ==="
    if [ -d "/home/my17-absensi/htdocs/my17.web.id/public" ]; then
        echo "✅ public folder exists"
        ls -la /home/my17-absensi/htdocs/my17.web.id/public/
        
        echo ""
        echo "=== CHECKING index.php ==="
        if [ -f "/home/my17-absensi/htdocs/my17.web.id/public/index.php" ]; then
            echo "✅ index.php exists"
            head -5 /home/my17-absensi/htdocs/my17.web.id/public/index.php
        else
            echo "❌ index.php NOT FOUND!"
        fi
        
        echo ""
        echo "=== CHECKING .htaccess ==="
        if [ -f "/home/my17-absensi/htdocs/my17.web.id/public/.htaccess" ]; then
            echo "✅ .htaccess exists"
            head -10 /home/my17-absensi/htdocs/my17.web.id/public/.htaccess
        else
            echo "❌ .htaccess NOT FOUND!"
        fi
    else
        echo "❌ public folder NOT FOUND!"
    fi
else
    echo "❌ my17.web.id folder NOT FOUND!"
fi

echo ""
echo "=== CHECKING PERMISSIONS ==="
ls -la /home/my17-absensi/htdocs/ | grep my17

echo ""
echo "=== CHECKING NGINX/APACHE CONFIG ==="
# Check if nginx or apache is running
ps aux | grep -E "(nginx|apache)" | grep -v grep

echo ""
echo "=== CHECKING WEB SERVER LOGS ==="
if [ -f "/var/log/nginx/error.log" ]; then
    echo "Nginx error log (last 5 lines):"
    tail -5 /var/log/nginx/error.log
fi

if [ -f "/var/log/apache2/error.log" ]; then
    echo "Apache error log (last 5 lines):"
    tail -5 /var/log/apache2/error.log
fi

echo ""
echo "=== CLOUDPANEL DOCUMENT ROOT CHECK ==="
echo "Expected document root should point to: /home/my17-absensi/htdocs/my17.web.id/public"
echo "Current document root from web server config:"

# Try to find web server config
find /etc -name "*my17.web.id*" 2>/dev/null || echo "No specific config found"
