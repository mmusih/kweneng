import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/flutter_providers.dart';
import '../widgets/portal_widgets.dart';

class UpdatesScreen extends ConsumerWidget {
  const UpdatesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
    appBar: AppBar(title: const Text('School updates')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        const Text(
          'Stay connected',
          style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 8),
        const Text(
          'School news, upcoming dates and conversations in one place.',
        ),
        const SizedBox(height: 24),
        PortalCard(
          padding: EdgeInsets.zero,
          child: Column(
            children: [
              PortalLink(
                icon: Icons.campaign_outlined,
                title: 'Notices',
                subtitle: 'Announcements and acknowledgements',
                onTap: () => context.push('/announcements'),
              ),
              const Divider(height: 1),
              PortalLink(
                icon: Icons.calendar_month_outlined,
                title: 'School calendar',
                subtitle: 'Events and important dates',
                onTap: () => context.push('/events'),
              ),
              const Divider(height: 1),
              PortalLink(
                icon: Icons.chat_bubble_outline,
                title: 'Messages',
                subtitle: 'Conversations with the school',
                onTap: () => context.push('/messages'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        ref
            .watch(dashboardProvider)
            .when(
              loading: () => const LinearProgressIndicator(),
              error: (_, _) => TextButton(
                onPressed: () => ref.invalidate(dashboardProvider),
                child: const Text('Reload latest notices'),
              ),
              data: (data) => Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Latest notices',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 12),
                  if (data.announcements.isEmpty &&
                      data.importantAnnouncements.isEmpty)
                    const PortalCard(child: Text('No school notices yet.')),
                  ...({
                        ...{
                          for (final a in data.importantAnnouncements) a.id: a,
                        },
                        ...{for (final a in data.announcements) a.id: a},
                      }.values.toList()..sort(
                        (a, b) => b.displayDate.compareTo(a.displayDate),
                      ))
                      .take(5)
                      .map(
                        (a) => Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: PortalCard(
                            padding: EdgeInsets.zero,
                            child: PortalLink(
                              icon: Icons.campaign_outlined,
                              title: a.title,
                              subtitle: a.hasPendingAcknowledgement
                                  ? 'Acknowledgement needed'
                                  : a.typeLabel,
                              onTap: () => context.push(
                                '/announcements/${a.id}',
                                extra: a,
                              ),
                            ),
                          ),
                        ),
                      ),
                ],
              ),
            ),
      ],
    ),
  );
}

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;
    return Scaffold(
      appBar: AppBar(title: const Text('More')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          PortalCard(
            child: Row(
              children: [
                ChildAvatar(name: user?.name ?? 'Parent'),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.name ?? 'Parent account',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      Text(user?.email ?? ''),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Text('Your children', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 12),
          ref
              .watch(dashboardProvider)
              .when(
                loading: () => const LinearProgressIndicator(),
                error: (_, _) => TextButton(
                  onPressed: () => ref.invalidate(dashboardProvider),
                  child: const Text('Reload child profiles'),
                ),
                data: (dashboard) => PortalCard(
                  padding: EdgeInsets.zero,
                  child: Column(
                    children: [
                      if (dashboard.children.isEmpty)
                        const Padding(
                          padding: EdgeInsets.all(20),
                          child: Text('Contact the school to link your child.'),
                        ),
                      for (final child in dashboard.children)
                        PortalLink(
                          icon: Icons.person_outline,
                          title: child.name,
                          subtitle: 'Photo, identity and emergency contacts',
                          onTap: () => context.push(
                            '/children/${child.id}/profile',
                            extra: child,
                          ),
                        ),
                    ],
                  ),
                ),
              ),
          const SizedBox(height: 24),
          Text(
            'School services',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 12),
          PortalCard(
            padding: EdgeInsets.zero,
            child: Column(
              children: [
                for (final link in [
                  (Icons.menu_book_outlined, 'Library', '/library'),
                  (
                    Icons.fact_check_outlined,
                    'Attendance & behaviour',
                    '/attendance',
                  ),
                  (
                    Icons.event_busy_outlined,
                    'Report an absence',
                    '/absence-notices',
                  ),
                  (Icons.assignment_outlined, 'Homework', '/homework'),
                  (Icons.schedule_outlined, 'Timetable', '/timetable'),
                  (
                    Icons.emoji_events_outlined,
                    'Awards & leadership',
                    '/awards',
                  ),
                  (
                    Icons.account_balance_wallet_outlined,
                    'School fees',
                    '/fees',
                  ),
                  (Icons.folder_outlined, 'Documents', '/documents'),
                  (Icons.receipt_long_outlined, 'Payments & receipts', '/receipts'),
                  (Icons.chat_bubble_outline, 'Messages', '/messages'),
                ])
                  PortalLink(
                    icon: link.$1,
                    title: link.$2,
                    onTap: () => context.push(link.$3),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          OutlinedButton.icon(
            onPressed: () async {
              final signOut = await showDialog<bool>(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Sign out?'),
                  content: const Text(
                    'You can sign back in with your school account.',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Stay signed in'),
                    ),
                    FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Sign out'),
                    ),
                  ],
                ),
              );
              if (signOut == true) {
                await ref.read(authProvider.notifier).logout();
                if (context.mounted) context.go('/login');
              }
            },
            icon: const Icon(Icons.logout),
            label: const Text('Sign out'),
          ),
        ],
      ),
    );
  }
}

class AttendanceScreen extends ConsumerWidget {
  const AttendanceScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
    appBar: AppBar(title: const Text('Attendance & behaviour')),
    body: ref
        .watch(dashboardProvider)
        .when(
          loading: () => const PortalLoading(),
          error: (_, _) => PortalError(
            title: 'Attendance could not be loaded',
            onRetry: () => ref.invalidate(dashboardProvider),
          ),
          data: (data) {
            final child = selectedChild(
              data.children,
              ref.watch(selectedChildProvider),
            );
            return ListView(
              padding: const EdgeInsets.all(20),
              children: [
                ChildSelector(children: data.children),
                if (child == null)
                  const PortalCard(
                    child: Text(
                      'No linked children. Please contact the school.',
                    ),
                  )
                else ...[
                  PortalCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            ChildAvatar(name: child.name, photo: child.photo),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Text(
                                child.name,
                                style: Theme.of(context).textTheme.titleLarge,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 20),
                        Text(
                          [
                            data.currentTerm?.name,
                            data.academicYear?.yearName,
                          ].whereType<String>().join(' · '),
                        ),
                        const SizedBox(height: 16),
                        PortalMetric(
                          label: 'Recorded attendance',
                          value: child.attendanceRate == null
                              ? 'Not recorded'
                              : '${child.attendanceRate!.toStringAsFixed(1)}%',
                        ),
                        const SizedBox(height: 16),
                        const Text(
                          'This is the school’s recorded term summary. Contact the school if an entry needs checking.',
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  PortalCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Behaviour',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 12),
                        Text(child.behaviour?.label ?? 'No summary recorded'),
                        if (child.behaviour != null)
                          Text(
                            '${child.behaviour!.total} recorded incident(s)',
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  FilledButton.icon(
                    onPressed: () => context.push('/absence-notices'),
                    icon: const Icon(Icons.event_busy_outlined),
                    label: const Text('Report or review an absence'),
                  ),
                ],
              ],
            );
          },
        ),
  );
}
