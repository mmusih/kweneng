import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class AnnouncementsScreen extends ConsumerWidget {
  const AnnouncementsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final announcementsAsync = ref.watch(announcementsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Notices')),
      body: announcementsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.wifi_off, size: 48, color: Colors.grey),
                const SizedBox(height: 12),
                const Text(
                  'Could not load notices.',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: () => ref.invalidate(announcementsProvider),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
        data: (data) => RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(announcementsProvider);
            ref.invalidate(dashboardProvider);
          },
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (data.allCount > 0) ...[
                _UnreadSummary(
                  unreadCount: data.unreadCount,
                  allCount: data.allCount,
                ),
                const SizedBox(height: 14),
              ],
              if (data.urgent.isNotEmpty) ...[
                _SectionHeader(
                  icon: Icons.warning_amber,
                  title: 'Important Notices',
                  color: const Color(0xFFDC2626),
                ),
                const SizedBox(height: 8),
                ...data.urgent.map((a) => _AnnouncementCard(announcement: a)),
                const SizedBox(height: 16),
              ],
              if (data.general.isNotEmpty) ...[
                const _SectionHeader(
                  icon: Icons.campaign,
                  title: 'School Notices',
                  color: AppTheme.primaryLight,
                ),
                const SizedBox(height: 8),
                ...data.general.map((a) => _AnnouncementCard(announcement: a)),
              ],
              if (data.urgent.isEmpty && data.general.isEmpty)
                const _EmptyState(),
            ],
          ),
        ),
      ),
    );
  }
}

class _UnreadSummary extends StatelessWidget {
  final int unreadCount;
  final int allCount;

  const _UnreadSummary({required this.unreadCount, required this.allCount});

  @override
  Widget build(BuildContext context) {
    final text = unreadCount == 0
        ? 'You are up to date. No unread notices.'
        : unreadCount == 1
        ? 'You have 1 unread notice.'
        : 'You have $unreadCount unread notices.';

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: unreadCount == 0
            ? const Color(0xFFECFDF5)
            : const Color(0xFFE8F1FA),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: unreadCount == 0
              ? const Color(0xFFA7F3D0)
              : const Color(0xFFBFDBFE),
        ),
      ),
      child: Row(
        children: [
          Icon(
            unreadCount == 0
                ? Icons.check_circle_outline
                : Icons.markunread_mailbox_outlined,
            color: unreadCount == 0
                ? const Color(0xFF059669)
                : AppTheme.primary,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: unreadCount == 0
                    ? const Color(0xFF065F46)
                    : AppTheme.primary,
                fontSize: 13,
              ),
            ),
          ),
          Text(
            '$allCount total',
            style: const TextStyle(fontSize: 12, color: Colors.grey),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;

  const _SectionHeader({
    required this.icon,
    required this.title,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 6),
        Text(
          title,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _AnnouncementCard extends ConsumerWidget {
  final AnnouncementModel announcement;

  const _AnnouncementCard({required this.announcement});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final color = _typeColor(announcement.typeColor);

    return InkWell(
      onTap: () async {
        await context.push('/announcements/${announcement.id}');

        ref.invalidate(announcementsProvider);
        ref.invalidate(dashboardProvider);
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: announcement.isRead ? Colors.white : const Color(0xFFF8FBFF),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: announcement.isRead
                ? Colors.grey.shade200
                : AppTheme.primaryLight.withValues(alpha: 0.45),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.03),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 4,
              height: 72,
              decoration: BoxDecoration(
                color: color,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        announcement.typeIcon,
                        style: const TextStyle(fontSize: 18),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          announcement.title,
                          style: TextStyle(
                            fontWeight: announcement.isRead
                                ? FontWeight.w600
                                : FontWeight.bold,
                            fontSize: 14,
                            color: announcement.isRead
                                ? Colors.black87
                                : AppTheme.primary,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    children: [
                      _Pill(label: announcement.typeLabel, color: color),
                      if (!announcement.isRead)
                        const _Pill(label: 'Unread', color: AppTheme.primary),
                      if (announcement.hasPendingAcknowledgement)
                        const _Pill(
                          label: 'Needs acknowledgement',
                          color: Color(0xFFEA580C),
                        ),
                      if (announcement.isAcknowledged)
                        const _Pill(
                          label: 'Acknowledged',
                          color: Color(0xFF059669),
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    announcement.message,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 13, color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 4,
                    runSpacing: 4,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Text(
                        'by ${announcement.author}',
                        style: const TextStyle(
                          fontSize: 11,
                          color: Colors.grey,
                        ),
                      ),
                      const Text('·', style: TextStyle(color: Colors.grey)),
                      Text(
                        _formatDate(announcement.displayDate),
                        style: const TextStyle(
                          fontSize: 11,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 4),
            Icon(Icons.chevron_right, color: Colors.grey.shade400),
          ],
        ),
      ),
    );
  }

  Color _typeColor(String colorName) {
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

  String _formatDate(DateTime dt) {
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

class _Pill extends StatelessWidget {
  final String label;
  final Color color;

  const _Pill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    if (label.trim().isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(40),
        child: Column(
          children: [
            Icon(Icons.campaign_outlined, size: 48, color: Colors.grey),
            SizedBox(height: 12),
            Text(
              'No notices at this time.',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }
}
