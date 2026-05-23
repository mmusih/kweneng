import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../providers/flutter_providers.dart';
import '../models/flutter_models.dart';
import '../core/theme.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashAsync = ref.watch(dashboardProvider);

    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: dashAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off, size: 48, color: Colors.grey),
              const SizedBox(height: 12),
              const Text('Could not load dashboard'),
              const SizedBox(height: 8),
              ElevatedButton(
                onPressed: () => ref.invalidate(dashboardProvider),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
        data: (data) => RefreshIndicator(
          onRefresh: () async => ref.invalidate(dashboardProvider),
          child: CustomScrollView(
            slivers: [
              // ── App Bar ─────────────────────────────────────
              SliverAppBar(
                expandedHeight: 130,
                pinned: true,
                backgroundColor: AppTheme.primary,
                flexibleSpace: FlexibleSpaceBar(
                  background: Container(
                    color: AppTheme.primary,
                    padding: const EdgeInsets.fromLTRB(16, 60, 16, 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Welcome, ${data.user.name.split(' ').first}!',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 20,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  if (data.currentTerm != null)
                                    Text(
                                      '${data.academicYear?.yearName} · ${data.currentTerm!.name}'
                                      '${data.currentTerm!.daysLeft > 0 ? ' · ${data.currentTerm!.daysLeft}d left' : ''}',
                                      style: TextStyle(
                                        color: Colors.white.withOpacity(0.75),
                                        fontSize: 12,
                                      ),
                                    ),
                                ],
                              ),
                            ),
                            CircleAvatar(
                              backgroundColor: AppTheme.primaryLight,
                              child: Text(
                                data.user.name.substring(0, 1).toUpperCase(),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                actions: [
                  IconButton(
                    icon: const Icon(Icons.logout, color: Colors.white),
                    onPressed: () async {
                      await ref.read(authProvider.notifier).logout();
                      if (context.mounted) context.go('/login');
                    },
                  ),
                ],
              ),

              SliverPadding(
                padding: const EdgeInsets.all(16),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    // ── Important announcements ──────────────
                    if (data.importantAnnouncements.isNotEmpty) ...[
                      _ImportantBanner(
                        announcements: data.importantAnnouncements,
                      ),
                      const SizedBox(height: 16),
                    ],

                    // ── Blocked children notice ──────────────
                    if (data.stats.blockedChildren > 0) ...[
                      _BlockedNotice(count: data.stats.blockedChildren),
                      const SizedBox(height: 16),
                    ],

                    // ── Upcoming Events ──────────────────────
                    if (data.upcomingEvents.isNotEmpty) ...[
                      _SectionHeader(
                        title: 'Upcoming Events',
                        icon: Icons.calendar_month,
                        color: Colors.purple,
                        onTap: () => context.go('/events'),
                      ),
                      const SizedBox(height: 8),
                      ...data.upcomingEvents
                          .take(3)
                          .map((e) => _EventTile(event: e)),
                      if (data.upcomingEvents.length > 3)
                        TextButton(
                          onPressed: () => context.go('/events'),
                          child: Text(
                            '+ ${data.upcomingEvents.length - 3} more events →',
                          ),
                        ),
                      const SizedBox(height: 16),
                    ],

                    // ── Announcements ────────────────────────
                    if (data.announcements.isNotEmpty) ...[
                      _SectionHeader(
                        title: 'School Announcements',
                        icon: Icons.campaign,
                        color: AppTheme.primaryLight,
                        onTap: () => context.go('/announcements'),
                      ),
                      const SizedBox(height: 8),
                      Card(
                        child: Column(
                          children: data.announcements
                              .take(3)
                              .map((a) => _AnnouncementTile(announcement: a))
                              .toList(),
                        ),
                      ),
                      if (data.announcements.length > 3)
                        TextButton(
                          onPressed: () => context.go('/announcements'),
                          child: Text(
                            'View all ${data.announcements.length} →',
                          ),
                        ),
                      const SizedBox(height: 16),
                    ],

                    // ── Children ─────────────────────────────
                    _SectionHeader(
                      title: 'Your Children',
                      icon: Icons.people,
                      color: AppTheme.primary,
                    ),
                    const SizedBox(height: 8),
                    ...data.children.map((c) => _ChildCard(child: c)),

                    const SizedBox(height: 80),
                  ]),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// Widgets
// ─────────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  final String title;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;

  const _SectionHeader({
    required this.title,
    required this.icon,
    required this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: 6),
        Text(
          title,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
        ),
        const Spacer(),
        if (onTap != null)
          GestureDetector(
            onTap: onTap,
            child: Text(
              'View all →',
              style: TextStyle(fontSize: 12, color: color),
            ),
          ),
      ],
    );
  }
}

class _ImportantBanner extends StatelessWidget {
  final List<AnnouncementModel> announcements;
  const _ImportantBanner({required this.announcements});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFFEF2F2), Color(0xFFFFF7ED)],
        ),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFFECACA)),
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.warning_amber, color: Color(0xFFDC2626), size: 18),
              SizedBox(width: 6),
              Text(
                'IMPORTANT NOTICES',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF991B1B),
                  fontSize: 12,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ...announcements.map(
            (a) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: const Border(
                    left: BorderSide(color: Color(0xFFDC2626), width: 3),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${a.typeIcon} ${a.title}',
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      a.message,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _BlockedNotice extends StatelessWidget {
  final int count;
  const _BlockedNotice({required this.count});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFCEBEB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFF7C1C1)),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning, color: Color(0xFFA32D2D), size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              count == 1
                  ? 'One student has restricted results due to an outstanding balance.'
                  : '$count students have restricted results due to outstanding balances.',
              style: const TextStyle(color: Color(0xFFA32D2D), fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}

class _EventTile extends StatelessWidget {
  final EventModel event;
  const _EventTile({required this.event});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            // Date badge
            Column(
              children: [
                Text(
                  _monthShort(event.startDatetime.month),
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: Colors.purple,
                  ),
                ),
                Text(
                  event.startDatetime.day.toString().padLeft(2, '0'),
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.primary,
                  ),
                ),
              ],
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    event.title,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    event.isAllDay
                        ? 'All day · ${event.typeLabel}'
                        : '${_formatTime(event.startDatetime)} · ${event.typeLabel}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                  ),
                ],
              ),
            ),
            _DaysChip(daysUntil: event.daysUntil),
          ],
        ),
      ),
    );
  }

  String _monthShort(int month) {
    const months = [
      'JAN',
      'FEB',
      'MAR',
      'APR',
      'MAY',
      'JUN',
      'JUL',
      'AUG',
      'SEP',
      'OCT',
      'NOV',
      'DEC',
    ];
    return months[month - 1];
  }

  String _formatTime(DateTime dt) {
    final h = dt.hour > 12 ? dt.hour - 12 : (dt.hour == 0 ? 12 : dt.hour);
    final m = dt.minute.toString().padLeft(2, '0');
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    return '$h:$m $period';
  }
}

