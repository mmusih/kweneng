import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';
import '../screens/login_screen.dart';
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
import '../screens/absence_notice_screen.dart';
import '../screens/homework_screen.dart';
import '../screens/child_profile_screen.dart';
import '../screens/timetable_screen.dart';

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
        ],
      ),
    ],
  );
});

class MainScaffold extends ConsumerWidget {
  final Widget child;
  const MainScaffold({super.key, required this.child});

  static const homeColor = Color(0xFF2563EB);
  static const eventsColor = Color(0xFF7C3AED);
  static const noticesColor = Color(0xFFEF4444);
  static const marksColor = Color(0xFF10B981);
  static const libraryColor = Color(0xFFF59E0B);
  static const mutedColor = Color(0xFF64748B);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final location = GoRouterState.of(context).matchedLocation;

    final unreadMessages = ref.watch(
      messagesProvider.select((async) => async.asData?.value.unreadCount ?? 0),
    );
    final unreadNotices = ref.watch(
      announcementsProvider.select(
        (async) => async.asData?.value.unreadCount ?? 0,
      ),
    );
    int currentIndex = 0;
    if (location.startsWith('/dashboard')) currentIndex = 0;
    if (location.startsWith('/events')) currentIndex = 1;
    if (location.startsWith('/announcements')) currentIndex = 2;
    if (location.startsWith('/marks')) currentIndex = 3;
    if (location.startsWith('/library')) currentIndex = 4;

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBarTheme(
        data: NavigationBarThemeData(
          backgroundColor: const Color(0xFFF8FAFC),
          indicatorColor: _indicatorColor(currentIndex),
          labelTextStyle: WidgetStateProperty.resolveWith((states) {
            final selected = states.contains(WidgetState.selected);
            return TextStyle(
              fontSize: 12,
              fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
              color: selected ? _selectedColor(currentIndex) : mutedColor,
            );
          }),
        ),
        child: NavigationBar(
          selectedIndex: currentIndex,
          height: 74,
          elevation: 8,
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
          onDestinationSelected: (index) {
            switch (index) {
              case 0:
                context.go('/dashboard');
                break;
              case 1:
                context.go('/events');
                break;
              case 2:
                context.go('/announcements');
                break;
              case 3:
                context.go('/marks');
                break;
              case 4:
                context.go('/library');
                break;
            }
          },
          destinations: [
            NavigationDestination(
              icon: _badgedIcon(
                Icons.home_outlined,
                unreadMessages,
                homeColor.withValues(alpha: 0.55),
              ),
              selectedIcon: _badgedIcon(Icons.home, unreadMessages, homeColor),
              label: 'Home',
            ),
            NavigationDestination(
              icon: Icon(
                Icons.calendar_month_outlined,
                color: eventsColor.withValues(alpha: 0.55),
              ),
              selectedIcon: const Icon(
                Icons.calendar_month,
                color: eventsColor,
              ),
              label: 'Events',
            ),
            NavigationDestination(
              icon: _badgedIcon(
                Icons.campaign_outlined,
                unreadNotices,
                noticesColor.withValues(alpha: 0.55),
              ),
              selectedIcon: _badgedIcon(
                Icons.campaign,
                unreadNotices,
                noticesColor,
              ),
              label: 'Notices',
            ),
            NavigationDestination(
              icon: Icon(
                Icons.bar_chart_outlined,
                color: marksColor.withValues(alpha: 0.55),
              ),
              selectedIcon: const Icon(Icons.bar_chart, color: marksColor),
              label: 'Marks',
            ),
            NavigationDestination(
              icon: Icon(
                Icons.menu_book_outlined,
                color: libraryColor.withValues(alpha: 0.55),
              ),
              selectedIcon: const Icon(Icons.menu_book, color: libraryColor),
              label: 'Library',
            ),
          ],
        ),
      ),
    );
  }

  Color _selectedColor(int index) {
    switch (index) {
      case 1:
        return eventsColor;
      case 2:
        return noticesColor;
      case 3:
        return marksColor;
      case 4:
        return libraryColor;
      case 0:
      default:
        return homeColor;
    }
  }

  Color _indicatorColor(int index) {
    return _selectedColor(index).withValues(alpha: 0.14);
  }

  Widget _badgedIcon(IconData icon, int count, Color color) {
    final iconWidget = Icon(icon, color: color);

    if (count <= 0) return iconWidget;

    return Badge.count(
      count: count > 99 ? 99 : count,
      backgroundColor: noticesColor,
      textColor: Colors.white,
      child: iconWidget,
    );
  }
}
