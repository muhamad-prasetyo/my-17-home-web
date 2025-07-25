<?php
/**
 * GitHub Webhook Handler untuk Auto Deploy Laravel MY17
 * Letakkan file ini di server Anda (contoh: /var/www/html/webhook.php)
 */

// Konfigurasi
$secret = 'MY17_AUTO_DEPLOY_SECRET_2025'; // Secret key untuk keamanan webhook
$project_path = '/home/my17-absensi/htdocs/my17.web.id'; // Path CloudPanel project

// Validasi request dari GitHub
function validate_github_webhook($payload, $signature, $secret) {
    $calculated_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($signature, $calculated_signature);
}

// Log function
function log_message($message) {
    $log_file = '/var/log/laravel-webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Get request data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Validate signature
if (!validate_github_webhook($payload, $signature, $secret)) {
    log_message('Invalid signature');
    http_response_code(401);
    exit('Unauthorized');
}

// Parse payload
$data = json_decode($payload, true);

// Check if this is a push to main branch
if (isset($data['ref']) && $data['ref'] === 'refs/heads/main') {
    log_message('Push to main branch detected, starting deployment...');
    
    // Execute deploy commands for Laravel Filament
    $commands = [
        "cd $project_path",
        "git pull origin main",
        "composer install --no-dev --optimize-autoloader",
        "php artisan migrate --force",
        "php artisan config:cache",
        "php artisan route:cache", 
        "php artisan view:cache",
        "php artisan storage:link",
        "php artisan filament:cache-components", // Cache Filament components
        "php artisan optimize", // Laravel optimization
        "npm ci --production", // Install production npm dependencies
        "npm run build", // Build assets with Vite
        "chown -R my17-absensi:my17-absensi .",
        "chmod -R 775 storage bootstrap/cache", // Laravel needs 775 for storage
        "chmod -R 755 public" // Public directory permissions
    ];
    
    $command = implode(' && ', $commands);
    $output = shell_exec($command . ' 2>&1');
    
    log_message('Deploy completed. Output: ' . $output);
    
    echo "Deployment successful!";
} else {
    log_message('Push to non-main branch, ignoring...');
    echo "Not a main branch push, ignoring";
}

http_response_code(200);
?>
