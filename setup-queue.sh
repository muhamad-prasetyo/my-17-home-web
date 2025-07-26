#!/bin/bash

# Laravel Queue Setup Script untuk aaPanel
# Jalankan script ini di server untuk setup queue worker

echo "🚀 Setting up Laravel Queue Worker..."

# 1. Setup database tables
echo "📁 Creating queue database tables..."
php artisan queue:table
php artisan queue:failed-table
php artisan migrate --force

# 2. Install supervisor (jika belum ada)
echo "📦 Installing supervisor..."
if ! command -v supervisorctl &> /dev/null; then
    if command -v yum &> /dev/null; then
        # CentOS/RHEL
        sudo yum install -y supervisor
    elif command -v apt-get &> /dev/null; then
        # Ubuntu/Debian
        sudo apt-get update
        # Install supervisor dengan auto-confirm untuk keep existing config
        echo "supervisor supervisor/keep_existing_config boolean true" | sudo debconf-set-selections
        sudo DEBIAN_FRONTEND=noninteractive apt-get install -y supervisor
    fi
fi

# 3. Start supervisor service
echo "🔄 Starting supervisor service..."
sudo systemctl enable supervisor
sudo systemctl start supervisor

# 4. Copy supervisor config
echo "⚙️  Setting up supervisor config..."
PROJECT_PATH=$(pwd)
SUPERVISOR_CONF="/etc/supervisor/conf.d/laravel-queue.conf"

sudo tee $SUPERVISOR_CONF > /dev/null <<EOF
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_PATH/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=2
redirect_stderr=true
stdout_logfile=$PROJECT_PATH/storage/logs/laravel-queue.log
stopwaitsecs=3600
EOF

# 5. Reload and start supervisor
echo "🔄 Reloading supervisor..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue:*

# 6. Check status
echo "✅ Checking queue worker status..."
sudo supervisorctl status

echo "🎉 Laravel Queue setup completed!"
echo "📊 Monitor queue with: php artisan queue:monitor"
echo "📋 Check logs at: $PROJECT_PATH/storage/logs/laravel-queue.log"
