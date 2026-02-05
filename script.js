// Global variables
let lockActive = false;
let timerInterval;
let secondsRemaining = 300; // 5 minutes

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    // Start fake update progress
    startFakeUpdate();
    
    // Block all user interactions
    blockAllInteractions();
    
    // Request fullscreen
    requestFullscreen();
    
    // Start vibration if supported
    startVibrationPattern();
    
    // Play alarm sound
    playAlertSound();
    
    // Prevent context menu
    document.addEventListener('contextmenu', e => {
        e.preventDefault();
        return false;
    });
    
    // Block keyboard shortcuts
    document.addEventListener('keydown', e => {
        if (e.ctrlKey || e.altKey || e.metaKey || e.key === 'F12') {
            e.preventDefault();
            return false;
        }
    });
    
    // Block touch gestures
    document.addEventListener('touchmove', e => {
        if (lockActive) {
            e.preventDefault();
            return false;
        }
    }, { passive: false });
});

function startFakeUpdate() {
    let progress = 0;
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const messages = [
        "Downloading security components...",
        "Verifying system integrity...",
        "Applying malware patches...",
        "Encrypting system files...",
        "Finalizing installation..."
    ];
    
    const updateInterval = setInterval(() => {
        progress += Math.random() * 3;
        if (progress > 100) progress = 100;
        
        progressFill.style.width = progress + '%';
        progressText.textContent = messages[Math.min(Math.floor(progress/20), 4)] + 
                                  " " + Math.floor(progress) + "% complete";
        
        if (progress >= 100) {
            clearInterval(updateInterval);
            setTimeout(() => {
                // Switch to lock screen
                document.getElementById('lockScreen').style.display = 'flex';
                lockActive = true;
                startLockTimer();
                
                // Hide progress section
                document.querySelector('.progress-section').style.opacity = '0.3';
                document.querySelector('.details').style.opacity = '0.3';
            }, 1500);
        }
    }, 300);
}

function blockAllInteractions() {
    const blocker = document.getElementById('touchBlocker');
    
    // Block all events
    const events = ['click', 'mousedown', 'mouseup', 'mousemove', 
                   'touchstart', 'touchend', 'touchmove', 
                   'keydown', 'keypress', 'keyup'];
    
    events.forEach(eventType => {
        document.addEventListener(eventType, function(e) {
            if (lockActive && !e.target.closest('.lock-content')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Add visual feedback for blocked input
                showBlockedIndicator(e);
                return false;
            }
        }, true);
    });
}

function showBlockedIndicator(event) {
    const indicator = document.createElement('div');
    indicator.style.cssText = `
        position: fixed;
        width: 50px;
        height: 50px;
        background: rgba(247, 37, 133, 0.7);
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transform: translate(-50%, -50%);
        animation: ripple 0.6s linear;
    `;
    
    const x = event.touches ? event.touches[0].clientX : event.clientX;
    const y = event.touches ? event.touches[0].clientY : event.clientY;
    
    indicator.style.left = x + 'px';
    indicator.style.top = y + 'px';
    
    document.body.appendChild(indicator);
    
    setTimeout(() => {
        indicator.remove();
    }, 600);
}

function requestFullscreen() {
    const elem = document.documentElement;
    
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.webkitRequestFullscreen) { /* Safari */
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) { /* IE11 */
        elem.msRequestFullscreen();
    }
}

function startVibrationPattern() {
    if (navigator.vibrate) {
        setInterval(() => {
            navigator.vibrate([200, 100, 200, 100, 200]);
        }, 5000);
    }
}

function playAlertSound() {
    // Create audio context for alarm sound
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.1;
        
        oscillator.start();
        
        // Pulsing effect
        setInterval(() => {
            gainNode.gain.value = gainNode.gain.value === 0.1 ? 0.05 : 0.1;
        }, 1000);
    } catch (e) {
        console.log("Audio not supported");
    }
}

function startLockTimer() {
    const timerElement = document.getElementById('lockTimer');
    
    timerInterval = setInterval(() => {
        secondsRemaining--;
        
        const minutes = Math.floor(secondsRemaining / 60);
        const seconds = secondsRemaining % 60;
        
        timerElement.textContent = 
            minutes.toString().padStart(2, '0') + ':' + 
            seconds.toString().padStart(2, '0');
        
        if (secondsRemaining <= 0) {
            clearInterval(timerInterval);
            // Auto-lock actions
            intensifyLock();
        }
    }, 1000);
}

function intensifyLock() {
    // Make lock stronger when timer expires
    document.querySelector('.lock-content').style.borderColor = '#ff0000';
    document.querySelector('.lock-content').style.boxShadow = '0 0 80px rgba(255, 0, 0, 0.8)';
    
    // Increase vibration
    if (navigator.vibrate) {
        navigator.vibrate([500, 100, 500, 100, 500, 100, 500]);
    }
    
    // Show warning
    const timerElement = document.getElementById('lockTimer');
    timerElement.innerHTML = '<span style="color:#ff0000">LOCKED PERMANENTLY</span>';
}

function checkPassword() {
    const passwordInput = document.getElementById('passwordInput');
    const password = passwordInput.value;
    
    // Default password: 0000
    if (password === '0000') {
        // Unlock sequence
        unlockDevice();
    } else {
        // Wrong password effect
        passwordInput.style.borderColor = '#ff0000';
        passwordInput.value = '';
        passwordInput.placeholder = 'WRONG PASSWORD! Try again...';
        
        // Shake animation
        passwordInput.style.animation = 'shake 0.5s';
        setTimeout(() => {
            passwordInput.style.animation = '';
        }, 500);
        
        // Vibration feedback
        if (navigator.vibrate) {
            navigator.vibrate([300]);
        }
    }
}

function unlockDevice() {
    // Stop all locks
    clearInterval(timerInterval);
    lockActive = false;
    
    // Hide lock screen
    document.getElementById('lockScreen').style.opacity = '0';
    
    // Show unlock message
    const lockContent = document.querySelector('.lock-content');
    lockContent.innerHTML = `
        <i class="fas fa-unlock" style="color:#00ff00; font-size:4rem;"></i>
        <h2 style="color:#00ff00">DEVICE UNLOCKED</h2>
        <p>Security protocol terminated</p>
        <div style="margin-top:30px;">
            <button onclick="closeWindow()" style="
                padding:15px 30px;
                background:#00ff00;
                color:#000;
                border:none;
                border-radius:10px;
                font-size:1.2rem;
                cursor:pointer;
            ">
                <i class="fas fa-times"></i> CLOSE WINDOW
            </button>
        </div>
    `;
    
    // Stop vibration and sound
    if (navigator.vibrate) {
        navigator.vibrate(0);
    }
}

function closeWindow() {
    // Exit fullscreen
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) { /* Safari */
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) { /* IE11 */
        document.msExitFullscreen();
    }
    
    // Can't close window from browser for security,
    // but we can redirect
    window.location.href = 'about:blank';
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @keyframes ripple {
        0% {
            width: 0;
            height: 0;
            opacity: 1;
        }
        100% {
            width: 100px;
            height: 100px;
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Prevent window close
window.onbeforeunload = function(e) {
    if (lockActive) {
        e.preventDefault();
        e.returnValue = '⚠️ System is locked. Closing may cause instability.';
        return '⚠️ System is locked. Closing may cause instability.';
    }
};