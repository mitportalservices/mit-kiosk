import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_staff_app/config.dart';
import 'package:flutter_staff_app/state/app_providers.dart';
import 'package:flutter_staff_app/state/appointments_controller.dart';
import 'package:flutter_staff_app/state/consultations_controller.dart';
import 'package:flutter_staff_app/state/native_notification_bridge.dart';
import 'package:flutter_staff_app/state/notifications_controller.dart';
import 'package:flutter_staff_app/ui/screens/appointments_screen.dart';
import 'package:flutter_staff_app/ui/screens/consultations_screen.dart';
import 'package:flutter_staff_app/ui/screens/profile_screen.dart';
import 'package:flutter_staff_app/ui/theme.dart';
import 'package:flutter_staff_app/ui/admin/admin_shell.dart';
import 'package:intl/intl.dart';

class HomeShell extends ConsumerStatefulWidget {
  const HomeShell({super.key});

  @override
  ConsumerState<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends ConsumerState<HomeShell> {
  int _index = 0;
  Timer? _pollingTimer;
  Timer? _popupDismissTimer;
  String? _lastPopupId;

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      try {
        final user = await ref.read(meProvider.future);
        final token = await ref.read(tokenStorageProvider).readToken();
        if (token != null && token.isNotEmpty) {
          await NativeNotificationBridge.startBackgroundService(
            token: token,
            baseUrl: AppConfig.apiBaseUrl,
            role: user.role,
          );
        }
        if (user.role != 'admin' && mounted) {
          ref.invalidate(notificationsControllerProvider);
          await _pollData(initial: true);
          _startPolling();
        }
      } catch (_) {}
    });
  }

  Future<void> _pollData({bool initial = false}) async {
    if (!mounted) return;
    if (initial) {
      await Future.wait([
        ref.read(appointmentsControllerProvider.notifier).refresh(),
        ref.read(consultationsControllerProvider.notifier).refresh(),
      ]);
    } else {
      await Future.wait([
        ref.read(appointmentsControllerProvider.notifier).refreshSilently(),
        ref.read(consultationsControllerProvider.notifier).refreshSilently(),
      ]);
    }
    if (!mounted) return;
    final appts = ref.read(appointmentsControllerProvider).valueOrNull;
    if (appts != null) {
      ref.read(notificationsControllerProvider.notifier).syncAppointments(appts);
    }
    final cons = ref.read(consultationsControllerProvider).valueOrNull;
    if (cons != null) {
      ref.read(notificationsControllerProvider.notifier).syncConsultations(cons);
    }
  }

  void _startPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (mounted) {
        _pollData();
      }
    });
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _popupDismissTimer?.cancel();
    super.dispose();
  }

  void _showNotificationsSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Consumer(
        builder: (ctx, sheetRef, _) {
          final notifState = sheetRef.watch(notificationsControllerProvider);
          final list = notifState.notifications;

          return Container(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(ctx).size.height * 0.75,
            ),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
            ),
            child: SafeArea(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const SizedBox(height: 12),
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 16, 12),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.notifications_active_rounded,
                          color: AppTheme.primaryColor,
                        ),
                        const SizedBox(width: 10),
                        const Expanded(
                          child: Text(
                            'Notifications',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFF1E293B),
                            ),
                          ),
                        ),
                        if (notifState.unreadCount > 0)
                          TextButton(
                            onPressed: () {
                              sheetRef
                                  .read(notificationsControllerProvider.notifier)
                                  .markAllRead();
                            },
                            child: const Text(
                              'Mark all read',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  if (list.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(
                        vertical: 48,
                        horizontal: 24,
                      ),
                      child: Column(
                        children: [
                          Icon(
                            Icons.notifications_none_rounded,
                            size: 48,
                            color: Colors.grey.shade300,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'No notifications yet',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'New appointment and consultation requests will pop up here automatically.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade400,
                            ),
                          ),
                        ],
                      ),
                    )
                  else
                    Flexible(
                      child: ListView.separated(
                        shrinkWrap: true,
                        padding: const EdgeInsets.all(16),
                        itemCount: list.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (ctx, i) {
                          final item = list[i];
                          final isAppt = item.type == 'appointment';
                          final timeStr = DateFormat('MMM d, h:mm a')
                              .format(item.createdAt.toLocal());

                          return InkWell(
                            borderRadius: BorderRadius.circular(16),
                            onTap: () {
                              sheetRef
                                  .read(notificationsControllerProvider.notifier)
                                  .markRead(item.id);
                              Navigator.pop(ctx);
                              setState(() {
                                _index = isAppt ? 0 : 1;
                              });
                            },
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: item.isRead
                                    ? const Color(0xFFF8FAFC)
                                    : AppTheme.primaryColor.withValues(alpha: 0.06),
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(
                                  color: item.isRead
                                      ? const Color(0xFFE2E8F0)
                                      : AppTheme.primaryColor.withValues(alpha: 0.25),
                                ),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      color: isAppt
                                          ? const Color(0xFF8B5CF6).withValues(alpha: 0.12)
                                          : const Color(0xFFF59E0B).withValues(alpha: 0.12),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Icon(
                                      isAppt
                                          ? Icons.calendar_today_rounded
                                          : Icons.confirmation_number_rounded,
                                      size: 20,
                                      color: isAppt
                                          ? const Color(0xFF8B5CF6)
                                          : const Color(0xFFF59E0B),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Expanded(
                                              child: Text(
                                                item.title,
                                                style: TextStyle(
                                                  fontSize: 13,
                                                  fontWeight: item.isRead
                                                      ? FontWeight.w700
                                                      : FontWeight.w900,
                                                  color: const Color(0xFF1E293B),
                                                ),
                                              ),
                                            ),
                                            if (!item.isRead)
                                              Container(
                                                width: 8,
                                                height: 8,
                                                decoration: const BoxDecoration(
                                                  color: AppTheme.primaryColor,
                                                  shape: BoxShape.circle,
                                                ),
                                              ),
                                          ],
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          item.body,
                                          style: const TextStyle(
                                            fontSize: 12,
                                            color: Color(0xFF475569),
                                            height: 1.35,
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        Text(
                                          timeStr,
                                          style: const TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.w600,
                                            color: Color(0xFF94A3B8),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final me = ref.watch(meProvider);

    if (me.valueOrNull?.role == 'admin') {
      return const AdminShell();
    }

    final notifState = ref.watch(notificationsControllerProvider);
    final activePopup = notifState.activePopup;

    if (activePopup != null && activePopup.id != _lastPopupId) {
      _lastPopupId = activePopup.id;
      _popupDismissTimer?.cancel();
      _popupDismissTimer = Timer(const Duration(seconds: 8), () {
        if (mounted) {
          ref.read(notificationsControllerProvider.notifier).dismissPopup();
        }
      });
    }

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        title: me.when(
          data: (u) => Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: AppTheme.primaryColor.withValues(alpha: 0.2),
                    width: 2,
                  ),
                ),
                child: CircleAvatar(
                  radius: 18,
                  backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                  child: Text(
                    u.name.substring(0, 1).toUpperCase(),
                    style: const TextStyle(
                      color: AppTheme.primaryColor,
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    u.name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      u.role.toUpperCase(),
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.primaryColor,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          loading: () => const Text('Loading profile…'),
          error: (_, __) => const Text('Staff Portal'),
        ),
        actions: [
          Stack(
            alignment: Alignment.center,
            children: [
              IconButton(
                tooltip: 'Notifications',
                icon: Icon(
                  notifState.unreadCount > 0
                      ? Icons.notifications_active_rounded
                      : Icons.notifications_none_rounded,
                  color: notifState.unreadCount > 0
                      ? AppTheme.primaryColor
                      : const Color(0xFF64748B),
                ),
                onPressed: _showNotificationsSheet,
              ),
              if (notifState.unreadCount > 0)
                Positioned(
                  right: 8,
                  top: 8,
                  child: GestureDetector(
                    onTap: _showNotificationsSheet,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 5,
                        vertical: 1.5,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEF4444),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.white, width: 1.5),
                      ),
                      child: Text(
                        notifState.unreadCount > 9
                            ? '9+'
                            : '${notifState.unreadCount}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Stack(
        children: [
          PageTransitionSwitcher(
            index: _index,
            children: const [
              AppointmentsScreen(),
              ConsultationsScreen(),
              ProfileScreen(),
            ],
          ),
          if (activePopup != null)
            Positioned(
              top: 12,
              left: 16,
              right: 16,
              child: _PopupNotificationBanner(
                notification: activePopup,
                onView: () {
                  final isAppt = activePopup.type == 'appointment';
                  ref
                      .read(notificationsControllerProvider.notifier)
                      .markRead(activePopup.id);
                  setState(() {
                    _index = isAppt ? 0 : 1;
                  });
                },
                onDismiss: () {
                  ref
                      .read(notificationsControllerProvider.notifier)
                      .dismissPopup();
                },
              ),
            ),
        ],
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 20,
              offset: const Offset(0, -5),
            ),
          ],
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _NavButton(
                  index: 0,
                  currentIndex: _index,
                  icon: Icons.calendar_today_rounded,
                  label: 'Schedule',
                  onTap: (i) => setState(() => _index = i),
                ),
                _NavButton(
                  index: 1,
                  currentIndex: _index,
                  icon: Icons.chat_bubble_outline_rounded,
                  label: 'Chats',
                  onTap: (i) => setState(() => _index = i),
                ),
                _NavButton(
                  index: 2,
                  currentIndex: _index,
                  icon: Icons.person_outline_rounded,
                  label: 'Account',
                  onTap: (i) => setState(() => _index = i),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _PopupNotificationBanner extends StatelessWidget {
  final StaffNotification notification;
  final VoidCallback onView;
  final VoidCallback onDismiss;

  const _PopupNotificationBanner({
    required this.notification,
    required this.onView,
    required this.onDismiss,
  });

  @override
  Widget build(BuildContext context) {
    final isAppt = notification.type == 'appointment';
    final accentColor =
        isAppt ? const Color(0xFF8B5CF6) : const Color(0xFFF59E0B);

    return Material(
      elevation: 12,
      shadowColor: Colors.black.withValues(alpha: 0.2),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF0F172A),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: accentColor.withValues(alpha: 0.6), width: 1.5),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: accentColor.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                isAppt
                    ? Icons.calendar_month_rounded
                    : Icons.notifications_active_rounded,
                color: accentColor,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: accentColor,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          isAppt ? 'NEW APPOINTMENT' : 'NEW CONSULTATION',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 9,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.5,
                          ),
                        ),
                      ),
                      const Spacer(),
                      GestureDetector(
                        onTap: onDismiss,
                        child: const Icon(
                          Icons.close_rounded,
                          color: Color(0xFF94A3B8),
                          size: 18,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    notification.title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    notification.body,
                    style: const TextStyle(
                      color: Color(0xFFCBD5E1),
                      fontSize: 12,
                      height: 1.3,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Align(
                    alignment: Alignment.centerRight,
                    child: FilledButton.tonalIcon(
                      onPressed: onView,
                      style: FilledButton.styleFrom(
                        backgroundColor: accentColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 6,
                        ),
                        minimumSize: const Size(0, 32),
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      icon: const Icon(Icons.open_in_new_rounded, size: 14),
                      label: const Text(
                        'View Now',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  final int index;
  final int currentIndex;
  final IconData icon;
  final String label;
  final Function(int) onTap;

  const _NavButton({
    required this.index,
    required this.currentIndex,
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isSelected = index == currentIndex;
    final color = isSelected ? AppTheme.primaryColor : const Color(0xFF94A3B8);

    return GestureDetector(
      onTap: () => onTap(index),
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? AppTheme.primaryColor.withValues(alpha: 0.1)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.w800 : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class PageTransitionSwitcher extends StatelessWidget {
  final int index;
  final List<Widget> children;

  const PageTransitionSwitcher({
    super.key,
    required this.index,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 300),
      transitionBuilder: (child, animation) {
        return FadeTransition(
          opacity: animation,
          child: SlideTransition(
            position: Tween<Offset>(
              begin: const Offset(0, 0.02),
              end: Offset.zero,
            ).animate(animation),
            child: child,
          ),
        );
      },
      child: KeyedSubtree(
        key: ValueKey<int>(index),
        child: children[index],
      ),
    );
  }
}
