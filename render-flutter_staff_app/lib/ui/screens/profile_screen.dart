import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_staff_app/state/app_providers.dart';
import 'package:flutter_staff_app/state/native_notification_bridge.dart';
import 'package:flutter_staff_app/ui/theme.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _current = TextEditingController();
  final _next = TextEditingController();
  final _timeController = TextEditingController();
  bool _saving = false;
  bool _timeInitialized = false;
  bool _obscureCurrent = true;
  bool _obscureNext = true;

  @override
  void dispose() {
    _current.dispose();
    _next.dispose();
    _timeController.dispose();
    super.dispose();
  }

  Future<void> _logout() async {
    try {
      await ref.read(mobileApiProvider).logout();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Logout request failed, clearing session locally.')),
        );
      }
    }
    await NativeNotificationBridge.stopBackgroundService();
    await ref.read(tokenStorageProvider).clearToken();
    ref.invalidate(tokenProvider);
    ref.invalidate(meProvider);
  }

  @override
  Widget build(BuildContext context) {
    final me = ref.watch(meProvider);
    final api = ref.read(mobileApiProvider);

    return me.when(
      data: (u) {
        if (!_timeInitialized) {
          _timeController.text = (u.timeAvailability ?? '').isNotEmpty ? u.timeAvailability! : '8:00 AM - 5:00 PM';
          _timeInitialized = true;
        }
        return ListView(
          padding: const EdgeInsets.all(24),
          children: [
            // Header Info
            Center(
              child: Column(
                children: [
                  Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 4),
                      boxShadow: [
                        BoxShadow(
                          color: AppTheme.primaryColor.withValues(alpha: 0.1),
                          blurRadius: 20,
                        ),
                      ],
                    ),
                    child: CircleAvatar(
                      radius: 48,
                      backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                      child: Text(
                        u.name.substring(0, 1).toUpperCase(),
                        style: const TextStyle(
                          fontSize: 36,
                          fontWeight: FontWeight.w900,
                          color: AppTheme.primaryColor,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    u.name,
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w900,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                  Text(
                    u.email,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey.shade500,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor,
                      borderRadius: BorderRadius.circular(99),
                    ),
                    child: Text(
                      u.role.toUpperCase(),
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                        letterSpacing: 1,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 40),
            
            // Availability Section
            const _SectionHeader(label: 'AVAILABILITY'),
            const SizedBox(height: 12),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    DropdownButtonFormField<String>(
                      initialValue: u.availabilityStatus,
                      decoration: const InputDecoration(
                        labelText: 'Current Status',
                        prefixIcon: Icon(Icons.circle_notifications_outlined),
                      ),
                      items: [
                        _statusItem('available', 'Available', Colors.green),
                        _statusItem('away', 'Away', Colors.orange),
                        _statusItem('busy', 'Busy', Colors.red),
                      ],
                      onChanged: _saving
                          ? null
                          : (v) async {
                              if (v == null) return;
                              setState(() => _saving = true);
                              try {
                                await api.updateAvailability(status: v);
                                ref.invalidate(meProvider);
                              } catch (e) {
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Failed to update status.')),
                                  );
                                }
                              } finally {
                                if (mounted) setState(() => _saving = false);
                              }
                            },
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _timeController,
                      decoration: InputDecoration(
                        labelText: 'Time Availability',
                        hintText: 'e.g. 8:00 AM - 5:00 PM',
                        prefixIcon: const Icon(Icons.schedule_rounded),
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.save_rounded, color: AppTheme.primaryColor),
                          tooltip: 'Save Time Availability',
                          onPressed: _saving
                              ? null
                              : () async {
                                  final val = _timeController.text.trim();
                                  if (val.isEmpty) return;
                                  setState(() => _saving = true);
                                  try {
                                    await api.updateAvailability(timeAvailability: val);
                                    ref.invalidate(meProvider);
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Time availability updated successfully!')),
                                      );
                                    }
                                  } catch (e) {
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Failed to update time availability.')),
                                      );
                                    }
                                  } finally {
                                    if (mounted) setState(() => _saving = false);
                                  }
                                },
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 6,
                      children: [
                        '8:00 AM - 5:00 PM',
                        '7:30 AM - 4:30 PM',
                        '9:00 AM - 6:00 PM',
                        '1:00 PM - 5:00 PM',
                      ].map((preset) {
                        return ActionChip(
                          label: Text(preset, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                          backgroundColor: _timeController.text == preset
                              ? AppTheme.primaryColor.withValues(alpha: 0.15)
                              : null,
                          onPressed: () {
                            setState(() => _timeController.text = preset);
                          },
                        );
                      }).toList(),
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 32),
            
            // Security Section
            const _SectionHeader(label: 'SECURITY'),
            const SizedBox(height: 12),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    TextField(
                      controller: _current,
                      obscureText: _obscureCurrent,
                      decoration: InputDecoration(
                        labelText: 'Current Password',
                        prefixIcon: const Icon(Icons.lock_reset_outlined),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureCurrent ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                            size: 20,
                          ),
                          onPressed: () => setState(() => _obscureCurrent = !_obscureCurrent),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _next,
                      obscureText: _obscureNext,
                      decoration: InputDecoration(
                        labelText: 'New Password',
                        prefixIcon: const Icon(Icons.password_outlined),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureNext ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                            size: 20,
                          ),
                          onPressed: () => setState(() => _obscureNext = !_obscureNext),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: _saving
                          ? null
                          : () async {
                              setState(() => _saving = true);
                              try {
                                await api.changePassword(
                                  currentPassword: _current.text,
                                  newPassword: _next.text,
                                );
                                _current.clear();
                                _next.clear();
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Password updated successfully.')),
                                  );
                                }
                              } catch (e) {
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Failed to update password.')),
                                  );
                                }
                              } finally {
                                if (mounted) setState(() => _saving = false);
                              }
                            },
                      child: const Text('Update Password'),
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 40),
            
            // Sign Out Button
            OutlinedButton.icon(
              onPressed: _saving ? null : _logout,
              icon: const Icon(Icons.logout_rounded),
              label: const Text('Sign Out'),
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.red.shade700,
                side: BorderSide(color: Colors.red.shade100),
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (_, __) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text('Profile error'),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () => ref.invalidate(meProvider),
              child: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }

  DropdownMenuItem<String> _statusItem(String value, String label, Color color) {
    return DropdownMenuItem(
      value: value,
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 12),
          Text(label),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String label;

  const _SectionHeader({required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 4),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w900,
          color: Colors.grey.shade400,
          letterSpacing: 1.2,
        ),
      ),
    );
  }
}
