import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_staff_app/models/appointment_request.dart';
import 'package:flutter_staff_app/state/appointments_controller.dart';
import 'package:flutter_staff_app/ui/widgets/status_chip.dart';
import 'package:flutter_staff_app/ui/theme.dart';
import 'package:intl/intl.dart';

class AppointmentsScreen extends ConsumerWidget {
  const AppointmentsScreen({super.key});

  Future<DateTime?> _pickDateTime(BuildContext context) async {
    final now = DateTime.now();
    final d = await showDatePicker(
      context: context,
      firstDate: now,
      lastDate: now.add(const Duration(days: 180)),
      initialDate: now,
    );
    if (d == null) return null;
    if (!context.mounted) return null;
    final t = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(now.add(const Duration(hours: 1))),
    );
    if (t == null) return null;
    return DateTime(d.year, d.month, d.day, t.hour, t.minute);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(appointmentsControllerProvider);
    final controller = ref.read(appointmentsControllerProvider.notifier);

    return state.when(
      data: (items) {
        if (items.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.event_busy_rounded, size: 64, color: Colors.grey.shade300),
                const SizedBox(height: 16),
                Text(
                  'No appointments found',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 16, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          );
        }
        return RefreshIndicator(
          onRefresh: () => controller.refresh(),
          child: ListView.separated(
            padding: const EdgeInsets.all(20),
            itemBuilder: (ctx, i) {
              final a = items[i];
              return _AppointmentCard(
                item: a,
                onAccept: a.status == 'pending'
                    ? () async {
                        final dt = await _pickDateTime(context);
                        if (dt == null) return;
                        try {
                          await controller.accept(a.id, dt);
                        } catch (e) {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Failed to accept appointment.')),
                            );
                          }
                        }
                      }
                    : null,
                onDecline: a.status == 'pending'
                    ? () async {
                        try {
                          await controller.decline(a.id);
                        } catch (e) {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Failed to decline appointment.')),
                            );
                          }
                        }
                      }
                    : null,
              );
            },
            separatorBuilder: (_, __) => const SizedBox(height: 16),
            itemCount: items.length,
          ),
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (_, __) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text('Something went wrong'),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () => controller.refresh(),
              child: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

class _AppointmentCard extends StatelessWidget {
  final AppointmentRequest item;
  final VoidCallback? onAccept;
  final VoidCallback? onDecline;

  const _AppointmentCard({
    required this.item,
    required this.onAccept,
    required this.onDecline,
  });

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('MMM d, y • h:mm a');
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                  child: Text(
                    item.requesterName.substring(0, 1).toUpperCase(),
                    style: const TextStyle(
                      color: AppTheme.primaryColor,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.requesterName,
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      Text(
                        item.requesterEmail,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    StatusChip(label: item.status.toUpperCase()),
                    if ((item.targetUserTimeAvailability ?? '').isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.schedule_rounded, size: 12, color: AppTheme.primaryColor),
                          const SizedBox(width: 4),
                          Text(
                            item.targetUserTimeAvailability!,
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.primaryColor,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ],
            ),
            const SizedBox(height: 20),
            const Divider(height: 1),
            const SizedBox(height: 16),
            
            _InfoRow(
              icon: Icons.label_important_outline_rounded,
              label: 'Purpose',
              value: item.purposeCode,
              valueColor: const Color(0xFF1E293B),
            ),
            if ((item.purposeOtherText ?? '').isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(left: 32, top: 4),
                child: Text(
                  item.purposeOtherText!,
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                ),
              ),
              
            const SizedBox(height: 12),
            _InfoRow(
              icon: Icons.location_on_outlined,
              label: 'Location',
              value: item.waypointName ?? 'Not specified',
            ),
            
            const SizedBox(height: 12),
            _InfoRow(
              icon: Icons.access_time_rounded,
              label: 'Scheduled',
              value: item.scheduledAt != null 
                ? df.format(item.scheduledAt!.toLocal())
                : 'Not scheduled yet',
              valueColor: item.scheduledAt != null ? AppTheme.primaryColor : null,
            ),
            
            if (onAccept != null || onDecline != null) ...[
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      onPressed: onAccept,
                      style: FilledButton.styleFrom(
                        backgroundColor: AppTheme.accentColor,
                      ),
                      child: const Text('Approve'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton(
                      onPressed: onDecline,
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        side: BorderSide(color: Colors.red.shade200),
                        foregroundColor: Colors.red.shade700,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: const Text('Decline'),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20, color: Colors.grey.shade400),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Colors.grey.shade400,
                  letterSpacing: 0.5,
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: valueColor ?? const Color(0xFF475569),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
