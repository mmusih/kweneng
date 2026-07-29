import 'dart:io';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Keep this lightweight.
  // Background notification display is handled by Android/FCM.
}

class NotificationService {
  NotificationService._();

  static final NotificationService instance = NotificationService._();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  static const AndroidNotificationChannel _channel = AndroidNotificationChannel(
    'school_alerts',
    'School Alerts',
    description: 'Important school notices and parent messages.',
    importance: Importance.high,
    playSound: true,
  );

  Future<void> initialise() async {
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

    await _requestPermissions();
    await _setupLocalNotifications();

    FirebaseMessaging.onMessage.listen(_showForegroundNotification);

    FirebaseMessaging.instance.onTokenRefresh.listen((token) async {
      await syncTokenToServer(tokenOverride: token);
    });

    // Important:
    // If the app was reinstalled or Firebase generated a new token,
    // this syncs the token automatically when the app starts,
    // as long as the parent is already logged in.
    await syncTokenToServer();
  }

  Future<void> _requestPermissions() async {
    await _messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );
  }

  Future<void> _setupLocalNotifications() async {
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');

    const initSettings = InitializationSettings(android: androidInit);

    await _localNotifications.initialize(initSettings);

    await _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(_channel);
  }

  Future<void> syncTokenToServer({String? tokenOverride}) async {
    try {
      final api = ApiService();

      final hasAuthToken = await api.hasToken();
      if (!hasAuthToken) return;

      final token = tokenOverride ?? await _messaging.getToken();

      if (token == null || token.isEmpty) return;

      await api.saveFcmToken(
        token: token,
        platform: Platform.isAndroid ? 'android' : 'ios',
      );
    } catch (_) {
      // Do not block app startup/login if notification token sync fails.
    }
  }

  Future<void> deleteTokenFromDevice() async {
    try {
      await _messaging.deleteToken();
    } catch (_) {
      // Ignore token deletion errors.
    }
  }

  Future<void> _showForegroundNotification(RemoteMessage message) async {
    final notification = message.notification;

    final title =
        notification?.title ??
        message.data['title'] ??
        message.data['subject'] ??
        'School Alert';

    final body = notification?.body ?? message.data['body'] ?? '';

    await _localNotifications.show(
      DateTime.now().millisecondsSinceEpoch ~/ 1000,
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          _channel.id,
          _channel.name,
          channelDescription: _channel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
      ),
    );
  }
}
