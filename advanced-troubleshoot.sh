#!/bin/bash

# Advanced Troubleshoot untuk Laravel Filament 500 Error

echo "=== ADVANCED LARAVEL FILAMENT TROUBLESHOOT ==="

PROJECT_PATH="/home/my17-absensi/htdocs/my17.web.id"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_status "Starting advanced troubleshoot..."

# Check if project directory exists
if [ ! -d "$PROJECT_PATH" ]; then
    print_error "Project directory not found: $PROJECT_PATH"
    exit 1
fi

cd $PROJECT_PATH

echo ""
echo "=== 1. ENVIRONMENT CHECK ==="
print_status "Checking PHP version..."
php -v | head -1

print_status "Checking Laravel version..."
php artisan --version

print_status "Checking current .env config..."
if [ -f .env ]; then
    echo "APP_ENV: $(grep APP_ENV .env)"
    echo "APP_DEBUG: $(grep APP_DEBUG .env)"
    echo "APP_KEY: $(grep APP_KEY .env | cut -c1-20)..."
    echo "DB_CONNECTION: $(grep DB_CONNECTION .env)"
    echo "SESSION_DRIVER: $(grep SESSION_DRIVER .env)"
    echo "CACHE_STORE: $(grep CACHE_STORE .env)"
else
    print_error ".env file not found!"
fi

echo ""
echo "=== 2. DATABASE CONNECTION TEST ==="
print_status "Testing database connection..."
php artisan db:show || print_error "Database connection failed"

echo ""
echo "=== 3. FILE PERMISSIONS CHECK ==="
print_status "Checking critical directory permissions..."
ls -la storage/
ls -la bootstrap/cache/

echo ""
echo "=== 4. LARAVEL ERRORS ==="
print_status "Checking Laravel log files..."
if [ -f storage/logs/laravel.log ]; then
    echo "Last 10 lines of Laravel log:"
    tail -10 storage/logs/laravel.log
else
    print_warning "No Laravel log file found"
fi

echo ""
echo "=== 5. FILAMENT SPECIFIC CHECKS ==="
print_status "Checking Filament installation..."
php artisan filament:version 2>/dev/null || print_warning "Filament not installed or not accessible"

print_status "Checking if admin user exists..."
php artisan tinker --execute="echo App\Models\User::where('email', 'admin@admin.com')->exists() ? 'Admin exists' : 'No admin user'"

echo ""
echo "=== 6. COMPOSER DEPENDENCIES ==="
print_status "Checking critical packages..."
composer show | grep -E "(filament|livewire)" || print_warning "Filament packages not found"

echo ""
echo "=== 7. STORAGE LINKS ==="
print_status "Checking storage link..."
ls -la public/storage || print_warning "Storage link not found"

echo ""
echo "=== 8. WEB SERVER LOGS ==="
if [ -f /var/log/nginx/error.log ]; then
    print_status "Nginx error log (last 5 lines):"
    tail -5 /var/log/nginx/error.log
elif [ -f /var/log/apache2/error.log ]; then
    print_status "Apache error log (last 5 lines):"
    tail -5 /var/log/apache2/error.log
else
    print_warning "No web server logs found"
fi

echo ""
echo "=== 9. MEMORY AND RESOURCES ==="
print_status "Checking system resources..."
df -h . | head -2
free -h | head -2

echo ""
echo "=== TROUBLESHOOT COMPLETE ==="
echo ""
echo "Based on the output above, try these solutions:"
echo "1. If database connection failed: Check DB credentials"
echo "2. If permission errors: Run chmod -R 775 storage bootstrap/cache"
echo "3. If Filament not found: Run composer install"
echo "4. If no admin user: Run php artisan make:filament-user"
echo "5. If memory issues: Increase PHP memory_limit"
echo ""
echo "For immediate fix, run: ./quick-fix-500.sh"
