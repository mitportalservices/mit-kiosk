import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_staff_app/models/consultation.dart';
import 'package:flutter_staff_app/state/app_providers.dart';
import 'package:flutter_staff_app/state/consultations_controller.dart';
import 'package:flutter_staff_app/ui/widgets/status_chip.dart';
import 'package:flutter_staff_app/ui/theme.dart';
import 'package:intl/intl.dart';

class ConsultationsScreen extends ConsumerStatefulWidget {
  const ConsultationsScreen({super.key});

  @override
  ConsumerState<ConsultationsScreen> createState() => _ConsultationsScreenState();
}

class _ConsultationsScreenState extends ConsumerState<ConsultationsScreen> {
  bool _showCompleted = false;

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(consultationsControllerProvider);
    final controller = ref.read(consultationsControllerProvider.notifier);
    final me = ref.watch(meProvider).valueOrNull;
    final role = me?.role ?? 'faculty';

    return state.when(
      data: (items) {
        final filtered = _showCompleted
            ? items
            : items.where((c) => c.status != 'completed').toList();

        return RefreshIndicator(
          onRefresh: () => controller.refresh(),
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Consultation Queue',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF1E293B),
                          ),
                        ),
                        Text(
                          'Manage your active requests',
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                  FilterChip(
                    label: const Text('All'),
                    selected: _showCompleted,
                    onSelected: (v) => setState(() => _showCompleted = v),
                    backgroundColor: Colors.white,
                    selectedColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                    checkmarkColor: AppTheme.primaryColor,
                    labelStyle: TextStyle(
                      color: _showCompleted ? AppTheme.primaryColor : Colors.grey.shade600,
                      fontWeight: _showCompleted ? FontWeight.w700 : FontWeight.w500,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              if (filtered.isEmpty)
                Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 60),
                    child: Column(
                      children: [
                        Icon(Icons.forum_outlined, size: 64, color: Colors.grey.shade200),
                        const SizedBox(height: 16),
                        Text(
                          'No consultations in the queue.',
                          style: TextStyle(color: Colors.grey.shade400, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),
                ),
              ...filtered.map(
                (c) => Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: _ConsultationCard(
                    item: c,
                    role: role,
                    onStart: c.status == 'waiting'
                        ? () async {
                            try {
                              await controller.start(c.id);
                            } catch (e) {
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Failed to start.')),
                                );
                              }
                            }
                          }
                        : null,
                    onFinish: (c.status == 'waiting' || c.status == 'in_progress')
                        ? () async {
                            try {
                              await controller.finish(c.id);
                            } catch (e) {
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Failed to finish.')),
                                );
                              }
                            }
                          }
                        : null,
                  ),
                ),
              ),
            ],
          ),
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (_, __) => Center(
        child: FilledButton(
          onPressed: () => controller.refresh(),
          child: const Text('Retry'),
        ),
      ),
    );
  }
}

class _ConsultationCard extends StatelessWidget {
  final Consultation item;
  final String role;
  final VoidCallback? onStart;
  final VoidCallback? onFinish;

  const _ConsultationCard({
    required this.item,
    required this.role,
    required this.onStart,
    required this.onFinish,
  });

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('h:mm a');
    final ticketNumber = item.displayTicketNumberForRole(role);
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 60,
                  height: 60,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Text(
                    ticketNumber,
                    style: const TextStyle(
                      fontSize: 22, 
                      fontWeight: FontWeight.w900,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.purposeCode,
                        style: const TextStyle(
                          fontSize: 17, 
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.access_time_rounded, size: 14, color: Colors.grey.shade400),
                          const SizedBox(width: 4),
                          Text(
                            df.format(item.createdAt.toLocal()),
                            style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    StatusChip(label: item.status.toUpperCase()),
                    if ((item.assignedUserTimeAvailability ?? '').isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.schedule_rounded, size: 12, color: AppTheme.primaryColor),
                          const SizedBox(width: 4),
                          Text(
                            item.assignedUserTimeAvailability!,
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
            
            if ((item.waypointName ?? '').isNotEmpty) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.location_on_outlined, size: 16, color: AppTheme.primaryColor),
                    const SizedBox(width: 8),
                    Text(
                      item.waypointName!,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF475569),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            if ((item.purposeOtherText ?? '').isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                item.purposeOtherText!,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
              ),
            ],
            
            if (onStart != null || onFinish != null) ...[
              const SizedBox(height: 20),
              Row(
                children: [
                  if (onStart != null)
                    Expanded(
                      child: FilledButton(
                        onPressed: onStart,
                        style: FilledButton.styleFrom(
                          backgroundColor: AppTheme.accentColor,
                        ),
                        child: const Text('Start Now'),
                      ),
                    ),
                  if (onStart != null && onFinish != null) const SizedBox(width: 12),
                  if (onFinish != null)
                    Expanded(
                      child: OutlinedButton(
                        onPressed: onFinish,
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          side: BorderSide(color: AppTheme.primaryColor.withValues(alpha: 0.5)),
                          foregroundColor: AppTheme.primaryColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Mark Done'),
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
