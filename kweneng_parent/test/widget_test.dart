import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/screens/forgot_password_screen.dart';

void main() {
  testWidgets('forgot password screen renders', (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(child: MaterialApp(home: ForgotPasswordScreen())),
    );

    expect(find.text('Forgot your password?'), findsOneWidget);
    expect(find.text('Email reset link'), findsOneWidget);
    expect(find.byType(TextFormField), findsOneWidget);
  });
}
