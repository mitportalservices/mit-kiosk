package com.example.flutter_staff_app

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.media.RingtoneManager
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.os.Looper
import org.json.JSONArray
import java.io.BufferedReader
import java.io.InputStreamReader
import java.net.HttpURLConnection
import java.net.URL
import java.util.concurrent.Executors

class NotificationPollingService : Service() {

    companion object {
        const val PREFS_NAME = "mit_kiosk_bg_prefs"
        const val KEY_TOKEN = "api_token"
        const val KEY_BASE_URL = "base_url"
        const val KEY_ROLE = "user_role"
        const val KEY_SEEN_APPTS = "seen_appt_ids"
        const val KEY_SEEN_CONS = "seen_cons_ids"
        const val KEY_INITIALIZED = "baseline_initialized"

        const val CHANNEL_ALERTS = "mit_staff_alerts_high"
        const val CHANNEL_SERVICE = "mit_staff_bg_sync"
        const val SERVICE_NOTIF_ID = 99001

        fun startServiceIfLoggedIn(context: Context) {
            val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            val token = prefs.getString(KEY_TOKEN, null)
            if (token.isNullOrEmpty()) return

            val intent = Intent(context, NotificationPollingService::class.java)
            try {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    context.startForegroundService(intent)
                } else {
                    context.startService(intent)
                }
            } catch (_: Throwable) {}
        }

        fun stopService(context: Context) {
            try {
                val intent = Intent(context, NotificationPollingService::class.java)
                context.stopService(intent)
            } catch (_: Throwable) {}
        }

        fun showHeadsUpAlert(context: Context, notifId: Int, title: String, body: String) {
            ensureChannels(context)
            val nm = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

            val openIntent = Intent(context, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
            }
            val piFlags = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
            } else {
                PendingIntent.FLAG_UPDATE_CURRENT
            }
            val pendingIntent = PendingIntent.getActivity(context, notifId, openIntent, piFlags)