class _DaysChip extends StatelessWidget {
  final int daysUntil;
  const _DaysChip({required this.daysUntil});

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    String label;
    if (daysUntil == 0) {
      bg = const Color(0xFFDCFCE7);
      fg = const Color(0xFF166534);
      label = 'Today';
    } else if (daysUntil == 1) {
      bg = const Color(0xFFFEF9C3);
      fg = const Color(0xFF854D0E);
      label = 'Tomorrow';
    } else if (daysUntil <= 7) {
      bg = const Color(0xFFDBEAFE);
      fg = const Color(0xFF1E40AF);
      label = 'In ${daysUntil}d';
    } else {
      bg = const Color(0xFFF3F4F6);
      fg = const Color(0xFF374151);
      label = 'In ${daysUntil}d';
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: fg),
      ),
    );
  }
}

class _AnnouncementTile extends StatelessWidget {
  final AnnouncementModel announcement;
  const _AnnouncementTile({required this.announcement});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Text(
        announcement.typeIcon,
        style: const TextStyle(fontSize: 20),
      ),
      title: Text(
        announcement.title,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
      ),
      subtitle: Text(
        announcement.message,
        style: const TextStyle(fontSize: 12),
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
      dense: true,
    );
  }
}

class _ChildCard extends StatelessWidget {
  final ChildModel child;
  const _ChildCard({required this.child});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          children: [
            // Header
            Row(
              children: [
                _avatar(),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        child.name,
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 15,
                        ),
                      ),
                      Text(
                        '${child.admissionNo} · ${child.className ?? 'N/A'}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: child.isBlocked
                        ? const Color(0xFFFCEBEB)
                        : const Color(0xFFD0E4F5),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    child.isBlocked ? 'Blocked' : 'Results OK',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: child.isBlocked
                          ? const Color(0xFFA32D2D)
                          : AppTheme.primary,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Marks averages
            Row(
              children: [
                Expanded(
                  child: _StatBox(
                    label: 'Midterm avg',
                    value: child.isBlocked
                        ? 'Restricted'
                        : child.marks?.midtermAverage != null
                        ? child.marks!.midtermAverage!.toStringAsFixed(1)
                        : 'Not recorded',
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _StatBox(
                    label: 'Endterm avg',
                    value: child.isBlocked
                        ? 'Restricted'
                        : child.marks?.endtermAverage != null
                        ? child.marks!.endtermAverage!.toStringAsFixed(1)
                        : 'Not recorded',
                  ),
                ),
              ],
            ),

            if (!child.isBlocked) ...[
              const SizedBox(height: 10),
              _InfoRow(
                'Position',
                child.marks?.endtermPosition?.display ??
                    child.marks?.midtermPosition?.display ??
                    'Not available yet',
              ),
              _InfoRow('Trend', child.marks?.trend ?? 'Not enough data'),
              _InfoRow(
                'Attendance',
                child.attendanceRate != null
                    ? '${child.attendanceRate!.toStringAsFixed(1)}%'
                    : 'Not recorded',
              ),
              _InfoRow(
                'Behaviour',
                '${child.behaviour?.label ?? 'Good'}'
                    '${(child.behaviour?.total ?? 0) > 0 ? ' (${child.behaviour!.total})' : ''}',
              ),
            ],

            // Library
            const Divider(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'LIBRARY',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: Colors.grey,
                    letterSpacing: 0.5,
                  ),
                ),
                Text(
                  '${child.library?.borrowed ?? 0} borrowed'
                  '${(child.library?.overdue ?? 0) > 0 ? ' · ${child.library!.overdue} overdue' : ''}',
                  style: TextStyle(
                    fontSize: 11,
                    color: (child.library?.overdue ?? 0) > 0
                        ? AppTheme.danger
                        : Colors.grey,
                    fontWeight: (child.library?.overdue ?? 0) > 0
                        ? FontWeight.w600
                        : FontWeight.normal,
                  ),
                ),
              ],
            ),
            if (child.library != null && child.library!.books.isNotEmpty) ...[
              const SizedBox(height: 6),
              ...child.library!.books.map(
                (b) => Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Row(
                    children: [
                      Icon(
                        Icons.menu_book,
                        size: 14,
                        color: b.overdue
                            ? AppTheme.danger
                            : Colors.blue.shade200,
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          b.title,
                          style: const TextStyle(fontSize: 12),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        b.overdue ? 'Overdue' : 'Due ${b.dueAt ?? ''}',
                        style: TextStyle(
                          fontSize: 11,
                          color: b.overdue ? AppTheme.danger : Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ] else
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  'No books currently borrowed.',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade400),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _avatar() {
    if (child.photo != null) {
      return CircleAvatar(
        radius: 22,
        backgroundImage: CachedNetworkImageProvider(child.photo!),
      );
    }
    return CircleAvatar(
      radius: 22,
      backgroundColor: AppTheme.accent,
      child: Text(
        child.name.substring(0, 1).toUpperCase(),
        style: const TextStyle(
          color: AppTheme.primary,
          fontWeight: FontWeight.bold,
          fontSize: 16,
        ),
      ),
    );
  }
}

class _StatBox extends StatelessWidget {
  final String label;
  final String value;
  const _StatBox({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFE8F1FA),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: AppTheme.primary,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
          ),
          Text(
            value,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}
