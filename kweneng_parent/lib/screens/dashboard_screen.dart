import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';
import '../widgets/portal_widgets.dart';
import '../widgets/current_activity_card.dart';
import '../widgets/school_attendance_badge.dart';
import '../widgets/powered_by_footer.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardProvider);
    return Scaffold(
      body: SafeArea(
        child: dashboard.when(
          loading: () => const PortalLoading(),
          error: (_, _) => PortalError(
            title: 'Your home could not be loaded',
            onRetry: () => ref.invalidate(dashboardProvider),
          ),
          data: (data) {
            final child = selectedChild(
              data.children,
              ref.watch(selectedChildProvider),
            );
            final hour = DateTime.now().hour;
            final greeting = hour < 12
                ? 'Good morning'
                : hour < 17
                ? 'Good afternoon'
                : 'Good evening';
            return RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(dashboardProvider);
                ref.invalidate(todayAttendanceProvider);
                if (child != null) ref.invalidate(timetableProvider(child.id));
                await ref.read(dashboardProvider.future);
              },
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                children: [
                  _FamilyHeader(data: data, child: child, greeting: greeting),
                  const SizedBox(height: 16),
                  if (child == null)
                    const PortalCard(
                      child: Text(
                        'No children are linked yet. Please contact the school to link your child to this account.',
                      ),
                    )
                  else ...[
                    CurrentActivityCard(childId: child.id),
                  ],
                  _Attention(child: child, data: data),
                  const SizedBox(height: 18),
                  const _HomeShortcuts(),
                  if (child != null) ...[
                    const SizedBox(height: 24),
                    Text(
                      'At a glance',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 12),
                    _ChildSummary(child: child),
                  ],
                  const SizedBox(height: 26),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          'From your school',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                      ),
                      TextButton(
                        onPressed: () => context.push('/updates'),
                        child: const Text('View all'),
                      ),
                    ],
                  ),
                  if (data.announcements.isEmpty &&
                      data.importantAnnouncements.isEmpty)
                    const PortalCard(
                      child: Text(
                        'You’re up to date. School notices will appear here.',
                      ),
                    )
                  else
                    PortalCard(
                      padding: EdgeInsets.zero,
                      child: Column(
                        children: [
                          ...({
                                ...{
                                  for (final a in data.importantAnnouncements)
                                    a.id: a,
                                },
                                ...{
                                  for (final a in data.announcements) a.id: a,
                                },
                              }.values.toList()..sort(
                                (a, b) =>
                                    b.displayDate.compareTo(a.displayDate),
                              ))
                              .take(2)
                              .map(
                                (a) => PortalLink(
                                  icon: Icons.campaign_outlined,
                                  title: a.title,
                                  subtitle: DateFormat(
                                    'd MMM yyyy',
                                  ).format(a.displayDate),
                                  onTap: () => context.push(
                                    '/announcements/${a.id}',
                                    extra: a,
                                  ),
                                ),
                              ),
                        ],
                      ),
                    ),
                  if (data.upcomingEvents.isNotEmpty) ...[
                    const SizedBox(height: 22),
                    Text(
                      'Coming up',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 12),
                    PortalCard(
                      padding: EdgeInsets.zero,
                      child: Column(
                        children: data.upcomingEvents
                            .take(2)
                            .map(
                              (event) => PortalLink(
                                icon: Icons.calendar_today_outlined,
                                title: event.title,
                                subtitle: DateFormat(
                                  event.isAllDay
                                      ? 'EEE, d MMM · All day'
                                      : 'EEE, d MMM · HH:mm',
                                ).format(event.startDatetime),
                                onTap: () => context.push('/events'),
                              ),
                            )
                            .toList(),
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                  const PoweredByFooter(),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class _FamilyHeader extends ConsumerWidget {
  final DashboardData data;
  final ChildModel? child;
  final String greeting;
  const _FamilyHeader({
    required this.data,
    required this.child,
    required this.greeting,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final index = child == null ? 0 : data.children.indexOf(child!);
    final nextChild = data.children.length > 1
        ? data.children[(index + 1) % data.children.length]
        : null;
    void changeChild(int direction) {
      final next = (index + direction) % data.children.length;
      ref.read(selectedChildProvider.notifier).select(data.children[next].id);
    }

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF253B66), Color(0xFF456DA0)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .16),
            blurRadius: 24,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  greeting,
                  style: const TextStyle(
                    color: Color(0xFFDDEBFF),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              IconButton(
                tooltip: 'School updates',
                onPressed: () => context.push('/updates'),
                icon: const Icon(
                  Icons.notifications_rounded,
                  color: Color(0xFFFFD479),
                ),
              ),
              IconButton(
                tooltip: 'Account and more',
                onPressed: () => context.push('/more'),
                icon: const Icon(
                  Icons.account_circle_rounded,
                  color: Color(0xFFBEEBE8),
                ),
              ),
            ],
          ),
          Text(
            data.user.name,
            style: const TextStyle(
              fontSize: 23,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 18),
          if (child != null) ...[
            InkWell(
              borderRadius: BorderRadius.circular(18),
              onTap: () =>
                  context.push('/children/${child!.id}/profile', extra: child),
              child: Row(
                children: [
                  _ChildPortrait(child: child!, width: 82, height: 104),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          child!.name,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 5),
                        SchoolAttendanceBadge(childId: child!.id),
                        const SizedBox(height: 5),
                        Text(
                          child!.className ?? 'Class not assigned',
                          style: const TextStyle(color: Color(0xFFDDEBFF)),
                        ),
                        const SizedBox(height: 8),
                        const Wrap(
                          crossAxisAlignment: WrapCrossAlignment.center,
                          spacing: 4,
                          children: [
                            Text(
                              'View profile',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFFFFD479),
                              ),
                            ),
                            Icon(
                              Icons.arrow_forward_rounded,
                              size: 14,
                              color: Color(0xFFFFD479),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            if (data.children.length > 1) ...[
              const SizedBox(height: 14),
              Row(
                children: [
                  IconButton(
                    tooltip: 'Previous child',
                    onPressed: () => changeChild(-1),
                    icon: const Icon(
                      Icons.arrow_back_rounded,
                      color: Colors.white,
                    ),
                  ),
                  Expanded(
                    child: Text(
                      'Child ${index + 1} of ${data.children.length}',
                      style: const TextStyle(
                        color: Color(0xFFDDEBFF),
                        fontSize: 12,
                      ),
                    ),
                  ),
                  if (nextChild != null)
                    Tooltip(
                      message: 'Switch to ${nextChild.name}',
                      child: Semantics(
                        button: true,
                        label: 'Next child: ${nextChild.name}',
                        child: InkWell(
                          key: const ValueKey('next-child-preview'),
                          borderRadius: BorderRadius.circular(8),
                          onTap: () => changeChild(1),
                          child: _ChildPortrait(
                            child: nextChild,
                            width: 36,
                            height: 46,
                          ),
                        ),
                      ),
                    ),
                  IconButton(
                    tooltip: 'Next child',
                    onPressed: () => changeChild(1),
                    icon: const Icon(
                      Icons.arrow_forward_rounded,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ],
          ],
          Text(
            [
              data.currentTerm?.name,
              data.academicYear?.yearName,
              if (data.dayLabel.isNotEmpty) data.dayLabel,
            ].whereType<String>().join(' · '),
            style: const TextStyle(color: Color(0xFFDDEBFF), fontSize: 12),
          ),
        ],
      ),
    );
  }
}

class _ChildPortrait extends StatelessWidget {
  final ChildModel child;
  final double width;
  final double height;
  const _ChildPortrait({
    required this.child,
    required this.width,
    required this.height,
  });

  @override
  Widget build(BuildContext context) {
    final fallback = ColoredBox(
      color: AppTheme.accent,
      child: Center(
        child: Text(
          child.name.isEmpty ? 'S' : child.name.characters.first.toUpperCase(),
          style: TextStyle(
            color: AppTheme.primary,
            fontSize: width * .38,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: SizedBox(
        width: width,
        height: height,
        child: child.photo?.isNotEmpty == true
            ? Image.network(
                child.photo!,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => fallback,
                loadingBuilder: (_, image, progress) =>
                    progress == null ? image : fallback,
              )
            : fallback,
      ),
    );
  }
}

class _HomeShortcuts extends StatelessWidget {
  const _HomeShortcuts();

  @override
  Widget build(BuildContext context) {
    const shortcuts = [
      (Icons.bar_chart_rounded, 'Results', '/marks'),
      (Icons.assignment_outlined, 'Homework', '/homework'),
      (Icons.schedule_rounded, 'Timetable', '/timetable'),
      (Icons.fact_check_outlined, 'Attendance', '/attendance'),
      (Icons.account_balance_wallet_outlined, 'Fees', '/fees'),
      (Icons.receipt_long_outlined, 'Receipts', '/receipts'),
      (Icons.chat_bubble_outline_rounded, 'Messages', '/messages'),
      (Icons.campaign_outlined, 'Notices', '/announcements'),
      (Icons.event_outlined, 'Events', '/events'),
      (Icons.folder_open_rounded, 'Documents', '/documents'),
      (Icons.menu_book_outlined, 'Library', '/library'),
      (Icons.emoji_events_outlined, 'Awards', '/awards'),
    ];
    const colours = [
      Color(0xFF3B6FE8),
      Color(0xFFEC8736),
      Color(0xFF8B5BD6),
      Color(0xFF219B82),
      Color(0xFF2997BD),
      Color(0xFFCE6191),
      Color(0xFF5965D9),
      Color(0xFFE16D54),
      Color(0xFFB95AA3),
      Color(0xFFCF9B25),
      Color(0xFF339B9A),
      Color(0xFFD68A22),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Quick access', style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 14),
        LayoutBuilder(
          builder: (context, constraints) {
            final scale = MediaQuery.textScalerOf(context).scale(13) / 13;
            final columns = (constraints.maxWidth / (96 * scale)).floor().clamp(
              1,
              4,
            );
            final width = (constraints.maxWidth - (columns - 1) * 10) / columns;
            return Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                for (var index = 0; index < shortcuts.length; index++)
                  SizedBox(
                    width: width,
                    child: Semantics(
                      button: true,
                      child: PortalCard(
                        padding: EdgeInsets.zero,
                        child: InkWell(
                          borderRadius: BorderRadius.circular(22),
                          onTap: () => context.push(shortcuts[index].$3),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 14,
                            ),
                            child: Column(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    gradient: LinearGradient(
                                      colors: [
                                        colours[index],
                                        colours[index].withValues(alpha: .72),
                                      ],
                                      begin: Alignment.topLeft,
                                      end: Alignment.bottomRight,
                                    ),
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: Icon(
                                    shortcuts[index].$1,
                                    size: 28,
                                    color: Colors.white,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                SizedBox(
                                  height: 34 * scale,
                                  child: Center(
                                    child: Text(
                                      shortcuts[index].$2,
                                      textAlign: TextAlign.center,
                                      style: const TextStyle(
                                        fontSize: 13,
                                        height: 1.2,
                                        fontWeight: FontWeight.w600,
                                        color: AppTheme.primary,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
      ],
    );
  }
}

class _ChildSummary extends StatelessWidget {
  final ChildModel child;
  const _ChildSummary({required this.child});
  @override
  Widget build(BuildContext context) {
    final marks = child.isBlocked ? null : child.marks;
    final average = marks?.endtermAverage ?? marks?.midtermAverage;
    return PortalCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: 14,
            runSpacing: 18,
            children: [
              PortalMetric(
                subdued: child.isBlocked || average == null,
                label: marks?.endtermAverage != null
                    ? 'End-term average'
                    : 'Midterm average',
                value: child.isBlocked
                    ? 'Restricted'
                    : average == null
                    ? '—'
                    : '${average.toStringAsFixed(1)}%',
              ),
              PortalMetric(
                subdued: child.attendanceRate == null,
                label: 'Attendance · this term',
                value: child.attendanceRate == null
                    ? 'Not recorded'
                    : '${child.attendanceRate!.toStringAsFixed(0)}%',
              ),
              PortalMetric(
                subdued: child.behaviour?.label == null,
                label: 'Behaviour',
                value: child.behaviour?.label ?? 'Not recorded',
              ),
            ],
          ),
          if (marks?.performanceLabel != null)
            Padding(
              padding: const EdgeInsets.only(top: 18),
              child: Text(
                marks!.performanceLabel!,
                style: const TextStyle(
                  color: AppTheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _Attention extends StatelessWidget {
  final ChildModel? child;
  final DashboardData data;
  const _Attention({required this.child, required this.data});
  @override
  Widget build(BuildContext context) {
    final notices = {
      ...{for (final a in data.importantAnnouncements) a.id: a},
      ...{
        for (final a in data.announcements.where(
          (a) => a.hasPendingAcknowledgement,
        ))
          a.id: a,
      },
    }.values.where((a) => !a.isRead || a.hasPendingAcknowledgement).toList();
    final links = <PortalLink>[
      if (child?.isBlocked == true)
        PortalLink(
          icon: Icons.lock_outline,
          title: 'Results access is restricted',
          subtitle: 'Review fees or contact the school for help.',
          alert: true,
          onTap: () => context.push('/fees'),
        ),
      if ((child?.library?.overdue ?? 0) > 0)
        PortalLink(
          icon: Icons.menu_book_outlined,
          title: '${child!.library!.overdue} overdue library book(s)',
          subtitle: 'Check titles and return dates.',
          alert: true,
          onTap: () => context.push('/library'),
        ),
      if (child != null && !child!.profile.complete)
        PortalLink(
          icon: Icons.person_outline,
          title: 'Complete your child’s profile',
          subtitle: 'Check identity and emergency contacts.',
          onTap: () =>
              context.push('/children/${child!.id}/profile', extra: child),
        ),
      if ((child?.homework.unreadCount ?? 0) > 0)
        PortalLink(
          icon: Icons.assignment_outlined,
          title: '${child!.homework.unreadCount} new homework update(s)',
          onTap: () => context.push('/homework'),
        ),
      for (final notice in notices)
        PortalLink(
          icon: Icons.campaign_outlined,
          title: notice.title,
          subtitle: notice.hasPendingAcknowledgement
              ? 'Acknowledgement needed'
              : 'Important school notice',
          onTap: () =>
              context.push('/announcements/${notice.id}', extra: notice),
        ),
    ];

    if (links.isEmpty) return const SizedBox.shrink();
    Widget compactLink(PortalLink link) => ListTile(
      dense: true,
      visualDensity: VisualDensity.compact,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14),
      minLeadingWidth: 20,
      leading: Icon(link.icon, size: 20, color: AppTheme.danger),
      title: Text(
        link.title,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
      ),
      trailing: const Icon(Icons.chevron_right, size: 18),
      onTap: link.onTap,
    );
    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: PortalCard(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 14),
              child: Text(
                'Urgent messages',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.danger,
                ),
              ),
            ),
            compactLink(links.first),
            if (links.length > 1)
              ExpansionTile(
                key: ValueKey('urgent-${child?.id}'),
                dense: true,
                visualDensity: VisualDensity.compact,
                tilePadding: const EdgeInsets.symmetric(horizontal: 14),
                title: Text(
                  '${links.length - 1} more updates',
                  style: const TextStyle(fontSize: 12),
                ),
                children: links.skip(1).map(compactLink).toList(),
              ),
          ],
        ),
      ),
    );
  }
}
