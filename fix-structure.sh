#!/bin/bash

# Fix Double Nested Folder Structure

echo "=== FIXING DOUBLE NESTED STRUCTURE ==="

cd /home/my17-absensi/htdocs/my17.web.id

echo "Current structure:"
ls -la

echo ""
echo "Moving Laravel files from nested folder to root..."

# Move everything from nested my17.web.id to current directory
mv my17.web.id/* . 2>/dev/null
mv my17.web.id/.[^.]* . 2>/dev/null

# Remove empty nested folder
rmdir my17.web.id 2>/dev/null

echo ""
echo "New structure:"
ls -la

echo ""
echo "Checking if Laravel files are now in correct location:"
if [ -f "artisan" ]; then
    echo "✅ artisan found - Laravel in correct location"
else
    echo "❌ artisan not found - something went wrong"
fi

if [ -f "public/index.php" ]; then
    echo "✅ public/index.php found - Document root will work"
else
    echo "❌ public/index.php not found - document root issue"
fi

echo ""
echo "Setting correct permissions..."
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
chown -R my17-absensi:my17-absensi .

echo ""
echo "=== STRUCTURE FIX COMPLETE ==="
echo "Laravel files should now be accessible at https://my17.web.id/"
