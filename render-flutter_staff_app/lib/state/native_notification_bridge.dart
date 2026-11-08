import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

class NativeNotificationBridge {
  static const MethodChannel _channel =
      MethodChannel('com.mitkiosk.staffportal/notifications');

  static bool get _isSupported =>
      !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  static Future<void> requestPermission() async {
    if (!_isSupported) return;
    try {
      await _channel.invokeMethod('requestPermission');
    } catch (_) {}
  }

  static Future<void> startBackgroundService({
    required String token,
    required String baseUrl,
    required String role,
  }) async {
    if (!_isSupported || token.isEmpty) return;
    try {
      await _channel.invokeMethod('startBackgroundService', {
        'token': token,
        'baseUrl': baseUrl,
        'role': role,
      });
    } catch (_) {}
  }

  static Future<void> stopBackgroundService() async {
    if (!_isSupported) return;
    try {
      await _channel.invokeMethod('stopBackgroundService');
    } catch (_) {}
  }

  static Future<void> showHeadsUpNotification({
    required int id,
    required String title,
    required String body,
  }) async {
    if (!_isSupported) return;
    try {
      await _channel.invokeMethod('showHeadsUpNotification', {
        'id': id,
        'title': title,
        'body': body,
      });
    } catch (_) {}
  }
}

