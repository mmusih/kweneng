import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/intl.dart';
import 'package:kweneng_parent/widgets/school_attendance_badge.dart';

void main() {
  Future<void> render(
    WidgetTester tester, {
    String status = 'present',
    String end = '15:15',
    bool stale = false,
    bool error = false,
    int id = 1,
  }) async {
    final now = DateTime.now().toUtc();
    final schoolTime = now.add(const Duration(hours: 2));
    await tester.pumpWidget(
      ProviderScope(
        key: UniqueKey(),
        overrides: [
          todayAttendanceProvider.overrideWith((ref) async {
            if (error) throw Exception('Offline');
            return {
              'checked_at':
                  (stale ? now.subtract(const Duration(days: 1)) : now)
                      .toIso8601String(),
              'children': [
                {
                  'student_id': 1,
                  'date': DateFormat('yyyy-MM-dd').format(schoolTime),
                  'status': status,
                  'label': status == 'absent'
                      ? 'Absent'
                      : 'Attendance not marked yet',
                  'school_end': end,
                },
              ],
            };
          }),
        ],
        child: MaterialApp(
          home: Scaffold(
            body: SizedBox(
              width: 170,
              child: SchoolAttendanceBadge(childId: id),
            ),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
  }

  testWidgets('present child shows the correct school hours', (tester) async {
    await render(tester);
    expect(find.textContaining('07:30–15:15'), findsOneWidget);
    await render(tester, end: '13:10');
    expect(find.textContaining('07:30–13:10'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets('absent is red and an unmarked register stays neutral', (
    tester,
  ) async {
    await render(tester, status: 'absent');
    expect(find.text('Absent'), findsOneWidget);
    expect(
      tester.widget<Icon>(find.byIcon(Icons.circle)).color,
      const Color(0xFFFF7777),
    );
    await render(tester, status: 'unmarked');
    expect(find.text('Attendance not marked yet'), findsOneWidget);
    expect(
      tester.widget<Icon>(find.byIcon(Icons.circle)).color,
      const Color(0xFFCDD9EA),
    );
  });
  testWidgets('offline, stale, and a different child never show in school', (
    tester,
  ) async {
    for (final mode in ['offline', 'stale', 'other']) {
      await render(
        tester,
        error: mode == 'offline',
        stale: mode == 'stale',
        id: mode == 'other' ? 2 : 1,
      );
      expect(find.text('Attendance unavailable'), findsOneWidget);
      expect(find.textContaining('In school'), findsNothing);
    }
  });
}
