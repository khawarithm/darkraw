<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
if (API_ENABLED && $api_key !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Route actions
switch ($action) {
    case 'lock':
        handle_lock($input);
        break;
        
    case 'unlock':
        handle_unlock($input);
        break;
        
    case 'status':
        handle_status($input);
        break;
        
    case 'extend':
        handle_extend($input);
        break;
        
    case 'reset':
        handle_reset($input);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

// Handle lock action
function handle_lock($data) {
    $device_id = $data['device_id'] ?? generate_device_id();
    $password = $data['password'] ?? DEFAULT_PASSWORD;
    $duration = $data['duration'] ?? LOCK_DURATION;
    
    $_SESSION['lock_data'] = [
        'device_id' => $device_id,
        'locked_at' => time(),
        'password' => $password,
        'duration' => $duration,
        'locked' => true,
        'failed_attempts' => 0
    ];
    
    log_event('device_locked', [
        'device_id' => $device_id,
        'ip' => get_client_ip()
    ]);
    
    echo json_encode([
        'status' => 'locked',
        'device_id' => $device_id,
        'duration' => $duration,
        'unlock_code' => generate_unlock_code($device_id)
    ]);
}

// Handle unlock action
function handle_unlock($data) {
    $device_id = $data['device_id'] ?? '';
    $password = $data['password'] ?? '';
    
    if (!isset($_SESSION['lock_data'])) {
        http_response_code(404);
        echo json_encode(['error' => 'No active lock found']);
        return;
    }
    
    if ($_SESSION['lock_data']['password'] === $password) {
        $lock_duration = time() - $_SESSION['lock_data']['locked_at'];
        
        log_event('device_unlocked', [
            'device_id' => $device_id,
            'duration' => $lock_duration
        ]);
        
        unset($_SESSION['lock_data']);
        
        echo json_encode([
            'status' => 'unlocked',
            'lock_duration' => $lock_duration
        ]);
    } else {
        $_SESSION['lock_data']['failed_attempts']++;
        
        log_event('failed_unlock_attempt', [
            'device_id' => $device_id,
            'attempts' => $_SESSION['lock_data']['failed_attempts']
        ]);
        
        http_response_code(403);
        echo json_encode([
            'error' => 'Invalid password',
            'attempts' => $_SESSION['lock_data']['failed_attempts']
        ]);
    }
}

// Handle status check
function handle_status($data) {
    if (!isset($_SESSION['lock_data'])) {
        echo json_encode(['locked' => false]);
        return;
    }
    
    $lock_data = $_SESSION['lock_data'];
    $current_time = time();
    $locked_at = $lock_data['locked_at'];
    $duration = $lock_data['duration'];
    $remaining = max(0, $locked_at + $duration - $current_time);
    
    echo json_encode([
        'locked' => $lock_data['locked'],
        'locked_at' => date('Y-m-d H:i:s', $locked_at),
        'duration' => $duration,
        'remaining' => $remaining,
        'failed_attempts' => $lock_data['failed_attempts'],
        'device_id' => $lock_data['device_id']
    ]);
}

// Generate device ID
function generate_device_id() {
    return md5(get_client_ip() . $_SERVER['HTTP_USER_AGENT'] . time() . random_bytes(16));
}

// Generate unlock code
function generate_unlock_code($device_id) {
    return strtoupper(substr(md5($device_id . time() . API_KEY), 0, 8));
}

// Log helper (from config.php)
function get_client_ip() {
    // Same function as in config.php
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function log_event($event, $data = []) {
    if (!ENABLE_LOGGING) return;
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => get_client_ip(),
        'event' => $event,
        'data' => $data
    ];
    
    $log_line = json_encode($log_entry) . PHP_EOL;
    file_put_contents(LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);
}
?>