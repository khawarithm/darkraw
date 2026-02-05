from flask import Flask, render_template, request, jsonify
from flask_cors import CORS
import os
import json
import time

app = Flask(__name__)
CORS(app)

# Store locked devices
locked_devices = {}

@app.route('/')
def index():
    """Serve the lock screen"""
    return render_template('index.html')

@app.route('/api/lock', methods=['POST'])
def lock_device():
    """API to lock a device"""
    data = request.json
    device_id = data.get('device_id', 'unknown')
    
    locked_devices[device_id] = {
        'locked_at': time.time(),
        'locked_by': data.get('user', 'anonymous'),
        'password': data.get('password', '0000')
    }
    
    return jsonify({
        'status': 'locked',
        'device_id': device_id,
        'unlock_code': generate_unlock_code(device_id)
    })

@app.route('/api/unlock', methods=['POST'])
def unlock_device():
    """API to unlock a device"""
    data = request.json
    device_id = data.get('device_id')
    password = data.get('password')
    
    if device_id in locked_devices:
        if locked_devices[device_id]['password'] == password:
            del locked_devices[device_id]
            return jsonify({'status': 'unlocked'})
    
    return jsonify({'status': 'failed', 'error': 'Invalid password or device ID'}), 403

@app.route('/api/status')
def device_status():
    """Check if device is locked"""
    device_id = request.args.get('device_id', 'unknown')
    
    if device_id in locked_devices:
        locked_time = locked_devices[device_id]['locked_at']
        return jsonify({
            'locked': True,
            'since': time.ctime(locked_time),
            'duration': int(time.time() - locked_time)
        })
    
    return jsonify({'locked': False})

def generate_unlock_code(device_id):
    """Generate unique unlock code"""
    import hashlib
    timestamp = str(time.time())
    return hashlib.md5((device_id + timestamp).encode()).hexdigest()[:8]

if __name__ == '__main__':
    # Create templates directory if not exists
    os.makedirs('templates', exist_ok=True)
    
    # Copy HTML file to templates
    import shutil
    shutil.copy2('../web_content/index.html', 'templates/')
    
    # Run server
    print("""
    ╔══════════════════════════════════════════╗
    ║   DARK AI LOCK SERVER v5.0              ║
    ║   Running on: http://localhost:5000     ║
    ║   API Endpoints:                        ║
    ║     - GET  /                            ║
    ║     - POST /api/lock                    ║
    ║     - POST /api/unlock                  ║
    ║     - GET  /api/status                  ║
    ╚══════════════════════════════════════════╝
    """)
    
    app.run(host='0.0.0.0', port=5000, debug=True)