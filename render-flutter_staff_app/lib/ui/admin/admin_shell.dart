import 'dart:async';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_staff_app/models/appointment_request.dart';
import 'package:flutter_staff_app/models/consultation.dart';
import 'package:flutter_staff_app/state/app_providers.dart';
import 'package:flutter_staff_app/state/notifications_controller.dart';
import 'package:flutter_staff_app/ui/theme.dart';
import 'package:intl/intl.dart';

class AdminShell extends ConsumerStatefulWidget {
  const AdminShell({super.key});

  @override
  ConsumerState<AdminShell> createState() => _AdminShellState();
}

class _AdminShellState extends ConsumerState<AdminShell> {
  int _currentSection = 0;
  bool _isDark = false;
  Timer? _pollingTimer;
  Timer? _popupDismissTimer;
  String? _lastPopupId;

  final List<({String title, IconData icon})> _sections = const [
    (title: 'Overview', icon: Icons.dashboard_rounded),
    (title: 'Waypoints', icon: Icons.map_rounded),
    (title: 'Accounts', icon: Icons.manage_accounts_rounded),
    (title: 'Appointments', icon: Icons.event_rounded),
    (title: 'Consultations', icon: Icons.forum_rounded),
    (title: 'Feedback', icon: Icons.rate_review_rounded),
    (title: 'Activity Logs', icon: Icons.history_rounded),
    (title: 'Settings', icon: Icons.settings_rounded),
  ];

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      ref.invalidate(notificationsControllerProvider);
      await _pollAdminNotifications();
      _pollingTimer = Timer.periodic(const Duration(seconds: 5), (_) {
        if (mounted) _pollAdminNotifications();
      });
    });
  }

  Future<void> _pollAdminNotifications() async {
    try {
      final api = ref.read(mobileApiProvider);
      final rawAppts = await api.getAdminAppointments();
      final rawCons = await api.getAdminConsultations();
      if (!mounted) return;

      final appts = rawAppts
          .whereType<Map>()
          .map((e) => AppointmentRequest.fromJson(e.cast<String, dynamic>()))
          .toList();
      final cons = rawCons
          .whereType<Map>()
          .map((e) => Consultation.fromJson(e.cast<String, dynamic>()))
          .toList();

      ref.read(notificationsControllerProvider.notifier).syncAppointments(appts);
      ref.read(notificationsControllerProvider.notifier).syncConsultations(cons);
    } catch (_) {}
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _popupDismissTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final me = ref.watch(meProvider);
    final user = me.valueOrNull;
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

    final bgColor = _isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC);
    final cardColor = _isDark ? const Color(0xFF1E293B) : Colors.white;
    final textColor = _isDark ? const Color(0xFFF8FAFC) : const Color(0xFF1E293B);
    final mutedColor = _isDark ? const Color(0xFF94A3B8) : const Color(0xFF64748B);
    final borderColor = _isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0);

    return Theme(
      data: _isDark ? ThemeData.dark().copyWith(
        scaffoldBackgroundColor: bgColor,
        colorScheme: const ColorScheme.dark(
          primary: AppTheme.primaryColor,
          surface: Color(0xFF1E293B),
        ),
      ) : AppTheme.light,
      child: Scaffold(
        backgroundColor: bgColor,
        appBar: AppBar(
          backgroundColor: cardColor,
          elevation: 0,
          leading: Builder(
            builder: (ctx) => IconButton(
              icon: Icon(Icons.menu_rounded, color: textColor),
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
          title: Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.admin_panel_settings_rounded, color: Colors.white, size: 20),
              ),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'MIT Admin',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: textColor,
                    ),
                  ),
                  Text(
                    _sections[_currentSection].title,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                ],
              ),
            ],
          ),
          actions: [
            IconButton(
              icon: Icon(_isDark ? Icons.light_mode_rounded : Icons.dark_mode_rounded, color: mutedColor),
              onPressed: () => setState(() => _isDark = !_isDark),
              tooltip: 'Toggle Theme',
            ),
            IconButton(
              icon: const Icon(Icons.logout_rounded, color: Colors.redAccent),
              onPressed: () async {
                final confirm = await showDialog<bool>(
                  context: context,
                  builder: (ctx) => AlertDialog(
                    title: const Text('Log Out'),
                    content: const Text('Are you sure you want to log out of the admin panel?'),
                    actions: [
                      TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
                      FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Logout')),
                    ],
                  ),
                );
                if (confirm == true) {
                  await ref.read(mobileApiProvider).logout();
                  await ref.read(tokenStorageProvider).clearToken();
                  ref.invalidate(tokenProvider);
                }
              },
              tooltip: 'Log out',
            ),
            const SizedBox(width: 8),
          ],
        ),
        drawer: Drawer(
          backgroundColor: cardColor,
          child: SafeArea(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: AppTheme.primaryColor,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: AppTheme.primaryColor.withValues(alpha: 0.3),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: const Icon(Icons.admin_panel_settings_rounded, color: Colors.white, size: 28),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?.name ?? 'Administrator',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: textColor,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                            Container(
                              margin: const EdgeInsets.only(top: 4),
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppTheme.primaryColor.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: const Text(
                                'ADMIN PORTAL',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w900,
                                  color: AppTheme.primaryColor,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                Divider(height: 1, color: borderColor),
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    itemCount: _sections.length,
                    itemBuilder: (ctx, i) {
                      final item = _sections[i];
                      final isSelected = _currentSection == i;
                      return Container(
                        margin: const EdgeInsets.only(bottom: 4),
                        decoration: BoxDecoration(
                          color: isSelected ? AppTheme.primaryColor : Colors.transparent,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: ListTile(
                          dense: true,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          leading: Icon(
                            item.icon,
                            color: isSelected ? Colors.white : mutedColor,
                            size: 22,
                          ),
                          title: Text(
                            item.title,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                              color: isSelected ? Colors.white : textColor,
                            ),
                          ),
                          onTap: () {
                            setState(() => _currentSection = i);
                            Navigator.pop(ctx);
                          },
                        ),
                      );
                    },
                  ),
                ),
                Container(
                  margin: const EdgeInsets.all(16),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 10,
                        height: 10,
                        decoration: const BoxDecoration(
                          color: Color(0xFF10B981),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 10),
                      const Text(
                        'Live Kiosk Synced',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.primaryColor,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        body: Stack(
          children: [
            _buildCurrentSection(),
            if (activePopup != null)
              Positioned(
                top: 12,
                left: 16,
                right: 16,
                child: Material(
                  elevation: 12,
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: AppTheme.primaryColor.withValues(alpha: 0.6),
                        width: 1.5,
                      ),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.notifications_active_rounded,
                          color: AppTheme.primaryColor,
                          size: 24,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                activePopup.title,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 13,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                activePopup.body,
                                style: const TextStyle(
                                  color: Color(0xFFCBD5E1),
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        ),
                        TextButton(
                          onPressed: () {
                            final isAppt = activePopup.type == 'appointment';
                            ref
                                .read(notificationsControllerProvider.notifier)
                                .markRead(activePopup.id);
                            setState(() {
                              _currentSection = isAppt ? 3 : 4;
                            });
                          },
                          child: const Text(
                            'View',
                            style: TextStyle(fontWeight: FontWeight.w800),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(
                            Icons.close_rounded,
                            color: Color(0xFF94A3B8),
                            size: 18,
                          ),
                          onPressed: () {
                            ref
                                .read(notificationsControllerProvider.notifier)
                                .dismissPopup();
                          },
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildCurrentSection() {
    switch (_currentSection) {
      case 0:
        return _AdminOverviewTab(onNavigate: (index) => setState(() => _currentSection = index));
      case 1:
        return const _AdminWaypointsTab();
      case 2:
        return const _AdminAccountsTab();
      case 3:
        return const _AdminAppointmentsTab();
      case 4:
        return const _AdminConsultationsTab();
      case 5:
        return const _AdminFeedbackTab();
      case 6:
        return const _AdminActivityLogsTab();
      case 7:
        return const _AdminSettingsTab();
      default:
        return const Center(child: Text('Section not found'));
    }
  }
}

// ---------------------------------------------------------------------------
// 1. OVERVIEW / DASHBOARD TAB
// ---------------------------------------------------------------------------
class _AdminOverviewTab extends ConsumerStatefulWidget {
  final Function(int) onNavigate;
  const _AdminOverviewTab({required this.onNavigate});

  @override
  ConsumerState<_AdminOverviewTab> createState() => _AdminOverviewTabState();
}

class _AdminOverviewTabState extends ConsumerState<_AdminOverviewTab> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminDashboard();
      if (mounted) setState(() => _data = res);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    final counts = (_data?['counts'] as Map?) ?? {};
    final recentLogs = (_data?['recent_logs'] as List?) ?? [];
    final recentFeedbacks = (_data?['recent_feedbacks'] as List?) ?? [];

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // Header Bento
          const Text(
            'Campus Overview',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 4),
          const Text(
            'Real-time status of your digital campus guide & staff system.',
            style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 20),

          // Counts Grid
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.5,
            children: [
              _buildStatCard('Faculty Accounts', '${counts['faculty'] ?? 0}', Icons.school_rounded, const Color(0xFF3B82F6), () => widget.onNavigate(2)),
              _buildStatCard('Active Waypoints', '${counts['waypoints'] ?? 0}', Icons.location_on_rounded, const Color(0xFF10B981), () => widget.onNavigate(1)),
              _buildStatCard('Appointments', '${counts['appointments'] ?? 0}', Icons.calendar_today_rounded, const Color(0xFF8B5CF6), () => widget.onNavigate(3)),
              _buildStatCard('Consultations', '${counts['consultations'] ?? 0}', Icons.forum_rounded, const Color(0xFFF59E0B), () => widget.onNavigate(4)),
              _buildStatCard('Kiosk Settings', 'Configure', Icons.tune_rounded, const Color(0xFF6366F1), () => widget.onNavigate(7)),
              _buildStatCard('Activity Logs', 'Audit Trail', Icons.history_rounded, const Color(0xFF0EA5E9), () => widget.onNavigate(6)),
            ],
          ),

          const SizedBox(height: 24),

          // Recent Activity Logs Section
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Recent Activity Logs',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
              ),
              TextButton(
                onPressed: () => widget.onNavigate(6),
                child: const Text('View All'),
              ),
            ],
          ),
          if (recentLogs.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: Text('No activity logs recorded yet.')),
              ),
            )
          else
            ...recentLogs.take(4).map((log) {
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: ListTile(
                  dense: true,
                  leading: CircleAvatar(
                    backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.12),
                    child: const Icon(Icons.history_rounded, color: AppTheme.primaryColor, size: 20),
                  ),
                  title: Text(
                    log['description'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                  ),
                  subtitle: Text(
                    '${log['user_name'] ?? 'System'} • ${log['created_at'] != null ? DateFormat('MMM d, h:mm a').format(DateTime.parse(log['created_at'])) : ''}',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                  ),
                ),
              );
            }),

          const SizedBox(height: 24),

          // Recent Feedback Section
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Recent Kiosk Feedback',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
              ),
              TextButton(
                onPressed: () => widget.onNavigate(5),
                child: const Text('View All'),
              ),
            ],
          ),
          if (recentFeedbacks.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: Text('No feedback collected yet.')),
              ),
            )
          else
            ...recentFeedbacks.take(3).map((fb) {
              final rating = fb['rating'] ?? 'Neutral';
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: ListTile(
                  dense: true,
                  leading: Text(
                    _ratingEmoji(rating),
                    style: const TextStyle(fontSize: 26),
                  ),
                  title: Text(
                    rating,
                    style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                  ),
                  subtitle: Text(
                    fb['suggestions']?.isNotEmpty == true ? fb['suggestions'] : 'No suggestions provided',
                    style: const TextStyle(fontSize: 12),
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String count, IconData icon, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Card(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
                  ),
                  Icon(icon, color: color, size: 20),
                ],
              ),
              Text(
                count,
                style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 2. WAYPOINTS MANAGEMENT TAB
// ---------------------------------------------------------------------------
class _AdminWaypointsTab extends ConsumerStatefulWidget {
  const _AdminWaypointsTab();

  @override
  ConsumerState<_AdminWaypointsTab> createState() => _AdminWaypointsTabState();
}

class _AdminWaypointsTabState extends ConsumerState<_AdminWaypointsTab> {
  List<dynamic> _waypoints = [];
  List<dynamic> _availableStaff = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminWaypoints();
      if (mounted) {
        setState(() {
          _waypoints = res['waypoints'] ?? [];
          _availableStaff = res['available_staff'] ?? [];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  void _showAddEditDialog([Map<String, dynamic>? wp]) {
    final isEdit = wp != null;
    final nameCtrl = TextEditingController(text: wp?['name'] ?? '');
    final codeCtrl = TextEditingController(text: wp?['location_code'] ?? '');
    final descCtrl = TextEditingController(text: wp?['description'] ?? '');
    final floorCtrl = TextEditingController(text: wp?['floor']?.toString() ?? '1');
    final xCtrl = TextEditingController(text: wp?['coordinates_x']?.toString() ?? '50');
    final yCtrl = TextEditingController(text: wp?['coordinates_y']?.toString() ?? '50');
    final panoCtrl = TextEditingController(text: wp?['pano_url'] ?? '');

    List<int> selectedStaffIds = [];
    if (wp?['assigned_users'] is List) {
      selectedStaffIds = (wp!['assigned_users'] as List).map((u) => (u['id'] as num).toInt()).toList();
    }

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => AlertDialog(
          title: Text(isEdit ? 'Edit Waypoint' : 'Add New Waypoint'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(controller: nameCtrl, decoration: const InputDecoration(labelText: 'Waypoint Name')),
                const SizedBox(height: 10),
                TextField(controller: codeCtrl, decoration: const InputDecoration(labelText: 'Location Code (e.g. WP-001)')),
                const SizedBox(height: 10),
                TextField(controller: descCtrl, maxLines: 2, decoration: const InputDecoration(labelText: 'Description')),
                const SizedBox(height: 10),
                TextField(controller: floorCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Floor')),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(child: TextField(controller: xCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Coord X'))),
                    const SizedBox(width: 8),
                    Expanded(child: TextField(controller: yCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Coord Y'))),
                  ],
                ),
                const SizedBox(height: 10),
                TextField(controller: panoCtrl, decoration: const InputDecoration(labelText: 'Panorama URL (Optional)')),
                const SizedBox(height: 14),
                const Align(alignment: Alignment.centerLeft, child: Text('Assign Staff Member:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  children: _availableStaff.map((s) {
                    final sid = (s['id'] as num).toInt();
                    final isAssigned = selectedStaffIds.contains(sid);
                    return FilterChip(
                      label: Text(s['name'] ?? '', style: const TextStyle(fontSize: 11)),
                      selected: isAssigned,
                      onSelected: (val) {
                        setDlgState(() {
                          if (val) {
                            selectedStaffIds.add(sid);
                          } else {
                            selectedStaffIds.remove(sid);
                          }
                        });
                      },
                    );
                  }).toList(),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
            FilledButton(
              onPressed: () async {
                final payload = {
                  'name': nameCtrl.text.trim(),
                  'location_code': codeCtrl.text.trim(),
                  'description': descCtrl.text.trim(),
                  'floor': floorCtrl.text.trim(),
                  'coordinates_x': double.tryParse(xCtrl.text) ?? 50,
                  'coordinates_y': double.tryParse(yCtrl.text) ?? 50,
                  'pano_url': panoCtrl.text.trim(),
                  'assigned_user_ids': selectedStaffIds,
                };
                Navigator.pop(ctx);
                try {
                  if (isEdit) {
                    await ref.read(mobileApiProvider).updateWaypoint(wp['id'], payload);
                  } else {
                    await ref.read(mobileApiProvider).createWaypoint(payload);
                  }
                  _load();
                } catch (e) {
                  if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
                }
              },
              child: Text(isEdit ? 'Update' : 'Create'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddEditDialog(),
        icon: const Icon(Icons.add),
        label: const Text('Add Waypoint'),
        backgroundColor: AppTheme.primaryColor,
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView.builder(
          padding: const EdgeInsets.all(20),
          itemCount: _waypoints.length,
          itemBuilder: (ctx, i) {
            final wp = _waypoints[i];
            final users = (wp['assigned_users'] as List?) ?? [];
            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppTheme.primaryColor.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(Icons.location_on_rounded, color: AppTheme.primaryColor, size: 24),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(wp['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                                Text('Floor ${wp['floor']} • ${wp['location_code']}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                              ],
                            ),
                          ],
                        ),
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.edit_rounded, color: AppTheme.primaryColor, size: 20),
                              onPressed: () => _showAddEditDialog(wp),
                            ),
                            IconButton(
                              icon: const Icon(Icons.delete_rounded, color: Colors.redAccent, size: 20),
                              onPressed: () async {
                                final confirm = await showDialog<bool>(
                                  context: context,
                                  builder: (c) => AlertDialog(
                                    title: const Text('Delete Waypoint'),
                                    content: Text('Delete "${wp['name']}"?'),
                                    actions: [
                                      TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancel')),
                                      FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Delete')),
                                    ],
                                  ),
                                );
                                if (confirm == true) {
                                  await ref.read(mobileApiProvider).deleteWaypoint(wp['id']);
                                  _load();
                                }
                              },
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(wp['description'] ?? '', style: const TextStyle(fontSize: 12, color: Color(0xFF475569))),
                    if (users.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 6,
                        runSpacing: 4,
                        children: users.map((u) {
                          return Chip(
                            avatar: const Icon(Icons.person, size: 14),
                            label: Text(u['name'] ?? '', style: const TextStyle(fontSize: 10)),
                            padding: EdgeInsets.zero,
                          );
                        }).toList(),
                      ),
                    ],
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 3. ACCOUNTS MANAGEMENT TAB
// ---------------------------------------------------------------------------
class _AdminAccountsTab extends ConsumerStatefulWidget {
  const _AdminAccountsTab();

  @override
  ConsumerState<_AdminAccountsTab> createState() => _AdminAccountsTabState();
}

class _AdminAccountsTabState extends ConsumerState<_AdminAccountsTab> {
  List<dynamic> _users = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminUsers();
      if (mounted) setState(() => _users = res);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  String _extractDioError(Object e) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map) {
        if (data['errors'] is Map) {
          final errors = data['errors'] as Map;
          for (final val in errors.values) {
            if (val is List && val.isNotEmpty) {
              return val.first.toString();
            }
          }
        }
        if (data['message'] != null) {
          return data['message'].toString();
        }
      }
    }
    return e.toString();
  }

  void _showChangePasswordDialog(Map<String, dynamic> user) {
    final passCtrl = TextEditingController();
    bool obscure = true;
    bool saving = false;
    String? errText;
    final userId = (user['id'] as num).toInt();

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => AlertDialog(
          title: Text('Change Password — ${user['name'] ?? ''}'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextField(
                controller: passCtrl,
                obscureText: obscure,
                enabled: !saving,
                decoration: InputDecoration(
                  labelText: 'New Password (min 6 chars)',
                  prefixIcon: const Icon(Icons.lock_reset_rounded),
                  suffixIcon: IconButton(
                    icon: Icon(obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                    onPressed: () => setDlgState(() => obscure = !obscure),
                  ),
                ),
              ),
              if (errText != null) ...[
                const SizedBox(height: 10),
                Text(
                  errText!,
                  style: const TextStyle(color: Colors.redAccent, fontSize: 12, fontWeight: FontWeight.w600),
                ),
              ],
            ],
          ),
          actions: [
            TextButton(
              onPressed: saving ? null : () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: saving
                  ? null
                  : () async {
                      final newPass = passCtrl.text.trim();
                      if (newPass.length < 6) {
                        setDlgState(() => errText = 'Password must be at least 6 characters.');
                        return;
                      }
                      setDlgState(() {
                        saving = true;
                        errText = null;
                      });
                      try {
                        await ref.read(mobileApiProvider).updateAdminUser(userId, {
                          'password': newPass,
                        });
                        if (ctx.mounted) Navigator.pop(ctx);
                        _load();
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              backgroundColor: const Color(0xFF10B981),
                              content: Text('Password updated for ${user['name']}.'),
                            ),
                          );
                        }
                      } catch (e) {
                        setDlgState(() {
                          saving = false;
                          errText = _extractDioError(e);
                        });
                      }
                    },
              child: saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('Update Password'),
            ),
          ],
        ),
      ),
    );
  }

  void _showAddEditDialog([Map<String, dynamic>? user]) {
    final isEdit = user != null;
    const validTitles = ['', 'Mr.', 'Ms.', 'Mx.', 'Dr.', 'Prof.'];
    final rawTitle = (user?['title'] ?? '').toString().trim();
    String selectedTitle = validTitles.contains(rawTitle) ? rawTitle : '';

    final nameCtrl = TextEditingController(text: (user?['name'] ?? '').toString());
    final emailCtrl = TextEditingController(text: (user?['email'] ?? '').toString());
    final timeCtrl = TextEditingController(
      text: (user?['time_availability'] ?? '8:00 AM - 5:00 PM').toString(),
    );
    final passCtrl = TextEditingController();

    const validRoles = ['faculty', 'principal', 'guidance_counselor', 'admin'];
    final rawRole = (user?['role'] ?? 'faculty').toString();
    String role = validRoles.contains(rawRole) ? rawRole : 'faculty';

    const validStatuses = ['available', 'busy', 'away'];
    final rawStatus = (user?['availability_status'] ?? 'available').toString();
    String status = validStatuses.contains(rawStatus) ? rawStatus : 'available';

    bool obscurePass = true;
    bool saving = false;
    String? errText;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => AlertDialog(
          title: Text(isEdit ? 'Edit Account' : 'Add New Account'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<String>(
                  initialValue: selectedTitle,
                  decoration: const InputDecoration(labelText: 'Honorific Title'),
                  items: const [
                    DropdownMenuItem(value: '', child: Text('None')),
                    DropdownMenuItem(value: 'Mr.', child: Text('Mr.')),
                    DropdownMenuItem(value: 'Ms.', child: Text('Ms.')),
                    DropdownMenuItem(value: 'Mx.', child: Text('Mx.')),
                    DropdownMenuItem(value: 'Dr.', child: Text('Dr.')),
                    DropdownMenuItem(value: 'Prof.', child: Text('Prof.')),
                  ],
                  onChanged: (v) => setDlgState(() => selectedTitle = v ?? ''),
                ),
                const SizedBox(height: 10),
                TextField(controller: nameCtrl, decoration: const InputDecoration(labelText: 'Full Name')),
                const SizedBox(height: 10),
                TextField(controller: emailCtrl, decoration: const InputDecoration(labelText: 'Email Address')),
                const SizedBox(height: 10),
                TextField(
                  controller: timeCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Time Availability (e.g. 8:00 AM - 5:00 PM)',
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: passCtrl,
                  obscureText: obscurePass,
                  decoration: InputDecoration(
                    labelText: isEdit ? 'New Password (leave blank to keep)' : 'Password (min 6 chars)',
                    suffixIcon: IconButton(
                      icon: Icon(obscurePass ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                      onPressed: () => setDlgState(() => obscurePass = !obscurePass),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: role,
                  decoration: const InputDecoration(labelText: 'Role'),
                  items: const [
                    DropdownMenuItem(value: 'faculty', child: Text('Faculty / Instructor')),
                    DropdownMenuItem(value: 'principal', child: Text('School Principal')),
                    DropdownMenuItem(value: 'guidance_counselor', child: Text('Guidance Counselor')),
                    DropdownMenuItem(value: 'admin', child: Text('Administrator')),
                  ],
                  onChanged: (v) => setDlgState(() => role = v!),
                ),
                if (isEdit) ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: status,
                    decoration: const InputDecoration(labelText: 'Availability Status'),
                    items: const [
                      DropdownMenuItem(value: 'available', child: Text('🟢 Available')),
                      DropdownMenuItem(value: 'busy', child: Text('🔴 Busy')),
                      DropdownMenuItem(value: 'away', child: Text('🟡 Away')),
                    ],
                    onChanged: (v) => setDlgState(() => status = v!),
                  ),
                ],
                if (errText != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    errText!,
                    style: const TextStyle(color: Colors.redAccent, fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: saving ? null : () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: saving
                  ? null
                  : () async {
                      final name = nameCtrl.text.trim();
                      final email = emailCtrl.text.trim();
                      final pass = passCtrl.text.trim();
                      if (name.isEmpty || email.isEmpty) {
                        setDlgState(() => errText = 'Name and email are required.');
                        return;
                      }
                      if (!isEdit && pass.length < 6) {
                        setDlgState(() => errText = 'Password must be at least 6 characters.');
                        return;
                      }
                      if (isEdit && pass.isNotEmpty && pass.length < 6) {
                        setDlgState(() => errText = 'New password must be at least 6 characters.');
                        return;
                      }

                      final payload = {
                        'name': name,
                        'title': selectedTitle.isNotEmpty ? selectedTitle : null,
                        'email': email,
                        'role': role,
                        'time_availability': timeCtrl.text.trim().isNotEmpty ? timeCtrl.text.trim() : null,
                        if (pass.isNotEmpty) 'password': pass,
                        if (isEdit) 'availability_status': status,
                      };

                      setDlgState(() {
                        saving = true;
                        errText = null;
                      });

                      try {
                        if (isEdit) {
                          final userId = (user['id'] as num).toInt();
                          await ref.read(mobileApiProvider).updateAdminUser(userId, payload);
                        } else {
                          await ref.read(mobileApiProvider).createAdminUser(payload);
                        }
                        if (ctx.mounted) Navigator.pop(ctx);
                        _load();
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              backgroundColor: const Color(0xFF10B981),
                              content: Text(isEdit ? 'Account updated successfully!' : 'Account created successfully!'),
                            ),
                          );
                        }
                      } catch (e) {
                        setDlgState(() {
                          saving = false;
                          errText = _extractDioError(e);
                        });
                      }
                    },
              child: saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : Text(isEdit ? 'Save Changes' : 'Create Account'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    return Scaffold(
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddEditDialog(),
        icon: const Icon(Icons.person_add_rounded),
        label: const Text('Add Account'),
        backgroundColor: AppTheme.primaryColor,
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView.builder(
          padding: const EdgeInsets.all(20),
          itemCount: _users.length,
          itemBuilder: (ctx, i) {
            final u = _users[i];
            final role = u['role'] ?? 'staff';
            final status = u['availability_status'] ?? 'available';

            final statusColor = status == 'available'
                ? const Color(0xFF10B981)
                : (status == 'busy' ? const Color(0xFFEF4444) : const Color(0xFFF59E0B));

            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              child: ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                leading: Stack(
                  children: [
                    CircleAvatar(
                      backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.12),
                      child: Text(
                        (u['name'] as String? ?? 'U').substring(0, 1).toUpperCase(),
                        style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryColor),
                      ),
                    ),
                    Positioned(
                      right: 0,
                      bottom: 0,
                      child: Container(
                        width: 12,
                        height: 12,
                        decoration: BoxDecoration(
                          color: statusColor,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                        ),
                      ),
                    ),
                  ],
                ),
                title: Text(
                  '${['Mr.', 'Ms.', 'Mx.', 'Dr.', 'Prof.'].contains(u['title']) ? '${u['title']} ' : ''}${u['name'] ?? ''}',
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
                ),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(u['email'] ?? '', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                    if ((u['time_availability'] as String?)?.isNotEmpty == true)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(
                          '⏰ ${u['time_availability']}',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                        ),
                      ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: role == 'admin' ? Colors.purple.withValues(alpha: 0.12) : AppTheme.primaryColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        role.toUpperCase(),
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w900,
                          color: role == 'admin' ? Colors.purple : AppTheme.primaryColor,
                        ),
                      ),
                    ),
                  ],
                ),
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.key_rounded, color: Color(0xFFF59E0B), size: 20),
                      tooltip: 'Change Password',
                      onPressed: () => _showChangePasswordDialog(u),
                    ),
                    IconButton(
                      icon: const Icon(Icons.edit_rounded, color: AppTheme.primaryColor, size: 20),
                      tooltip: 'Edit Account',
                      onPressed: () => _showAddEditDialog(u),
                    ),
                    IconButton(
                      icon: const Icon(Icons.delete_rounded, color: Colors.redAccent, size: 20),
                      onPressed: () async {
                        final confirm = await showDialog<bool>(
                          context: context,
                          builder: (c) => AlertDialog(
                            title: const Text('Delete Account'),
                            content: Text('Delete account for "${u['name']}"?'),
                            actions: [
                              TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancel')),
                              FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Delete')),
                            ],
                          ),
                        );
                        if (confirm == true) {
                          try {
                            await ref.read(mobileApiProvider).deleteAdminUser(u['id']);
                            _load();
                          } catch (e) {
                            if (mounted) ScaffoldMessenger.of(this.context).showSnackBar(SnackBar(content: Text('Error: $e')));
                          }
                        }
                      },
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 4. APPOINTMENTS TAB
// ---------------------------------------------------------------------------
class _AdminAppointmentsTab extends ConsumerStatefulWidget {
  const _AdminAppointmentsTab();

  @override
  ConsumerState<_AdminAppointmentsTab> createState() => _AdminAppointmentsTabState();
}

class _AdminAppointmentsTabState extends ConsumerState<_AdminAppointmentsTab> {
  List<dynamic> _appointments = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminAppointments();
      if (mounted) setState(() => _appointments = res);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.all(20),
        itemCount: _appointments.length,
        itemBuilder: (ctx, i) {
          final a = _appointments[i];
          final status = a['status'] ?? 'pending';
          final target = a['target_user']?['name'] ?? 'Any Staff';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        a['requester_name'] ?? 'Visitor',
                        style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: status == 'accepted'
                              ? Colors.green.withValues(alpha: 0.12)
                              : (status == 'declined' ? Colors.red.withValues(alpha: 0.12) : Colors.orange.withValues(alpha: 0.12)),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          status.toUpperCase(),
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
                            color: status == 'accepted' ? Colors.green : (status == 'declined' ? Colors.red : Colors.orange),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text('Target: $target', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.primaryColor)),
                  Text('Email: ${a['requester_email']}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  Text('Purpose: ${a['purpose_code']}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      if (status == 'pending') ...[
                        FilledButton.tonal(
                          onPressed: () async {
                            final date = await showDatePicker(
                              context: context,
                              initialDate: DateTime.now().add(const Duration(days: 1)),
                              firstDate: DateTime.now(),
                              lastDate: DateTime.now().add(const Duration(days: 60)),
                            );
                            if (date != null && mounted) {
                              final time = await showTimePicker(
                                context: this.context,
                                initialTime: const TimeOfDay(hour: 9, minute: 0),
                              );
                              if (time != null) {
                                final sched = DateTime(date.year, date.month, date.day, time.hour, time.minute);
                                await ref.read(mobileApiProvider).adminAcceptAppointment(a['id'], sched);
                                _load();
                              }
                            }
                          },
                          child: const Text('Accept', style: TextStyle(fontSize: 12)),
                        ),
                        const SizedBox(width: 8),
                        OutlinedButton(
                          onPressed: () async {
                            await ref.read(mobileApiProvider).adminDeclineAppointment(a['id']);
                            _load();
                          },
                          child: const Text('Decline', style: TextStyle(fontSize: 12)),
                        ),
                        const SizedBox(width: 8),
                      ],
                      IconButton(
                        icon: const Icon(Icons.delete_rounded, color: Colors.redAccent, size: 20),
                        onPressed: () async {
                          final confirm = await showDialog<bool>(
                            context: context,
                            builder: (c) => AlertDialog(
                              title: const Text('Delete Appointment'),
                              content: const Text('Delete this appointment record?'),
                              actions: [
                                TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancel')),
                                FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Delete')),
                              ],
                            ),
                          );
                          if (confirm == true) {
                            await ref.read(mobileApiProvider).deleteAdminAppointment(a['id']);
                            _load();
                          }
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 5. CONSULTATIONS TAB
// ---------------------------------------------------------------------------
class _AdminConsultationsTab extends ConsumerStatefulWidget {
  const _AdminConsultationsTab();

  @override
  ConsumerState<_AdminConsultationsTab> createState() => _AdminConsultationsTabState();
}

class _AdminConsultationsTabState extends ConsumerState<_AdminConsultationsTab> {
  List<dynamic> _consultations = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminConsultations();
      if (mounted) setState(() => _consultations = res);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.all(20),
        itemCount: _consultations.length,
        itemBuilder: (ctx, i) {
          final c = _consultations[i];
          final status = c['status'] ?? 'waiting';
          final staff = c['assigned_user']?['name'] ?? 'Unassigned';
          final ticket = c['ticket_number']?.toString().padLeft(3, '0') ?? '001';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Center(
                      child: Text(
                        ticket,
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppTheme.primaryColor),
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(c['purpose_code'] ?? 'Consultation', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                        Text('Staff: $staff', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                        Text('Status: ${status.toUpperCase()}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                  Row(
                    children: [
                      if (status == 'waiting')
                        IconButton(
                          icon: const Icon(Icons.play_arrow_rounded, color: Colors.green),
                          onPressed: () async {
                            await ref.read(mobileApiProvider).adminStartConsultation(c['id']);
                            _load();
                          },
                          tooltip: 'Start Consultation',
                        )
                      else if (status == 'in_progress')
                        IconButton(
                          icon: const Icon(Icons.check_circle_rounded, color: Colors.blue),
                          onPressed: () async {
                            await ref.read(mobileApiProvider).adminFinishConsultation(c['id']);
                            _load();
                          },
                          tooltip: 'Finish Consultation',
                        ),
                      IconButton(
                        icon: const Icon(Icons.delete_rounded, color: Colors.redAccent, size: 20),
                        onPressed: () async {
                          final confirm = await showDialog<bool>(
                            context: context,
                            builder: (b) => AlertDialog(
                              title: const Text('Delete Consultation'),
                              content: Text('Delete Ticket #$ticket?'),
                              actions: [
                                TextButton(onPressed: () => Navigator.pop(b, false), child: const Text('Cancel')),
                                FilledButton(onPressed: () => Navigator.pop(b, true), child: const Text('Delete')),
                              ],
                            ),
                          );
                          if (confirm == true) {
                            await ref.read(mobileApiProvider).deleteAdminConsultation(c['id']);
                            _load();
                          }
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 6. FEEDBACK TAB
// ---------------------------------------------------------------------------
class _AdminFeedbackTab extends ConsumerStatefulWidget {
  const _AdminFeedbackTab();

  @override
  ConsumerState<_AdminFeedbackTab> createState() => _AdminFeedbackTabState();
}

class _AdminFeedbackTabState extends ConsumerState<_AdminFeedbackTab> {
  List<dynamic> _feedbacks = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminFeedbacks();
      if (mounted) setState(() => _feedbacks = res);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  void _showEditFeedbackDialog(Map<String, dynamic> fb) {
    String rating = fb['rating'] ?? 'Satisfied';
    final suggCtrl = TextEditingController(text: fb['suggestions'] ?? '');

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => AlertDialog(
          title: const Text('Edit Feedback'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String>(
                initialValue: rating,
                decoration: const InputDecoration(labelText: 'Rating'),
                items: const [
                  DropdownMenuItem(value: 'Very Satisfied', child: Text('😄 Very Satisfied')),
                  DropdownMenuItem(value: 'Satisfied', child: Text('🙂 Satisfied')),
                  DropdownMenuItem(value: 'Neutral', child: Text('😐 Neutral')),
                  DropdownMenuItem(value: 'Dissatisfied', child: Text('🙁 Dissatisfied')),
                  DropdownMenuItem(value: 'Very Dissatisfied', child: Text('😞 Very Dissatisfied')),
                ],
                onChanged: (v) => setDlgState(() => rating = v!),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: suggCtrl,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Suggestions / Comments'),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
            FilledButton(
              onPressed: () async {
                Navigator.pop(ctx);
                try {
                  await ref.read(mobileApiProvider).updateAdminFeedback(
                    fb['id'],
                    rating: rating,
                    suggestions: suggCtrl.text.trim().isEmpty ? null : suggCtrl.text.trim(),
                  );
                  _load();
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Feedback updated successfully.')),
                    );
                  }
                } catch (e) {
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Error updating feedback: $e')),
                    );
                  }
                }
              },
              child: const Text('Save Changes'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.all(20),
        itemCount: _feedbacks.length,
        itemBuilder: (ctx, i) {
          final fb = _feedbacks[i];
          final rating = fb['rating'] ?? 'Neutral';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            child: ListTile(
              contentPadding: const EdgeInsets.all(16),
              leading: Text(_ratingEmoji(rating), style: const TextStyle(fontSize: 32)),
              title: Text(rating, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14)),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 4),
                  Text(
                    fb['suggestions']?.isNotEmpty == true ? fb['suggestions'] : 'No suggestions',
                    style: const TextStyle(fontSize: 12),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    fb['created_at'] != null ? DateFormat('MMM d, yyyy h:mm a').format(DateTime.parse(fb['created_at'])) : '',
                    style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                  ),
                ],
              ),
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  IconButton(
                    icon: const Icon(Icons.edit_rounded, color: AppTheme.primaryColor, size: 20),
                    onPressed: () => _showEditFeedbackDialog(fb),
                    tooltip: 'Edit Feedback',
                  ),
                  IconButton(
                    icon: const Icon(Icons.delete_rounded, color: Colors.redAccent, size: 20),
                    onPressed: () async {
                      final confirm = await showDialog<bool>(
                        context: context,
                        builder: (b) => AlertDialog(
                          title: const Text('Delete Feedback'),
                          content: const Text('Delete this feedback submission?'),
                          actions: [
                            TextButton(onPressed: () => Navigator.pop(b, false), child: const Text('Cancel')),
                            FilledButton(onPressed: () => Navigator.pop(b, true), child: const Text('Delete')),
                          ],
                        ),
                      );
                      if (confirm == true) {
                        await ref.read(mobileApiProvider).deleteAdminFeedback(fb['id']);
                        _load();
                      }
                    },
                    tooltip: 'Delete Feedback',
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 7. ACTIVITY LOGS TAB
// ---------------------------------------------------------------------------
class _AdminActivityLogsTab extends ConsumerStatefulWidget {
  const _AdminActivityLogsTab();

  @override
  ConsumerState<_AdminActivityLogsTab> createState() => _AdminActivityLogsTabState();
}

class _AdminActivityLogsTabState extends ConsumerState<_AdminActivityLogsTab> {
  List<dynamic> _logs = [];
  bool _loading = true;
  String _selectedRole = 'all';
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminActivityLogs(
        role: _selectedRole,
        search: _searchCtrl.text.trim(),
      );
      if (mounted) setState(() => _logs = res);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Filter bar
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Column(
            children: [
              Row(
                children: [
                  _roleChip('all', 'All'),
                  const SizedBox(width: 6),
                  _roleChip('admin', 'Admin'),
                  const SizedBox(width: 6),
                  _roleChip('staff', 'Staff'),
                ],
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _searchCtrl,
                decoration: InputDecoration(
                  hintText: 'Search logs...',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.clear),
                    onPressed: () {
                      _searchCtrl.clear();
                      _load();
                    },
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                ),
                onSubmitted: (_) => _load(),
              ),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _logs.isEmpty
                      ? const Center(child: Text('No activity logs match your filter.'))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _logs.length,
                          itemBuilder: (ctx, i) {
                            final log = _logs[i];
                            final role = log['user_role'] ?? 'system';
                            final action = (log['action'] as String? ?? '').replaceAll('_', ' ').toUpperCase();

                            return Card(
                              margin: const EdgeInsets.only(bottom: 10),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              child: Padding(
                                padding: const EdgeInsets.all(14),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: AppTheme.primaryColor.withValues(alpha: 0.12),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            action,
                                            style: const TextStyle(
                                              fontSize: 9,
                                              fontWeight: FontWeight.w900,
                                              color: AppTheme.primaryColor,
                                            ),
                                          ),
                                        ),
                                        Text(
                                          log['created_at'] != null ? DateFormat('MMM d, h:mm:ss a').format(DateTime.parse(log['created_at'])) : '',
                                          style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      log['description'] ?? '',
                                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800),
                                    ),
                                    const SizedBox(height: 6),
                                    Row(
                                      children: [
                                        Text(
                                          'Actor: ${log['user_name'] ?? 'System'}',
                                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold),
                                        ),
                                        const SizedBox(width: 8),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                          decoration: BoxDecoration(
                                            color: Colors.grey.withValues(alpha: 0.15),
                                            borderRadius: BorderRadius.circular(4),
                                          ),
                                          child: Text(
                                            role.toUpperCase(),
                                            style: const TextStyle(fontSize: 8, fontWeight: FontWeight.w800),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                ),
        ),
      ],
    );
  }

  Widget _roleChip(String value, String label) {
    final isSelected = _selectedRole == value;
    return ChoiceChip(
      label: Text(label, style: const TextStyle(fontSize: 11)),
      selected: isSelected,
      onSelected: (val) {
        if (val) {
          setState(() => _selectedRole = value);
          _load();
        }
      },
    );
  }
}

String _ratingEmoji(String rating) {
  switch (rating) {
    case 'Very Satisfied':
      return '😄';
    case 'Satisfied':
      return '🙂';
    case 'Neutral':
      return '😐';
    case 'Dissatisfied':
      return '🙁';
    case 'Very Dissatisfied':
      return '😞';
    default:
      return '💬';
  }
}

// ---------------------------------------------------------------------------
// 8. SETTINGS & SERVICE HOURS TAB
// ---------------------------------------------------------------------------
class _AdminSettingsTab extends ConsumerStatefulWidget {
  const _AdminSettingsTab();

  @override
  ConsumerState<_AdminSettingsTab> createState() => _AdminSettingsTabState();
}

class _AdminSettingsTabState extends ConsumerState<_AdminSettingsTab> {
  bool _loading = true;
  bool _saving = false;
  bool _testingEmail = false;

  final TextEditingController _openTimeCtrl = TextEditingController(text: '08:00');
  final TextEditingController _closeTimeCtrl = TextEditingController(text: '17:00');
  final TextEditingController _daysCtrl = TextEditingController(text: 'Monday - Friday');
  final TextEditingController _titleCtrl = TextEditingController(text: 'Campus Guide - MIT');
  final TextEditingController _emailCtrl = TextEditingController(text: 'support@mit-maps.com');
  final TextEditingController _testEmailCtrl = TextEditingController();
  String _status = 'online';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(mobileApiProvider).getAdminSettings();
      if (mounted) {
        setState(() {
          _openTimeCtrl.text = res['service_hours_start'] ?? '08:00';
          _closeTimeCtrl.text = res['service_hours_end'] ?? '17:00';
          _daysCtrl.text = res['service_days'] ?? 'Monday - Friday';
          _titleCtrl.text = res['kiosk_title'] ?? 'Campus Guide - MIT';
          _status = res['kiosk_status'] ?? 'online';
          _emailCtrl.text = res['contact_email'] ?? 'support@mit-maps.com';
          if (_testEmailCtrl.text.isEmpty) {
            _testEmailCtrl.text = res['contact_email'] ?? 'admin@mit.edu';
          }
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      final payload = {
        'service_hours_start': _openTimeCtrl.text.trim(),
        'service_hours_end': _closeTimeCtrl.text.trim(),
        'service_days': _daysCtrl.text.trim(),
        'kiosk_title': _titleCtrl.text.trim(),
        'kiosk_status': _status,
        'contact_email': _emailCtrl.text.trim(),
      };
      await ref.read(mobileApiProvider).updateAdminSettings(payload);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            backgroundColor: Color(0xFF10B981),
            content: Row(
              children: [
                Icon(Icons.check_circle_rounded, color: Colors.white),
                SizedBox(width: 8),
                Text('Kiosk settings and service hours updated successfully.', style: TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.redAccent,
            content: Text('Failed to update settings: $e'),
          ),
        );
      }
    }
    if (mounted) setState(() => _saving = false);
  }

  Future<void> _sendTestEmail() async {
    final email = _testEmailCtrl.text.trim();
    if (email.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a recipient email address.')),
      );
      return;
    }

    setState(() => _testingEmail = true);
    try {
      final res = await ref.read(mobileApiProvider).testAdminEmail(email);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: const Color(0xFF10B981),
            content: Text(res['message'] ?? 'Test email sent successfully!'),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: Colors.redAccent,
            content: Text('Failed to send test email: $e'),
          ),
        );
      }
    }
    if (mounted) setState(() => _testingEmail = false);
  }

  Future<void> _pickTime(TextEditingController ctrl) async {
    final parts = ctrl.text.split(':');
    final initialTime = TimeOfDay(
      hour: int.tryParse(parts.firstOrNull ?? '8') ?? 8,
      minute: int.tryParse(parts.elementAtOrNull(1) ?? '0') ?? 0,
    );

    final picked = await showTimePicker(
      context: context,
      initialTime: initialTime,
    );
    if (picked != null) {
      final h = picked.hour.toString().padLeft(2, '0');
      final m = picked.minute.toString().padLeft(2, '0');
      ctrl.text = '$h:$m';
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // Header
          const Text(
            'Kiosk Settings',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 4),
          const Text(
            'Configure operating service hours, kiosk branding, and availability status.',
            style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 20),

          // 1. Service Hours Configuration Card
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            elevation: 0,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: AppTheme.primaryColor.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(Icons.schedule_rounded, color: AppTheme.primaryColor, size: 24),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Service Hours Configuration',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                            ),
                            Text(
                              'Define active hours for campus consultations and appointments.',
                              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _openTimeCtrl,
                          readOnly: true,
                          onTap: () => _pickTime(_openTimeCtrl),
                          decoration: InputDecoration(
                            labelText: 'Opening Time',
                            suffixIcon: IconButton(
                              icon: const Icon(Icons.access_time_rounded),
                              onPressed: () => _pickTime(_openTimeCtrl),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: _closeTimeCtrl,
                          readOnly: true,
                          onTap: () => _pickTime(_closeTimeCtrl),
                          decoration: InputDecoration(
                            labelText: 'Closing Time',
                            suffixIcon: IconButton(
                              icon: const Icon(Icons.access_time_rounded),
                              onPressed: () => _pickTime(_closeTimeCtrl),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _daysCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Operating Service Days',
                      hintText: 'e.g. Monday - Friday',
                      prefixIcon: Icon(Icons.calendar_month_rounded),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // 2. Kiosk System & Display Card
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            elevation: 0,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(Icons.display_settings_rounded, color: Color(0xFF6366F1), size: 24),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Kiosk System & Display',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                            ),
                            Text(
                              'General kiosk parameters and system operating status.',
                              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _titleCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Kiosk Title Display',
                      prefixIcon: Icon(Icons.branding_watermark_rounded),
                    ),
                  ),
                  const SizedBox(height: 14),
                  DropdownButtonFormField<String>(
                    initialValue: _status,
                    decoration: const InputDecoration(
                      labelText: 'Operating Status',
                      prefixIcon: Icon(Icons.power_settings_new_rounded),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'online', child: Text('🟢 Online (Normal Operation)')),
                      DropdownMenuItem(value: 'maintenance', child: Text('🟡 Maintenance Mode')),
                      DropdownMenuItem(value: 'offline', child: Text('🔴 Offline')),
                    ],
                    onChanged: (v) => setState(() => _status = v!),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _emailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Support Contact Email',
                      prefixIcon: Icon(Icons.contact_mail_rounded),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Save Button
          FilledButton.icon(
            onPressed: _saving ? null : _save,
            style: FilledButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
              padding: const EdgeInsets.symmetric(vertical: 18),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
              elevation: 4,
            ),
            icon: _saving
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.save_rounded, size: 22),
            label: Text(
              _saving ? 'Saving Settings…' : 'Update Service Hours & Settings',
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, letterSpacing: 0.3),
            ),
          ),
          const SizedBox(height: 24),

          // 3. Email & SMTP Diagnostics Card
          Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            elevation: 0,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: const Color(0xFF10B981).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(Icons.mark_email_read_rounded, color: Color(0xFF10B981), size: 24),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Email & SMTP Diagnostics',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                            ),
                            Text(
                              'Verify email credentials and test sending real emails.',
                              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.grey.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('MAIL DRIVER', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Color(0xFF64748B))),
                              SizedBox(height: 2),
                              Text('SMTP', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800)),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.grey.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('PORT / SECURITY', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Color(0xFF64748B))),
                              SizedBox(height: 2),
                              Text('587 (TLS)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800)),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: _testEmailCtrl,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Test Recipient Email',
                      prefixIcon: Icon(Icons.email_outlined),
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: _testingEmail ? null : _sendTestEmail,
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        side: const BorderSide(color: Color(0xFF10B981), width: 1.5),
                        foregroundColor: const Color(0xFF10B981),
                      ),
                      icon: _testingEmail
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF10B981)))
                          : const Icon(Icons.send_rounded, size: 18),
                      label: Text(
                        _testingEmail ? 'Sending Test Email…' : 'Send Test Email',
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 40),
        ],
      ),
    );
  }
}
