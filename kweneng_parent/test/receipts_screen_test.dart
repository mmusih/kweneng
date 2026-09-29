import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/screens/receipts_screen.dart';

Map<String, dynamic> receiptPage(int page) => {
  'data': [
    {
      'id': page,
      'receipt_number': 'KWE-0000000$page',
      'amount_minor': 285000,
      'paid_on': '2026-09-21',
      'status': page == 1 ? 'confirmed' : 'reversed',
      'items': [
        {
          'category': 'Tuition',
          'description': 'Term fees',
          'student_name': 'Naledi Test',
          'amount_minor': 200000,
        },
        {
          'category': 'Transport',
          'description': 'School bus',
          'student_name': 'Naledi Test',
          'amount_minor': 85000,
        },
      ],
    },
  ],
  'next_page_url': page == 1 ? '/api/parent/receipts?page=2' : null,
};

void main() {
  testWidgets('shows mixed payment receipts and pages through history', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: ReceiptsScreen(loadPage: (page) async => receiptPage(page)),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('P2850.00'), findsOneWidget);
    expect(find.textContaining('School bus'), findsOneWidget);
    expect(find.text('Download receipt PDF'), findsOneWidget);
    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    expect(find.text('KWE-00000002'), findsOneWidget);
    expect(find.text('This payment has been reversed.'), findsOneWidget);
  });

  testWidgets('handles an empty receipt history', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: ReceiptsScreen(
          loadPage: (_) async => {'data': [], 'next_page_url': null},
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('No receipts available yet'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('offers retry after a failed request', (tester) async {
    var calls = 0;
    await tester.pumpWidget(
      MaterialApp(
        home: ReceiptsScreen(
          loadPage: (page) async {
            calls++;
            if (calls == 1) throw Exception('Offline');
            return receiptPage(page);
          },
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Could not load receipts.'), findsOneWidget);
    await tester.tap(find.text('Try again'));
    await tester.pumpAndSettle();
    expect(find.text('KWE-00000001'), findsOneWidget);
  });

  testWidgets('receipt history fits small screens with large text', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(320, 740);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(
      MaterialApp(
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(
            context,
          ).copyWith(textScaler: const TextScaler.linear(2)),
          child: child!,
        ),
        home: ReceiptsScreen(loadPage: (page) async => receiptPage(page)),
      ),
    );
    await tester.pumpAndSettle();
    await tester.drag(find.byType(ListView), const Offset(0, -1000));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });
}
