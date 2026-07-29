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
              // ── Rich App Bar ─────────────────────────────────────────────
              SliverAppBar(
                expandedHeight: 238,
                pinned: true,
                backgroundColor: AppTheme.primary,
                automaticallyImplyLeading: false,
                // No elevation so the scoop below looks seamless
                elevation: 0,
                scrolledUnderElevation: 0,
                flexibleSpace: FlexibleSpaceBar(
                  collapseMode: CollapseMode.pin,
                  background: _DashboardHeader(data: data, ref: ref),
                ),
              ),

              SliverPadding(
                // Breathing room between the blue dashboard header and the first card.
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 0),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    // ── Important notices ───────────────────────────────
                    if (data.importantAnnouncements.isNotEmpty) ...[
                      _ImportantBanner(
                        announcements: data.importantAnnouncements,
                      ),
                      const SizedBox(height: 16),
                    ],

                    // ── Blocked children notice ──────────────────────────
                    if (data.stats.blockedChildren > 0) ...[
                      _BlockedNotice(count: data.stats.blockedChildren),
                      const SizedBox(height: 16),
                    ],

                    // ── Profile completion prompts ────────────────────
                    if (data.stats.incompleteProfiles > 0) ...[
                      _ProfileCompletionPrompt(children: data.children),
                      const SizedBox(height: 16),
                    ],

                    // ── Full Icon Navigation Grid ────────────────────────
                    _NavigationGrid(homeworkBadge: data.stats.unreadHomework),
                    const SizedBox(height: 20),

                    // ── Upcoming Events ──────────────────────────────────
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

                    // ── Notices ─────────────────────────────────────────
                    if (data.announcements.isNotEmpty) ...[
                      _SectionHeader(
                        title: 'School Notices',
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

                    // ── Children ─────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────────────────────
// Rich Dashboard Header
// ─────────────────────────────────────────────────────────────────────────────

class _DashboardHeader extends ConsumerWidget {
  final DashboardData data;
  final WidgetRef ref;

  const _DashboardHeader({required this.data, required this.ref});

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final unreadMessages = ref.watch(
      messagesProvider.select((async) => async.asData?.value.unreadCount ?? 0),
    );

    // Compute summary stats across all children
    double totalAvg = 0;
    int avgCount = 0;
    double totalAttendance = 0;
    int attCount = 0;
    for (final child in data.children) {
      final avg = child.marks?.endtermAverage ?? child.marks?.midtermAverage;
      if (avg != null) {
        totalAvg += avg;
        avgCount++;
      }
      if (child.attendanceRate != null) {
        totalAttendance += child.attendanceRate!;
        attCount++;
      }
    }
    final avgMarks = avgCount > 0
        ? (totalAvg / avgCount).toStringAsFixed(1)
        : '--';
    final avgAttendance = attCount > 0
        ? '${(totalAttendance / attCount).toStringAsFixed(0)}%'
        : 'No data';
    final attendanceSub = attCount > 0 ? 'this term' : 'register pending';

    return Container(
      // The bottom radius creates the "scoop" curve into the body
      decoration: BoxDecoration(
        color: AppTheme.primary,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(16, 40, 16, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          // Greeting row
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _greeting(),
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                        fontSize: 13,
                        fontWeight: FontWeight.w400,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      data.user.name.split(' ').first,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        height: 1.2,
                      ),
                    ),
                  ],
                ),
              ),
              _AvatarMenu(data: data, ref: ref),
            ],
          ),

          const SizedBox(height: 12),

          // Term pills
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [
              if (data.academicYear != null)
                _HeaderPill(
                  icon: Icons.school_outlined,
                  label: data.academicYear!.yearName,
                ),
              if (data.currentTerm != null) ...[
                _HeaderPill(
                  icon: Icons.calendar_today_outlined,
                  label: data.currentTerm!.name,
                ),
                if (data.currentTerm!.daysLeft > 0)
                  _HeaderPill(
                    icon: Icons.hourglass_bottom_outlined,
                    label: '${data.currentTerm!.daysLeft}d left',
                    highlight: data.currentTerm!.daysLeft <= 14,
                  ),
              ],
              _HeaderPill(
                icon: Icons.people_outline,
                label:
                    '${data.children.length} ${data.children.length == 1 ? 'child' : 'children'}',
              ),
            ],
          ),

          const SizedBox(height: 12),

          // Stats row
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Avg marks',
                  value: avgMarks,
                  sub: 'this term',
                  icon: Icons.bar_chart,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _StatCard(
                  label: 'Attendance',
                  value: avgAttendance,
                  sub: attendanceSub,
                  icon: Icons.check_circle_outline,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _StatCard(
                  label: 'Unread',
                  value: unreadMessages > 0 ? '$unreadMessages' : '0',
                  sub: 'messages',
                  icon: Icons.mail_outline,
                  alert: unreadMessages > 0,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeaderPill extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool highlight;

  const _HeaderPill({
    required this.icon,
    required this.label,
    this.highlight = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: highlight
            ? Colors.orange.withValues(alpha: 0.25)
            : Colors.white.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
        border: highlight
            ? Border.all(color: Colors.orange.withValues(alpha: 0.5), width: 1)
            : null,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 12,
            color: highlight
                ? Colors.orange.shade200
                : Colors.white.withValues(alpha: 0.8),
          ),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              color: highlight
                  ? Colors.orange.shade100
                  : Colors.white.withValues(alpha: 0.85),
              fontSize: 11,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final String sub;
  final IconData icon;
  final bool alert;

  const _StatCard({
    required this.label,
    required this.value,
    required this.sub,
    required this.icon,
    this.alert = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.13),
        borderRadius: BorderRadius.circular(10),
        border: alert
            ? Border.all(color: Colors.orange.withValues(alpha: 0.6), width: 1)
            : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 12, color: Colors.white.withValues(alpha: 0.65)),
              const SizedBox(width: 4),
              Text(
                label,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.65),
                  fontSize: 10,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
              height: 1,
            ),
          ),
          const SizedBox(height: 1),
          Text(
            sub,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.5),
              fontSize: 9,
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Avatar with dropdown menu (logout)
// ─────────────────────────────────────────────────────────────────────────────

class _AvatarMenu extends ConsumerWidget {
  final DashboardData data;
  final WidgetRef ref;

  const _AvatarMenu({required this.data, required this.ref});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return PopupMenuButton<String>(
      onSelected: (value) async {
        if (value == 'logout') {
          await ref.read(authProvider.notifier).logout();
          if (context.mounted) context.go('/login');
        }
      },
      offset: const Offset(0, 52),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      itemBuilder: (_) => [
        PopupMenuItem(
          enabled: false,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                data.user.name,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                  color: Colors.black87,
                ),
              ),
              Text(
                data.user.email,
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ],
          ),
        ),
        const PopupMenuDivider(),
        const PopupMenuItem(
          value: 'logout',
          child: Row(
            children: [
              Icon(Icons.logout, size: 18, color: Colors.redAccent),
              SizedBox(width: 10),
              Text('Log out', style: TextStyle(color: Colors.redAccent)),
            ],
          ),
        ),
      ],
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: AppTheme.primaryLight,
            child: Text(
              data.user.name.substring(0, 1).toUpperCase(),
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ),
          Positioned(
            bottom: 0,
            right: 0,
            child: Container(
              width: 10,
              height: 10,
              decoration: BoxDecoration(
                color: const Color(0xFF10B981),
                shape: BoxShape.circle,
                border: Border.all(color: AppTheme.primary, width: 1.5),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Full Navigation Icon Grid (all 9 destinations)
// ─────────────────────────────────────────────────────────────────────────────

class _NavigationGrid extends ConsumerWidget {
  final int homeworkBadge;
  const _NavigationGrid({required this.homeworkBadge});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final unreadMessages = ref.watch(
      messagesProvider.select((async) => async.asData?.value.unreadCount ?? 0),
    );
    final unreadNotices = ref.watch(
      announcementsProvider.select(
        (async) => async.asData?.value.unreadCount ?? 0,
      ),
    );

    final items = [
      _NavItem(
        icon: Icons.calendar_view_week_outlined,
        label: 'Timetable',
        color: const Color(0xFF0284C7),
        bg: const Color(0xFFE0F2FE),
        route: '/timetable',
      ),
      _NavItem(
        icon: Icons.calendar_month_outlined,
        label: 'Events',
        color: const Color(0xFF7C3AED),
        bg: const Color(0xFFF5F3FF),
        route: '/events',
      ),
      _NavItem(
        icon: Icons.campaign_outlined,
        label: 'Notices',
        color: const Color(0xFFEF4444),
        bg: const Color(0xFFFEF2F2),
        route: '/announcements',
        badge: unreadNotices,
      ),
      _NavItem(
        icon: Icons.bar_chart_outlined,
        label: 'Marks',
        color: const Color(0xFF10B981),
        bg: const Color(0xFFECFDF5),
        route: '/marks',
      ),
      _NavItem(
        icon: Icons.assignment_outlined,
        label: 'Homework',
        color: const Color(0xFF0EA5E9),
        bg: const Color(0xFFE0F2FE),
        route: '/homework',
        badge: homeworkBadge,
      ),
      _NavItem(
        icon: Icons.menu_book_outlined,
        label: 'Library',
        color: const Color(0xFFF59E0B),
        bg: const Color(0xFFFFFBEB),
        route: '/library',
      ),
      _NavItem(
        icon: Icons.mail_outline_rounded,
        label: 'Messages',
        color: const Color(0xFF6366F1),
        bg: const Color(0xFFEEF2FF),
        route: '/messages',
        badge: unreadMessages,
      ),
      _NavItem(
        icon: Icons.account_balance_wallet_outlined,
        label: 'Fees',
        color: const Color(0xFFD97706),
        bg: const Color(0xFFFFFBEB),
        route: '/fees',
      ),
      _NavItem(
        icon: Icons.folder_outlined,
        label: 'Documents',
        color: const Color(0xFF059669),
        bg: const Color(0xFFECFDF5),
        route: '/documents',
      ),
      _NavItem(
        icon: Icons.event_busy_rounded,
        label: 'Absence',
        color: const Color(0xFFDC2626),
        bg: const Color(0xFFFEF2F2),
        route: '/absence-notices',
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        GridView.count(
          padding: EdgeInsets.zero,
          crossAxisCount: 4,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 8,
          mainAxisSpacing: 8,
          childAspectRatio: 0.9,
          children: items.map((item) => _NavIconCard(item: item)).toList(),
        ),
      ],
    );
  }
}

class _NavItem {
  final IconData icon;
  final String label;
  final Color color;
  final Color bg;
  final String route;
  final int badge;

  const _NavItem({
    required this.icon,
    required this.label,
    required this.color,
    required this.bg,
    required this.route,
    this.badge = 0,
  });
}

class _NavIconCard extends StatelessWidget {
  final _NavItem item;
  const _NavIconCard({required this.item});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      elevation: 0,
      child: InkWell(
        onTap: () => context.go(item.route),
        borderRadius: BorderRadius.circular(16),
        splashColor: item.color.withValues(alpha: 0.10),
        highlightColor: item.color.withValues(alpha: 0.06),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE5E7EB), width: 1),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      color: item.bg,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(item.icon, color: item.color, size: 22),
                  ),
                  if (item.badge > 0)
                    Positioned(
                      top: -3,
                      right: -3,
                      child: Container(
                        padding: const EdgeInsets.all(3),
                        decoration: const BoxDecoration(
                          color: Colors.red,
                          shape: BoxShape.circle,
                        ),
                        constraints: const BoxConstraints(
                          minWidth: 17,
                          minHeight: 17,
                        ),
                        child: Text(
                          item.badge > 99 ? '99+' : '${item.badge}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 5),
              Text(
                item.label,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF374151),
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Existing widgets — unchanged
// ─────────────────────────────────────────────────────────────────────────────

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

class _ProfileCompletionPrompt extends StatelessWidget {
  final List<ChildModel> children;

  const _ProfileCompletionPrompt({required this.children});

  @override
  Widget build(BuildContext context) {
    final incomplete = children
        .where((child) => !child.profile.complete)
        .toList();
    if (incomplete.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(
                Icons.health_and_safety_outlined,
                color: Color(0xFFD97706),
                size: 20,
              ),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Complete student emergency information',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Please update nationality, ID/passport or birth certificate details, and emergency contacts.',
            style: TextStyle(color: Colors.grey.shade700, fontSize: 12),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: incomplete.map((child) {
              return OutlinedButton.icon(
                onPressed: () =>
                    context.go('/children/${child.id}/profile', extra: child),
                icon: const Icon(Icons.edit_outlined, size: 16),
                label: Text(child.name),
              );
            }).toList(),
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
      // Today — vivid green
      bg = const Color(0xFF16A34A);
      fg = Colors.white;
      label = 'Today';
    } else if (daysUntil == 1) {
      // Tomorrow — warm amber
      bg = const Color(0xFFF59E0B);
      fg = Colors.white;
      label = 'Tomorrow';
    } else if (daysUntil <= 7) {
      // Within a week — electric blue
      bg = const Color(0xFF2563EB);
      fg = Colors.white;
      label = 'In ${daysUntil}d';
    } else if (daysUntil <= 14) {
      // 8–14 days — purple
      bg = const Color(0xFF7C3AED);
      fg = Colors.white;
      label = 'In ${daysUntil}d';
    } else {
      // Far out — neutral slate
      bg = const Color(0xFFE2E8F0);
      fg = const Color(0xFF475569);
      label = 'In ${daysUntil}d';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: fg,
          letterSpacing: 0.1,
        ),
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
                        ? const Color(0xFFFEE2E2)
                        : const Color(0xFFDCFCE7),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    child.isBlocked ? 'Blocked' : 'Results OK',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: child.isBlocked
                          ? const Color(0xFFDC2626)
                          : const Color(0xFF16A34A),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
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
                    score: child.isBlocked ? null : child.marks?.midtermAverage,
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
                    score: child.isBlocked ? null : child.marks?.endtermAverage,
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
                    : 'No register records yet',
              ),
              _InfoRow(
                'Behaviour',
                '${child.behaviour?.label ?? 'Good'}'
                    '${(child.behaviour?.total ?? 0) > 0 ? ' (${child.behaviour!.total})' : ''}',
              ),
            ],
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
  final double? score; // raw number for colour logic

  const _StatBox({required this.label, required this.value, this.score});

  @override
  Widget build(BuildContext context) {
    // Colour-code by percentage: ≥60 green, <60 red, null/unrecorded neutral
    final Color bg;
    final Color valueColor;

    if (score == null) {
      bg = const Color(0xFFF1F5F9);
      valueColor = const Color(0xFF64748B);
    } else if (score! >= 60) {
      bg = const Color(0xFFDCFCE7);
      valueColor = const Color(0xFF15803D);
    } else {
      bg = const Color(0xFFFEE2E2);
      valueColor = const Color(0xFFDC2626);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: valueColor.withValues(alpha: 0.65),
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: valueColor,
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
