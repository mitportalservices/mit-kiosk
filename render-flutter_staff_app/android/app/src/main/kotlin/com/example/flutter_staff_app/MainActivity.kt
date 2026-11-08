package com.example.flutter_staff_app

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    companion object {
        private const val CHANNEL = "com.mitkiosk.staffportal/notifications"
        private const val REQ_POST_NOTIFICATIONS = 1001
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        NotificationPollingService.ensureChannels(this)
        requestNotificationPermissionIfNeeded()
        NotificationPollingService.startServiceIfLoggedIn(this)
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, CHANNEL)
            .setMethodCallHandler { call, result ->
                when (call.method) {
                    "requestPermission" -> {
                        requestNotificationPermissionIfNeeded()
                        result.success(true)
                    }
                    "startBackgroundService" -> {
                        val token = call.argument<String>("token") ?: ""
                        val baseUrl = call.argument<String>("baseUrl") ?: "https://laravel-web-app-kr4g.onrender.com"
                        val role = call.argument<String>("role") ?: "faculty"

                        val prefs = getSharedPreferences(NotificationPollingService.PREFS_NAME, Context.MODE_PRIVATE)
                        val oldToken = prefs.getString(NotificationPollingService.KEY_TOKEN, null)
                        val editor = prefs.edit()
                            .putString(NotificationPollingService.KEY_TOKEN, token)
                            .putString(NotificationPollingService.KEY_BASE_URL, baseUrl)
                            .putString(NotificationPollingService.KEY_ROLE, role)

                        if (oldToken != token) {
                            editor.putBoolean(NotificationPollingService.KEY_INITIALIZED, false)
                                .remove(NotificationPollingService.KEY_SEEN_APPTS)
                                .remove(NotificationPollingService.KEY_SEEN_CONS)
                        }
                        editor.apply()

                        requestNotificationPermissionIfNeeded()
                        NotificationPollingService.startServiceIfLoggedIn(this)
                        result.success(true)
                    }
                    "stopBackgroundService" -> {
                        val prefs = getSharedPreferences(NotificationPollingService.PREFS_NAME, Context.MODE_PRIVATE)
                        prefs.edit().clear().apply()
                        NotificationPollingService.stopService(this)
                        result.success(true)
                    }
                    "showHeadsUpNotification" -> {
                        val id = call.argument<Int>("id") ?: (System.currentTimeMillis() % 100000).toInt()
                        val title = call.argument<String>("title") ?: "MIT Staff Portal"
                        val body = call.argument<String>("body") ?: ""
                        NotificationPollingService.showHeadsUpAlert(this, id, title, body)
                        result.success(true)
                    }
                    else -> result.notImplemented()
                }
            }
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            val granted = ContextCompat.checkSelfPermission(
                this,
                Manifest.permission.POST_NOTIFICATIONS
            ) == PackageManager.PERMISSION_GRANTED
            if (!granted) {
                ActivityCompat.requestPermissions(
                    this,
                    arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                    REQ_POST_NOTIFICATIONS
                )
            }
        }
    }
}
