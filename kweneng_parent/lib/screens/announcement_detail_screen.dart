import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class AnnouncementDetailScreen extends ConsumerWidget {
  final int announcementId;
  final AnnouncementModel? initialAnnouncement;

  const AnnouncementDetailScreen({
    super.key,
    required this.announcementId,
    this.initialAnnouncement,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(announcementDetailProvider(announcementId));
    final announcement = async.asData?.value ?? initialAnnouncement;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notice'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: async.when(
        loading: () {
          if (announcement == null) {
            return const Center(child: CircularProgressIndicator());
          }
          return _DetailBody(announcement: announcement, isRefreshing: true);
        },
        error: (error, _) {
          if (announcement != null) {
            return _DetailBody(
              announcement: announcement,
              errorMessage:
                  'Could not update read status. Pull down or reopen later.',
            );
          }

          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.error_outline, size: 48, color: Colors.red),
                  const SizedBox(height: 12),
                  Text(
                    'Could not load this notice.\n$error',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: () => ref.invalidate(
                      announcementDetailProvider(announcementId),
                    ),
                    icon: const Icon(Icons.refresh),
                    label: const Text('Retry'),
                  ),
                ],
              ),
            ),
          );
        },
        data: (loaded) => _DetailBody(announcement: loaded),
      ),
    );
  }
}

class _DetailBody extends ConsumerWidget {
  final AnnouncementModel announcement;
  final bool isRefreshing;
  final String? errorMessage;

  const _DetailBody({
    required this.announcement,
    this.isRefreshing = false,
    this.errorMessage,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final color = _typeColor(announcement.typeColor);

    return Column(
      children: [
        if (isRefreshing) const LinearProgressIndicator(minHeight: 2),
        if (errorMessage != null)
          Container(
            width: double.infinity,
            color: const Color(0xFFFFF7ED),
            padding: const EdgeInsets.all(12),
            child: Text(
              errorMessage!,
              style: const TextStyle(color: Color(0xFF9A3412), fontSize: 12),
            ),
          ),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(18, 18, 18, 32),
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.10),
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      announcement.typeIcon,
                      style: const TextStyle(fontSize: 22),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          announcement.title,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.primary,
                            height: 1.25,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 6,
                          crossAxisAlignment: WrapCrossAlignment.center,
                          children: [
                            _Chip(
                              label: announcement.typeLabel.isNotEmpty
                                  ? announcement.typeLabel
                                  : announcement.type,
                              color: color,
                            ),
                            if (!announcement.isRead)
                              const _Chip(
                                label: 'Unread',
                                color: AppTheme.primary,
                              ),
                            if (announcement.requiresAcknowledgement &&
                                !announcement.isAcknowledged)
                              const _Chip(
                                label: 'Acknowledgement required',
                                color: Color(0xFFEA580C),
                              ),
                            if (announcement.isAcknowledged)
                              const _Chip(
                                label: 'Acknowledged',
                                color: Color(0xFF059669),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  const Icon(
                    Icons.person_outline,
                    size: 15,
                    color: Colors.grey,
                  ),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      'By ${announcement.author}',
                      style: const TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                  ),
                  const Icon(
                    Icons.calendar_today_outlined,
                    size: 14,
                    color: Colors.grey,
                  ),
                  const SizedBox(width: 5),
                  Text(
                    _formatDate(announcement.displayDate),
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Text(
                  announcement.message,
                  style: const TextStyle(
                    fontSize: 15,
                    height: 1.55,
                    color: Colors.black87,
                  ),
                ),
              ),
              const SizedBox(height: 18),
              _ReadStatusBox(announcement: announcement),
              if (announcement.requiresAcknowledgement) ...[
                const SizedBox(height: 14),
                _AcknowledgementBox(announcement: announcement),
              ],
            ],
          ),
        ),
      ],
    );
  }

  static Color _typeColor(String colorName) {
    switch (colorName) {
      case 'red':
        return const Color(0xFFDC2626);
      case 'purple':
        return const Color(0xFF7C3AED);
      case 'blue':
        return const Color(0xFF2563EB);
      case 'green':
        return const Color(0xFF059669);
      case 'orange':
        return const Color(0xFFEA580C);
      default:
        return const Color(0xFF6B7280);
    }
  }

  static String _formatDate(DateTime dt) {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return '${months[dt.month - 1]} ${dt.day}, ${dt.year}';
  }
}

class _ReadStatusBox extends StatelessWidget {
  final AnnouncementModel announcement;

  const _ReadStatusBox({required this.announcement});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFE8F1FA),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.check_circle_outline,
            size: 18,
            color: AppTheme.primary,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              announcement.readAt != null
                  ? 'Marked as read on ${_formatDateTime(announcement.readAt!)}.'
                  : 'This notice is marked as read automatically when opened.',
              style: const TextStyle(fontSize: 12, color: AppTheme.primary),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDateTime(DateTime dt) {
    final hour = dt.hour > 12 ? dt.hour - 12 : (dt.hour == 0 ? 12 : dt.hour);
    final minute = dt.minute.toString().padLeft(2, '0');
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    return '${dt.day}/${dt.month}/${dt.year} at $hour:$minute $period';
  }
}

class _AcknowledgementBox extends ConsumerWidget {
  final AnnouncementModel announcement;

  const _AcknowledgementBox({required this.announcement});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (announcement.isAcknowledged) {
      return Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFFECFDF5),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFA7F3D0)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Icon(Icons.verified_outlined, color: Color(0xFF059669)),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                announcement.acknowledgedAt != null
                    ? 'You acknowledged this notice on ${_formatDateTime(announcement.acknowledgedAt!)}.'
                    : 'You have acknowledged this notice.',
                style: const TextStyle(
                  color: Color(0xFF065F46),
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
            ),
          ],
        ),
      );
    }

    final loading = ref.watch(
      acknowledgeAnnouncementProvider.select(
        (map) => map[announcement.id] ?? false,
      ),
    );

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF7ED),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFFED7AA)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.priority_high_rounded, color: Color(0xFFEA580C)),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'This important notice requires your acknowledgement.',
                  style: TextStyle(
                    color: Color(0xFF9A3412),
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: loading
                  ? null
                  : () async {
                      final ok = await ref
                          .read(acknowledgeAnnouncementProvider.notifier)
                          .acknowledge(announcement.id);

                      if (!context.mounted) return;

                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            ok
                                ? 'Notice acknowledged.'
                                : 'Could not acknowledge notice. Please try again.',
                          ),
                        ),
                      );
                    },
              icon: loading
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.check_circle_outline),
              label: Text(
                loading ? 'Acknowledging...' : 'I acknowledge this notice',
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDateTime(DateTime dt) {
    final hour = dt.hour > 12 ? dt.hour - 12 : (dt.hour == 0 ? 12 : dt.hour);
    final minute = dt.minute.toString().padLeft(2, '0');
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    return '${dt.day}/${dt.month}/${dt.year} at $hour:$minute $period';
  }
}

class _Chip extends StatelessWidget {
  final String label;
  final Color color;

  const _Chip({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
