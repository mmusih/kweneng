import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/theme.dart';
import 'providers/app_providers.dart';
import 'screens/dashboard_screen.dart';
import 'screens/login_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const ProviderScope(child: KwenengTeacherApp()));
}

class KwenengTeacherApp extends ConsumerWidget {
  const KwenengTeacherApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Kweneng Teacher',
      theme: AppTheme.light(),
      home: auth.isLoading
          ? const _StartupScreen()
          : auth.isAuthenticated
          ? const TeacherDashboardScreen()
          : const TeacherLoginScreen(),
    );
  }
}

class _StartupScreen extends StatelessWidget {
  const _StartupScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
