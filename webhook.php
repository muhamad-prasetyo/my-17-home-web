<?php
/**
 * GitHub Webhook Handler untuk Auto Deploy Laravel MY17
 * Letakkan file ini di server Anda (contoh: /var/www/html/webhook.php)
 */

// Konfigurasi
$secret = 'MY17_AUTO_DEPLOY_SECRET_2025'; // Secret key untuk keamanan webhook
$project_path = '/var/www/html/laravel-my17'; // Path ke project Laravel

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
    
    // Execute deploy commands
    $commands = [
        "cd $project_path",
        "git pull origin main",
        "composer install --no-dev --optimize-autoloader",
        "php artisan migrate --force",
        "php artisan config:cache",
        "php artisan route:cache", 
        "php artisan view:cache",
        "php artisan storage:link",
        "sudo chown -R www-data:www-data storage bootstrap/cache",
        "sudo chmod -R 775 storage bootstrap/cache"
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
