import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';
import '../screens/login_screen.dart';
import '../screens/portal_screens.dart';
import 'theme.dart';
import '../screens/forgot_password_screen.dart';
import '../screens/change_password_screen.dart';
import '../screens/parent_code_screen.dart';
import '../screens/parent_registration_screen.dart';
import '../screens/dashboard_screen.dart';
import '../screens/events_screen.dart';
import '../screens/announcements_screen.dart';
import '../screens/announcement_detail_screen.dart';
import '../screens/marks_screen.dart';
import '../screens/library_screen.dart';
import '../screens/messages_screen.dart';
import '../screens/documents_screen.dart';
import '../screens/fees_screen.dart';
import '../screens/receipts_screen.dart';
import '../screens/absence_notice_screen.dart';
import '../screens/homework_screen.dart';
import '../screens/child_profile_screen.dart';
import '../screens/timetable_screen.dart';
import '../screens/awards_screen.dart';
import '../screens/academic_record_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/login',
    redirect: (context, state) {
      final auth = ref.read(authProvider);
      final isAuth = auth.isAuthenticated;
      final mustChangePassword = auth.mustChangePassword;
      final loc = state.matchedLocation;

      final isLoginRoute = loc == '/login';
      final isForgotPasswordRoute = loc == '/forgot-password';
      final isChangePasswordRoute = loc == '/change-password';
      final isRegistrationRoute = loc.startsWith('/parent-register');

      if (loc == '/') {
        if (!isAuth) return '/login';
        return mustChangePassword ? '/change-password' : '/dashboard';
      }

      if (!isAuth &&
          !isLoginRoute &&
          !isForgotPasswordRoute &&
          !isRegistrationRoute) {
        return '/login';
      }

      if (isAuth && mustChangePassword && !isChangePasswordRoute) {
        return '/change-password';
      }

      if (isAuth && !mustChangePassword && isChangePasswordRoute) {
        return '/dashboard';
      }

      if (isAuth &&
          (isLoginRoute || isForgotPasswordRoute || isRegistrationRoute)) {
        return mustChangePassword ? '/change-password' : '/dashboard';
      }

      return null;
    },
    routes: [
      GoRoute(path: '/', redirect: (_, _) => '/login'),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: '/forgot-password',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/change-password',
        builder: (context, state) => const ChangePasswordScreen(),
      ),
      GoRoute(
        path: '/parent-register',
        builder: (context, state) => const ParentCodeScreen(),
      ),
      GoRoute(
        path: '/parent-register/details',
        builder: (context, state) {
          final verification = state.extra is ParentCodeVerification
              ? state.extra as ParentCodeVerification
              : null;
          final inviteCode = state.uri.queryParameters['code'];
          return ParentRegistrationScreen(
            verification: verification,
            inviteCode: inviteCode,
          );
        },
      ),
      ShellRoute(
        builder: (context, state, child) => MainScaffold(child: child),
        routes: [
          GoRoute(
            path: '/updates',
            builder: (context, state) => const UpdatesScreen(),
          ),
          GoRoute(
            path: '/more',
            builder: (context, state) => const MoreScreen(),
          ),
          GoRoute(
            path: '/attendance',
            builder: (context, state) => const AttendanceScreen(),
          ),
          GoRoute(
            path: '/dashboard',
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/timetable',
            builder: (context, state) => const TimetableScreen(),
          ),
          GoRoute(
            path: '/events',
            builder: (context, state) => const EventsScreen(),
          ),
          GoRoute(
            path: '/announcements',
            builder: (context, state) => const AnnouncementsScreen(),
          ),
          GoRoute(
            path: '/announcements/:id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              final initial = state.extra is AnnouncementModel
                  ? state.extra as AnnouncementModel
                  : null;
              return AnnouncementDetailScreen(
                announcementId: id,
                initialAnnouncement: initial,
              );
            },
          ),
          GoRoute(
            path: '/marks',
            builder: (context, state) => const MarksScreen(),
          ),
          GoRoute(
            path: '/awards',
            builder: (context, state) => const AwardsScreen(),
          ),
          GoRoute(
            path: '/children/:studentId/academic-record',
            builder: (context, state) => AcademicRecordScreen(
              studentId:
                  int.tryParse(state.pathParameters['studentId'] ?? '') ?? 0,
            ),
          ),
          GoRoute(
            path: '/library',
            builder: (context, state) => const LibraryScreen(),
          ),
          GoRoute(
            path: '/messages',
            builder: (context, state) => const MessagesScreen(),
          ),
          GoRoute(
            path: '/absence-notices',
            builder: (context, state) => const AbsenceNoticeScreen(),
          ),
          GoRoute(
            path: '/homework',
            builder: (context, state) => const HomeworkScreen(),
          ),
          GoRoute(
            path: '/children/:studentId/profile',
            builder: (context, state) {
              final id =
                  int.tryParse(state.pathParameters['studentId'] ?? '') ?? 0;
              final child = state.extra is ChildModel
                  ? state.extra as ChildModel
                  : null;
              return ChildProfileScreen(studentId: id, initialChild: child);
            },
          ),
          GoRoute(
            path: '/documents',
            builder: (context, state) => const DocumentsScreen(),
          ),
          GoRoute(
            path: '/fees',
            builder: (context, state) => const FeesScreen(),
          ),
          GoRoute(
            path: '/receipts',
            builder: (context, state) => const ReceiptsScreen(),
          ),
        ],
      ),
    ],
  );
});

class MainScaffold extends ConsumerWidget {
  final Widget child;
  const MainScaffold({super.key, required this.child});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final location = GoRouterState.of(context).matchedLocation;
    final unreadNotices = ref.watch(
      announcementsProvider.select(
        (data) => data.asData?.value.unreadCount ?? 0,
      ),
    );
    final unreadMessages = ref.watch(
      messagesProvider.select((data) => data.asData?.value.unreadCount ?? 0),
    );
    final index = location == '/dashboard'
        ? 0
        : location == '/marks' ||
              location.contains('/academic-record') ||
              location == '/homework' ||
              location == '/timetable' ||
              location == '/awards'
        ? 1
        : location == '/updates' ||
              location.startsWith('/announcements') ||
              location == '/events' ||
              location == '/messages'
        ? 2
        : 3;
    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        backgroundColor: Colors.white,
        indicatorColor: AppTheme.accent.withValues(alpha: .65),
        elevation: 0,
        height: 72,
        onDestinationSelected: (value) =>
            context.go(['/dashboard', '/marks', '/updates', '/more'][value]),
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home_rounded),
            label: 'Home',
          ),
          const NavigationDestination(
            icon: Icon(Icons.bar_chart_outlined),
            selectedIcon: Icon(Icons.bar_chart_rounded),
            label: 'Academics',
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: unreadNotices + unreadMessages > 0,
              label: Text('${unreadNotices + unreadMessages}'),
              child: const Icon(Icons.notifications_outlined),
            ),
            selectedIcon: const Icon(Icons.notifications_rounded),
            label: 'Updates',
          ),
          const NavigationDestination(
            icon: Icon(Icons.more_horiz),
            label: 'More',
          ),
        ],
      ),
    );
  }
}