            val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                Notification.Builder(context, CHANNEL_ALERTS)
            } else {
                @Suppress("DEPRECATION")
                Notification.Builder(context)
                    .setPriority(Notification.PRIORITY_MAX)
                    .setDefaults(Notification.DEFAULT_ALL)
                    .setSound(RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION))
            }

            builder.setSmallIcon(R.mipmap.ic_launcher)
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(Notification.BigTextStyle().bigText(body))
                .setAutoCancel(true)
                .setContentIntent(pendingIntent)

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                builder.setCategory(Notification.CATEGORY_MESSAGE)
                    .setVisibility(Notification.VISIBILITY_PUBLIC)
            }

            nm.notify(notifId, builder.build())
        }

        fun ensureChannels(context: Context) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                val nm = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

                val alertChannel = NotificationChannel(
                    CHANNEL_ALERTS,
                    "Appointments & Consultations Alerts",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "Pop-up alerts for new campus kiosk appointments and consultation tickets"
                    enableVibration(true)
                    enableLights(true)
                    lockscreenVisibility = Notification.VISIBILITY_PUBLIC
                }
                nm.createNotificationChannel(alertChannel)

                val syncChannel = NotificationChannel(
                    CHANNEL_SERVICE,
                    "Background Live Sync",
                    NotificationManager.IMPORTANCE_MIN
                ).apply {
                    description = "Keeps MIT Staff Portal connected to receive instant notifications when closed"
                    setShowBadge(false)
                }
                nm.createNotificationChannel(syncChannel)
            }
        }
    }

    private val handler = Handler(Looper.getMainLooper())
    private val executor = Executors.newSingleThreadExecutor()
    private var running = false

    private val pollRunnable = object : Runnable {
        override fun run() {
            if (!running) return
            executor.execute { pollOnce() }
            handler.postDelayed(this, 10000L) // Poll every 10 seconds even when app is closed
        }
    }

    override fun onCreate() {
        super.onCreate()
        ensureChannels(this)
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        ensureChannels(this)
        val notification = buildForegroundNotification()
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.UPSIDE_DOWN_CAKE) {
                startForeground(
                    SERVICE_NOTIF_ID,
                    notification,
                    ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC
                )
            } else {
                startForeground(SERVICE_NOTIF_ID, notification)
            }
        } catch (_: Throwable) {}

        if (!running) {
            running = true
            handler.post(pollRunnable)
        }
        return START_STICKY
    }

    override fun onTaskRemoved(rootIntent: Intent?) {
        super.onTaskRemoved(rootIntent)
        // Restart service if the user swipes the app away from Recents while still logged in
        startServiceIfLoggedIn(applicationContext)
    }

    override fun onDestroy() {
        running = false
        handler.removeCallbacks(pollRunnable)
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun buildForegroundNotification(): Notification {
        val openIntent = Intent(this, MainActivity::class.java)
        val piFlags = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        } else {
            PendingIntent.FLAG_UPDATE_CURRENT
        }
        val pi = PendingIntent.getActivity(this, 0, openIntent, piFlags)

        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, CHANNEL_SERVICE)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this).setPriority(Notification.PRIORITY_MIN)
        }

        return builder
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle("MIT Staff Portal Active")
            .setContentText("Listening for new appointments & consultations")
            .setContentIntent(pi)
            .setOngoing(true)
            .build()
    }

    private fun pollOnce() {
        try {
            val prefs = getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            val token = prefs.getString(KEY_TOKEN, null)
            if (token.isNullOrEmpty()) {
                stopSelf()
                return
            }

            val baseUrl = (prefs.getString(KEY_BASE_URL, null) ?: "https://laravel-web-app-kr4g.onrender.com").trimEnd('/')
            val role = prefs.getString(KEY_ROLE, "faculty") ?: "faculty"
            val isAdmin = role == "admin"

            val apptPath = if (isAdmin) "/api/mobile/admin/appointments" else "/api/mobile/appointments"
            val consPath = if (isAdmin) "/api/mobile/admin/consultations" else "/api/mobile/consultations"

            val apptsJson = fetchJsonArray("$baseUrl$apptPath", token)
            val consJson = fetchJsonArray("$baseUrl$consPath", token)
            if (apptsJson == null && consJson == null) return

            val initialized = prefs.getBoolean(KEY_INITIALIZED, false)
            val seenAppts = (prefs.getStringSet(KEY_SEEN_APPTS, emptySet()) ?: emptySet()).toMutableSet()
            val seenCons = (prefs.getStringSet(KEY_SEEN_CONS, emptySet()) ?: emptySet()).toMutableSet()

            if (!initialized) {
                if (apptsJson != null) {
                    for (i in 0 until apptsJson.length()) {
                        val obj = apptsJson.optJSONObject(i) ?: continue
                        val id = obj.optInt("id", 0)
                        if (id > 0) seenAppts.add(id.toString())
                    }
                }
                if (consJson != null) {
                    for (i in 0 until consJson.length()) {
                        val obj = consJson.optJSONObject(i) ?: continue
                        val id = obj.optInt("id", 0)
                        if (id > 0) seenCons.add(id.toString())
                    }
                }
                prefs.edit()
                    .putBoolean(KEY_INITIALIZED, true)
                    .putStringSet(KEY_SEEN_APPTS, seenAppts)
                    .putStringSet(KEY_SEEN_CONS, seenCons)
                    .apply()
                return
            }

            var changed = false

            if (apptsJson != null) {
                for (i in 0 until apptsJson.length()) {
                    val obj = apptsJson.optJSONObject(i) ?: continue
                    val id = obj.optInt("id", 0)
                    if (id <= 0 || seenAppts.contains(id.toString())) continue

                    seenAppts.add(id.toString())
                    changed = true

                    val status = obj.optString("status", "").lowercase()
                    if (status == "pending") {
                        val reqName = obj.optString("requester_name", "A student/visitor")
                        val reqType = obj.optString("requester_type", "").uppercase()
                        val purpose = obj.optString("purpose_code", "an appointment")
                        val typeLabel = if (reqType.isNotEmpty()) " ($reqType)" else ""
                        showHeadsUpAlert(
                            this,
                            100000 + id,
                            "New Appointment Request",
                            "$reqName$typeLabel requested an appointment for $purpose."
                        )
                    }
                }
            }

            if (consJson != null) {
                for (i in 0 until consJson.length()) {
                    val obj = consJson.optJSONObject(i) ?: continue
                    val id = obj.optInt("id", 0)
                    if (id <= 0 || seenCons.contains(id.toString())) continue

                    seenCons.add(id.toString())
                    changed = true

                    val status = obj.optString("status", "").lowercase()
                    if (status == "waiting" || status == "pending") {
                        val displayTicket = obj.optString("display_ticket_number", "")
                        val ticketNum = if (displayTicket.isNotEmpty()) {
                            displayTicket
                        } else {
                            obj.optInt("ticket_number", id).toString().padStart(3, '0')
                        }
                        val purpose = obj.optString("purpose_code", "consultation")
                        showHeadsUpAlert(
                            this,
                            200000 + id,
                            "New Consultation Ticket #$ticketNum",
                            "A visitor/student requested a consultation for $purpose."
                        )
                    }
                }
            }

            if (changed) {
                prefs.edit()
                    .putStringSet(KEY_SEEN_APPTS, seenAppts)
                    .putStringSet(KEY_SEEN_CONS, seenCons)
                    .apply()
            }
        } catch (_: Throwable) {}
    }

    private fun fetchJsonArray(urlStr: String, token: String): JSONArray? {
        var conn: HttpURLConnection? = null
        return try {
            val url = URL(urlStr)
            conn = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "GET"
                connectTimeout = 8000
                readTimeout = 8000
                setRequestProperty("Accept", "application/json")
                setRequestProperty("Authorization", "Bearer $token")
            }
            if (conn.responseCode == 200) {
                val reader = BufferedReader(InputStreamReader(conn.inputStream))
                val sb = StringBuilder()
                var line: String?
                while (reader.readLine().also { line = it } != null) {
                    sb.append(line)
                }
                reader.close()
                JSONArray(sb.toString())
            } else {
                null
            }
        } catch (_: Throwable) {
            null
        } finally {
            conn?.disconnect()
        }
    }
}

