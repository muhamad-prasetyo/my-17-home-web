#!/bin/bash

# Laravel Scheduler Setup Script
# Script ini akan setup cron job untuk Laravel scheduler

echo "=== Laravel Scheduler Setup ==="
echo ""

# Get current directory
PROJECT_PATH=$(pwd)
echo "Project path: $PROJECT_PATH"

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "Error: artisan file not found. Please run this script from Laravel project root."
    exit 1
fi

# Create cron job entry
CRON_JOB="* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1"

echo "Setting up cron job..."
echo "Cron job: $CRON_JOB"
echo ""

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "Cron job already exists. Removing old entry..."
    crontab -l 2>/dev/null | grep -v "schedule:run" | crontab -
fi

# Add new cron job
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -

echo "Cron job has been added successfully!"
echo ""

# Verify cron job
echo "Current cron jobs:"
crontab -l

echo ""
echo "=== Setup Complete ==="
echo "Laravel scheduler will now run every minute."
echo "Your attendance:mark-alfa command will run daily at 02:00 AM as configured in app/Console/Kernel.php"
echo ""
echo "To test manually, run: php artisan attendance:mark-alfa" 