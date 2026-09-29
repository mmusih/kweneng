import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';
import '../widgets/portal_widgets.dart';
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
                await ref.read(dashboardProvider.future);
              },
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                children: [
                  Container(
                    padding: const EdgeInsets.all(22),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppTheme.primary, Color(0xFF405885)],
                      ),
                      borderRadius: BorderRadius.circular(26),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    greeting,
                                    style: const TextStyle(
                                      color: Colors.white70,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    data.user.name,
                                    style: const TextStyle(
                                      fontSize: 25,
                                      fontWeight: FontWeight.w800,
                                      color: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            IconButton(
                              tooltip: 'School updates',
                              onPressed: () => context.push('/updates'),
                              icon: const Icon(
                                Icons.notifications_outlined,
                                color: Colors.white,
                              ),
                            ),
                            IconButton(
                              tooltip: 'Account and more',
                              onPressed: () => context.push('/more'),
                              icon: const Icon(
                                Icons.account_circle_outlined,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Text(
                          [
                            data.currentTerm?.name,
                            data.academicYear?.yearName,
                            if (data.dayLabel.isNotEmpty) data.dayLabel,
                          ].whereType<String>().join(' · '),
                          style: const TextStyle(color: Colors.white70),
                        ),
                        if (data.children.length > 1) ...[
                          const SizedBox(height: 18),
                          ChildSelector(
                            children: data.children,
                            bottomPadding: 0,
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 22),

                  const _HomeShortcuts(),
                  const SizedBox(height: 24),
                  if (child == null)
                    const PortalCard(
                      child: Text(
                        'No children are linked yet. Please contact the school to link your child to this account.',
                      ),
                    )
                  else ...[
                    _ChildSummary(child: child),
                    const SizedBox(height: 20),
                    _Attention(child: child, data: data),
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
    const colours = [AppTheme.primary, Color(0xFF147D78), Color(0xFF8055A2)];
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
                                    color: colours[index % colours.length]
                                        .withValues(alpha: 0.10),
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: Icon(
                                    shortcuts[index].$1,
                                    size: 28,
                                    color: colours[index % colours.length],
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
          InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () =>
                context.push('/children/${child.id}/profile', extra: child),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Row(
                children: [
                  ChildAvatar(name: child.name, photo: child.photo, radius: 34),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          child.name,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 4),
                        Text(child.className ?? 'Class not assigned'),
                        const SizedBox(height: 4),
                        const Text(
                          'View child profile',
                          style: TextStyle(
                            fontSize: 12,
                            color: AppTheme.primary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
            ),
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 14),
            child: Divider(height: 1),
          ),
          Wrap(
            spacing: 14,
            runSpacing: 18,
            children: [
              PortalMetric(
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
                label: 'Attendance · this term',
                value: child.attendanceRate == null
                    ? 'Not recorded'
                    : '${child.attendanceRate!.toStringAsFixed(0)}%',
              ),
              PortalMetric(
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
  final ChildModel child;
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
    final links = <Widget>[
      if (child.isBlocked)
        PortalLink(
          icon: Icons.lock_outline,
          title: 'Results access is restricted',
          subtitle: 'Review fees or contact the school for help.',
          alert: true,
          onTap: () => context.push('/fees'),
        ),
      if ((child.library?.overdue ?? 0) > 0)
        PortalLink(
          icon: Icons.menu_book_outlined,
          title: '${child.library!.overdue} overdue library book(s)',
          subtitle: 'Check titles and return dates.',
          alert: true,
          onTap: () => context.push('/library'),
        ),
      if (!child.profile.complete)
        PortalLink(
          icon: Icons.person_outline,
          title: 'Complete your child’s profile',
          subtitle: 'Check identity and emergency contacts.',
          onTap: () =>
              context.push('/children/${child.id}/profile', extra: child),
        ),
      if (child.homework.unreadCount > 0)
        PortalLink(
          icon: Icons.assignment_outlined,
          title: '${child.homework.unreadCount} new homework update(s)',
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
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: PortalCard(
        padding: const EdgeInsets.only(top: 16, bottom: 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: Text(
                'Needs your attention',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
              ),
            ),
            ...links,
          ],
        ),
      ),
    );
  }
}
