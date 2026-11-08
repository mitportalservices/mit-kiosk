import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_staff_app/models/appointment_request.dart';
import 'package:flutter_staff_app/models/consultation.dart';
import 'package:flutter_staff_app/state/native_notification_bridge.dart';

class StaffNotification {
  final String id;
  final String title;
  final String body;
  final String type; // 'appointment' | 'consultation'
  final int recordId;
  final DateTime createdAt;
  final bool isRead;

  const StaffNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    required this.recordId,
    required this.createdAt,
    this.isRead = false,
  });

  StaffNotification copyWith({bool? isRead}) {
    return StaffNotification(
      id: id,
      title: title,
      body: body,
      type: type,
      recordId: recordId,
      createdAt: createdAt,
      isRead: isRead ?? this.isRead,
    );
  }
}

class NotificationsState {
  final List<StaffNotification> notifications;
  final StaffNotification? activePopup;

  const NotificationsState({
    this.notifications = const [],
    this.activePopup,
  });

  int get unreadCount => notifications.where((n) => !n.isRead).length;

  NotificationsState copyWith({
    List<StaffNotification>? notifications,
    StaffNotification? activePopup,
    bool clearPopup = false,
  }) {
    return NotificationsState(
      notifications: notifications ?? this.notifications,
      activePopup: clearPopup ? null : (activePopup ?? this.activePopup),
    );
  }
}

final notificationsControllerProvider =
    NotifierProvider<NotificationsController, NotificationsState>(
  NotificationsController.new,
);

class NotificationsController extends Notifier<NotificationsState> {
  final Set<int> _seenAppointmentIds = {};
  final Set<int> _seenConsultationIds = {};
  bool _initializedAppointments = false;
  bool _initializedConsultations = false;

  @override
  NotificationsState build() {
    _seenAppointmentIds.clear();
    _seenConsultationIds.clear();
    _initializedAppointments = false;
    _initializedConsultations = false;
    return const NotificationsState();
  }

  void syncAppointments(List<AppointmentRequest> items) {
    final currentList = List<StaffNotification>.from(state.notifications);
    StaffNotification? newestPopup;

    if (!_initializedAppointments) {
      _initializedAppointments = true;
      for (final a in items) {
        _seenAppointmentIds.add(a.id);
        if (a.status.toLowerCase() == 'pending') {
          final notif = _fromAppointment(a);
          if (!currentList.any((n) => n.id == notif.id)) {
            currentList.add(notif);
            if (DateTime.now().difference(a.createdAt).inMinutes <= 10) {
              newestPopup ??= notif;
            }
          }
        }
      }
    } else {
      for (final a in items) {
        if (!_seenAppointmentIds.contains(a.id)) {
          _seenAppointmentIds.add(a.id);
          final notif = _fromAppointment(a);
          currentList.insert(0, notif);
          newestPopup = notif;
        }
      }
    }

    currentList.sort((a, b) => b.createdAt.compareTo(a.createdAt));

    if (newestPopup != null) {
      _triggerAlertFeedback(newestPopup);
      state = state.copyWith(
        notifications: currentList,
        activePopup: newestPopup,
      );
    } else if (currentList.length != state.notifications.length) {
      state = state.copyWith(notifications: currentList);
    }
  }

  void syncConsultations(List<Consultation> items) {
    final currentList = List<StaffNotification>.from(state.notifications);
    StaffNotification? newestPopup;

    if (!_initializedConsultations) {
      _initializedConsultations = true;
      for (final c in items) {
        _seenConsultationIds.add(c.id);
        if (c.status.toLowerCase() == 'waiting' ||
            c.status.toLowerCase() == 'pending') {
          final notif = _fromConsultation(c);
          if (!currentList.any((n) => n.id == notif.id)) {
            currentList.add(notif);
            if (DateTime.now().difference(c.createdAt).inMinutes <= 10) {
              newestPopup ??= notif;
            }
          }
        }
      }
    } else {
      for (final c in items) {
        if (!_seenConsultationIds.contains(c.id)) {
          _seenConsultationIds.add(c.id);
          final notif = _fromConsultation(c);
          currentList.insert(0, notif);
          newestPopup = notif;
        }
      }
    }

    currentList.sort((a, b) => b.createdAt.compareTo(a.createdAt));

    if (newestPopup != null) {
      _triggerAlertFeedback(newestPopup);
      state = state.copyWith(
        notifications: currentList,
        activePopup: newestPopup,
      );
    } else if (currentList.length != state.notifications.length) {
      state = state.copyWith(notifications: currentList);
    }
  }

  StaffNotification _fromAppointment(AppointmentRequest a) {
    final loc = (a.waypointName != null && a.waypointName!.isNotEmpty)
        ? ' at ${a.waypointName}'
        : '';
    final requesterType =
        a.requesterType.isNotEmpty ? ' (${a.requesterType.toUpperCase()})' : '';
    return StaffNotification(
      id: 'appt_${a.id}',
      title: 'New Appointment Request',
      body:
          '${a.requesterName}$requesterType requested an appointment for ${a.purposeCode}$loc.',
      type: 'appointment',
      recordId: a.id,
      createdAt: a.createdAt,
    );
  }

  StaffNotification _fromConsultation(Consultation c) {
    final ticket = c.displayTicketNumberPadded ?? c.ticketNumberPadded;
    final loc = (c.waypointName != null && c.waypointName!.isNotEmpty)
        ? ' at ${c.waypointName}'
        : '';
    return StaffNotification(
      id: 'cons_${c.id}',
      title: 'New Consultation Ticket #$ticket',
      body: 'A visitor/student requested a consultation for ${c.purposeCode}$loc.',
      type: 'consultation',
      recordId: c.id,
      createdAt: c.createdAt,
    );
  }

  void _triggerAlertFeedback(StaffNotification notif) {
    try {
      HapticFeedback.heavyImpact();
      SystemSound.play(SystemSoundType.alert);
      final idOffset = notif.type == 'appointment' ? 100000 : 200000;
      NativeNotificationBridge.showHeadsUpNotification(
        id: idOffset + notif.recordId,
        title: notif.title,
        body: notif.body,
      );
    } catch (_) {}
  }

  void dismissPopup() {
    state = state.copyWith(clearPopup: true);
  }

  void markRead(String id) {
    final updated = state.notifications
        .map((n) => n.id == id ? n.copyWith(isRead: true) : n)
        .toList();
    final clearPopup = state.activePopup?.id == id;
    state = state.copyWith(notifications: updated, clearPopup: clearPopup);
  }

  void markAllRead() {
    final updated =
        state.notifications.map((n) => n.copyWith(isRead: true)).toList();
    state = state.copyWith(notifications: updated, clearPopup: true);
  }
}

