#!/bin/bash

# Fix 403 Forbidden Error for Laravel on CloudPanel

echo "=== FIXING 403 FORBIDDEN ERROR ==="

PROJECT_PATH="/home/my17-absensi/htdocs/my17.web.id"
cd $PROJECT_PATH

echo "1. Setting correct permissions on directories..."
find $PROJECT_PATH -type d -exec chmod 755 {} \;
echo "✓ Directory permissions set to 755"

echo "2. Setting correct permissions on files..."
find $PROJECT_PATH -type f -exec chmod 644 {} \;
echo "✓ File permissions set to 644"

echo "3. Making artisan executable..."
chmod 755 $PROJECT_PATH/artisan
echo "✓ Made artisan executable"

echo "4. Setting correct ownership..."
chown -R my17-absensi:my17-absensi $PROJECT_PATH
echo "✓ Set ownership to my17-absensi user"

echo "5. Setting special permissions for storage and bootstrap/cache..."
chmod -R 775 $PROJECT_PATH/storage
chmod -R 775 $PROJECT_PATH/bootstrap/cache
echo "✓ Set special permissions for writable directories"

echo ""
echo "6. Adding custom Nginx directive for /admin routes..."
echo "NOTE: You need to manually add this in CloudPanel > Websites > my17.web.id > Settings > Nginx Directives"
echo ""
echo "# Complete Nginx configuration for Laravel Filament application"
echo "cat << 'EOT' > /tmp/my17_nginx_config.txt
server {
  listen 80;
  listen [::]:80;
  listen 443 quic;
  listen 443 ssl;
  listen [::]:443 quic;
  listen [::]:443 ssl;
  http2 on;
  http3 off;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name www.my17.web.id;
  return 301 https://my17.web.id\$request_uri;
}

server {
  listen 80;
  listen [::]:80;
  listen 443 quic;
  listen 443 ssl;
  listen [::]:443 quic;
  listen [::]:443 ssl;
  http2 on;
  http3 off;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name my17.web.id www1.my17.web.id;
  {{root}}

  {{nginx_access_log}}
  {{nginx_error_log}}

  if (\$scheme != \"https\") {
    rewrite ^ https://\$host\$request_uri permanent;
  }

  location ~ /.well-known {
    auth_basic off;
    allow all;
  }

  {{settings}}

  include /etc/nginx/global_settings;
  
  # Admin panel processing - improved with direct PHP processing
  location ^~ /admin {
    # Try files or route to Laravel front controller
    try_files \$uri \$uri/ /index.php?\$query_string;
    
    # Add CORS headers for admin section
    add_header Access-Control-Allow-Origin '*';
    add_header Access-Control-Allow-Methods 'GET, POST, PUT, DELETE, OPTIONS';
    add_header Access-Control-Allow-Headers 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization,X-CSRF-TOKEN';
    
    # Handle OPTIONS preflight requests for CSRF
    if (\$request_method = OPTIONS) {
      add_header Content-Type 'text/plain charset=UTF-8';
      add_header Content-Length 0;
      return 204;
    }
  }

  # Penanganan PHP untuk semua file
  location ~ \\.php$ {
    include fastcgi_params;
    fastcgi_intercept_errors on;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    try_files \$uri =404;
    fastcgi_read_timeout 3600;
    fastcgi_send_timeout 3600;
    fastcgi_param HTTPS \"on\";
    fastcgi_param SERVER_PORT 443;
    fastcgi_pass 127.0.0.1:{{php_fpm_port}};
    fastcgi_param PHP_VALUE \"{{php_settings}}\";
  }

  # Lokasi umum untuk URL lainnya
  location / {
    try_files \$uri \$uri/ /index.php?\$query_string;
  }

  location ~* ^.+\\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    add_header Access-Control-Allow-Origin \"*\";
    add_header alt-svc 'h3=\":443\"; ma=86400';
    expires max;
    access_log off;
  }

  if (-f \$request_filename) {
    break;
  }
}
EOT"
echo ""
echo "echo 'The complete Nginx configuration has been saved to /tmp/my17_nginx_config.txt'"
echo "echo 'Copy and paste this into CloudPanel > Websites > my17.web.id > Settings > Custom vHost'"

echo ""
echo "7. Clear Laravel caches..."
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✓ Cleared all Laravel caches"

echo ""
echo "8. Checking and fixing CSRF token settings for admin routes..."
if [ -f "app/Http/Middleware/VerifyCsrfToken.php" ]; then
    # Check if admin/login is already in exceptions
    if ! grep -q "admin/login" "app/Http/Middleware/VerifyCsrfToken.php"; then
        echo "Adding CSRF exception for admin/login route..."
        # Backup the file
        cp app/Http/Middleware/VerifyCsrfToken.php app/Http/Middleware/VerifyCsrfToken.php.bak
        
        # Add admin/login to exceptions
        sed -i '/protected \$except = \[/a \ \ \ \ \ \ \ \ '\''admin/login'\'', ' app/Http/Middleware/VerifyCsrfToken.php
        
        echo "✓ Added admin/login to CSRF exceptions"
    else
        echo "✓ admin/login already in CSRF exceptions"
    fi
else
    echo "⚠️ VerifyCsrfToken.php not found"
fi

echo ""
echo "9. Creating a test file to verify PHP execution..."
echo "<?php phpinfo(); ?>" > public/test-php.php
chmod 644 public/test-php.php
echo "✓ Created PHP test file at public/test-php.php"
echo "  You can check this by visiting https://my17.web.id/test-php.php"

echo ""
echo "Done! After making these changes:"
echo "1. Go to CloudPanel > Websites > my17.web.id > Settings > Custom vHost"
echo "2. Replace the entire configuration with the content from /tmp/my17_nginx_config.txt"
echo "3. Save the changes and restart services:"
echo "   sudo systemctl restart php-fpm"
echo "   sudo systemctl restart nginx"
echo ""
echo "After applying these changes, try accessing your admin panel at:"
echo "https://my17.web.id/admin"
