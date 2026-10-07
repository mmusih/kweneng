import 'dart:io';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:kweneng_parent/main.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/core/theme.dart';
import 'package:kweneng_parent/models/flutter_models.dart';
import 'package:kweneng_parent/providers/flutter_providers.dart';
import 'package:kweneng_parent/screens/dashboard_screen.dart';
import 'package:kweneng_parent/screens/login_screen.dart';
import 'package:kweneng_parent/screens/marks_screen.dart';
import 'package:kweneng_parent/widgets/portal_widgets.dart';
import 'package:kweneng_parent/widgets/school_attendance_badge.dart';

class LoggedInAuth extends AuthNotifier {
  @override
  AuthState build() => const AuthState(isAuthenticated: true);
}

class TestAuth extends AuthNotifier {
  @override
  AuthState build() => const AuthState();
}

Map<String, dynamic> student(int id, String name, {bool blocked = false}) => {
  'id': id,
  'name': name,
  'class': 'Form 2A',
  'is_blocked': blocked,
  'profile': {'complete': true, 'profile_complete': true},
  'marks': {'midterm_average': 72, 'endterm_average': 78},
  'attendance_rate': 96,
  'behaviour': {'label': 'Good', 'total': 0},
  'library': {'borrowed': 1, 'overdue': 0, 'books': []},
};
DashboardData fixture({
  bool blocked = false,
  bool multiple = true,
  bool thirdChild = false,
}) => DashboardData.fromJson({
  'user': {'id': 1, 'name': 'Mme Motlotle', 'email': 'parent@example.test'},
  'stats': <String, dynamic>{},
  'day_label': 'Day 3',
  'children': [
    student(1, 'Naledi Motlotle', blocked: blocked),
    if (multiple) student(2, 'Kabelo Motlotle'),
    if (thirdChild) student(3, 'Amogelang Motlotle'),
  ],
  'announcements': [],
  'important_announcements': [],
  'upcoming_events': [],
});
Future<void> render(
  WidgetTester tester,
  Widget screen, {
  double width = 390,
  double scale = 1,
  bool blocked = false,
  bool multiple = true,
  bool thirdChild = false,
}) async {
  tester.view.physicalSize = Size(width, 844);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    ProviderScope(
      key: UniqueKey(),
      overrides: [
        authProvider.overrideWith(TestAuth.new),
        todayAttendanceProvider.overrideWith(
          (ref) async => {
            'checked_at': DateTime.now().toUtc().toIso8601String(),
            'children': [
              for (final id in [1, 2, 3])
                {
                  'student_id': id,
                  'date': DateTime.now()
                      .toUtc()
                      .add(const Duration(hours: 2))
                      .toIso8601String()
                      .split('T')
                      .first,
                  'status': id == 2 ? 'absent' : 'present',
                  'label': id == 2 ? 'Absent' : 'Present today',
                  'school_end': id == 1 ? '15:15' : '13:10',
                },
            ],
          },
        ),
        timetableProvider.overrideWith(
          (ref, id) async => const TimetableData(days: []),
        ),
        dashboardProvider.overrideWith(
          (ref) async => fixture(
            blocked: blocked,
            multiple: multiple,
            thirdChild: thirdChild,
          ),
        ),
        marksProvider.overrideWith(
          (ref) async => {
            'academic_year': {'year_name': '2026'},
            'children': [
              {
                'id': 1,
                'name': 'Naledi Motlotle',
                'class': 'Form 2A',
                'is_blocked': blocked,
                'terms': [
                  {
                    'term_id': 1,
                    'term_name': 'Term 2',
                    'midterm_average': 72,
                    'endterm_average': 78,
                    'term_status': 'open',
                    'subjects': [
                      {
                        'subject': 'Mathematics',
                        'midterm_score': 72,
                        'endterm_score': 78,
                      },
                    ],
                  },
                ],
              },
            ],
          },
        ),
      ],
      child: MaterialApp(
        theme: AppTheme.light(),
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(
            context,
          ).copyWith(textScaler: TextScaler.linear(scale)),
          child: child!,
        ),
        home: RepaintBoundary(key: const ValueKey('preview'), child: screen),
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> screenshot(WidgetTester tester, String name) async {
  final boundary = tester.renderObject<RenderRepaintBoundary>(
    find.byKey(const ValueKey('preview')),
  );
  await tester.runAsync(() async {
    final image = await boundary.toImage(pixelRatio: 1);
    final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
    await Directory('build/ux-previews').create(recursive: true);
    await File(
      'build/ux-previews/$name.png',
    ).writeAsBytes(bytes!.buffer.asUint8List());
  });
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() async {
    const fontPath = String.fromEnvironment('UX_FONT_DIR');
    if (fontPath.isNotEmpty) {
      for (final entry in {
        'Roboto': 'roboto-regular.ttf',
        'MaterialIcons': 'materialicons-regular.otf',
      }.entries) {
        final font = FontLoader(entry.key)
          ..addFont(
            File(
              '$fontPath/${entry.value}',
            ).readAsBytes().then((bytes) => ByteData.sublistView(bytes)),
          );
        await font.load();
      }
    }
  });
  testWidgets(
    'four tabs preserve feature navigation and Back returns to Home',
    (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authProvider.overrideWith(LoggedInAuth.new),
            todayAttendanceProvider.overrideWith(
              (ref) async => {
                'checked_at': DateTime.now().toUtc().toIso8601String(),
                'children': [
                  for (final id in [1, 2, 3])
                    {
                      'student_id': id,
                      'date': DateTime.now()
                          .toUtc()
                          .add(const Duration(hours: 2))
                          .toIso8601String()
                          .split('T')
                          .first,
                      'status': id == 2 ? 'absent' : 'present',
                      'label': id == 2 ? 'Absent' : 'Present today',
                      'school_end': id == 1 ? '15:15' : '13:10',
                    },
                ],
              },
            ),
            timetableProvider.overrideWith(
              (ref, id) async => const TimetableData(days: []),
            ),
            dashboardProvider.overrideWith((ref) async => fixture()),
            announcementsProvider.overrideWith(
              (ref) async => const AnnouncementsData(
                urgent: [],
                general: [],
                total: 0,
                allCount: 0,
              ),
            ),
            messagesProvider.overrideWith(
              (ref) async => const MessagesData(threads: [], unreadCount: 0),
            ),
            libraryProvider.overrideWith(
              (ref) async => {'children': <Map<String, dynamic>>[]},
            ),
          ],
          child: const KwenengApp(),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(NavigationDestination), findsNWidgets(4));
      await tester.scrollUntilVisible(
        find.text('Library'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Library'));
      await tester.pumpAndSettle();
      expect(find.byType(BackButton), findsOneWidget);
      await tester.tap(find.byType(BackButton));
      await tester.pumpAndSettle();
      expect(find.byType(DashboardScreen), findsOneWidget);
      await tester.drag(find.byType(ListView).first, const Offset(0, 1200));
      await tester.pumpAndSettle();
      expect(find.text('Mme Motlotle'), findsOneWidget);
      await tester.tap(find.text('More').last);
      await tester.pumpAndSettle();
      expect(find.text('Your children'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('additional child tabs scroll and remain selectable', (
    tester,
  ) async {
    int? selected;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Center(
            child: SizedBox(
              width: 280,
              child: ChildTabs(
                children: const [
                  ChildTabInfo(id: 1, name: 'Naledi', className: 'Form 4A'),
                  ChildTabInfo(id: 2, name: 'Kabelo', className: 'Form 2B'),
                  ChildTabInfo(id: 3, name: 'Amogelang', className: 'Form 1A'),
                ],
                selectedId: 1,
                onSelected: (id) => selected = id,
              ),
            ),
          ),
        ),
      ),
    );
    await tester.drag(
      find.byType(SingleChildScrollView),
      const Offset(-200, 0),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const ValueKey('child-tab-3')));
    expect(selected, 3);
    expect(tester.takeException(), isNull);
  });
  test('dashboard fixture parses', () {
    expect(fixture().children.length, 2);
  });
  testWidgets(
    'home puts child before school updates and changes selected child',
    (tester) async {
      await render(tester, const DashboardScreen());
      expect(tester.takeException(), isNull);
      expect(find.text('Urgent messages'), findsNothing);
      expect(find.textContaining('All caught up'), findsNothing);
      expect(
        tester.getTopLeft(find.text('Scheduled now')).dy,
        lessThan(tester.getTopLeft(find.text('Quick access')).dy),
      );
      await screenshot(tester, 'home');
      expect(find.byType(DropdownButtonFormField<int>), findsNothing);
      await tester.tap(find.byTooltip('Next child'));
      await tester.pumpAndSettle();
      await tester.scrollUntilVisible(
        find.text('Kabelo Motlotle'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(find.text('Kabelo Motlotle'), findsOneWidget);
      await tester.tap(find.byTooltip('Previous child'));
      await tester.pumpAndSettle();
      expect(find.text('Naledi Motlotle'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'next preview cycles all three children and wraps in both directions',
    (tester) async {
      await render(tester, const DashboardScreen(), thirdChild: true);
      expect(find.text('Child 1 of 3'), findsOneWidget);
      await tester.tap(find.byKey(const ValueKey('next-child-preview')));
      await tester.pumpAndSettle();
      expect(find.text('Kabelo Motlotle'), findsOneWidget);
      await tester.tap(find.byKey(const ValueKey('next-child-preview')));
      await tester.pumpAndSettle();
      expect(find.text('Amogelang Motlotle'), findsOneWidget);
      expect(find.text('Child 3 of 3'), findsOneWidget);
      await tester.tap(find.byTooltip('Next child'));
      await tester.pumpAndSettle();
      expect(find.text('Naledi Motlotle'), findsOneWidget);
      await tester.tap(find.byTooltip('Previous child'));
      await tester.pumpAndSettle();
      expect(find.text('Amogelang Motlotle'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('single child has no preview or switching controls', (
    tester,
  ) async {
    await render(tester, const DashboardScreen(), multiple: false);
    expect(find.byKey(const ValueKey('next-child-preview')), findsNothing);
    expect(find.byTooltip('Next child'), findsNothing);
    expect(find.byTooltip('Previous child'), findsNothing);
    expect(find.text('Urgent messages'), findsNothing);
    expect(tester.takeException(), isNull);
  });
  testWidgets('blocked child never shows academic average', (tester) async {
    await render(
      tester,
      const DashboardScreen(),
      blocked: true,
      multiple: false,
    );
    await tester.scrollUntilVisible(
      find.text('Urgent messages'),
      200,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.scrollUntilVisible(
      find.text('Restricted'),
      200,
      scrollable: find.byType(Scrollable).first,
    );
    expect(find.text('Restricted'), findsOneWidget);

    expect(find.text('78.0%'), findsNothing);
  });
  testWidgets('home and academics fit small phones with large text', (
    tester,
  ) async {
    await render(tester, const DashboardScreen(), width: 320, scale: 1.4);
    expect(tester.takeException(), isNull);
    await tester.drag(find.byType(ListView).first, const Offset(0, -600));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    await screenshot(tester, 'home-small-large-text');
    await render(tester, const MarksScreen(), width: 320, scale: 1.4);
    expect(tester.takeException(), isNull);
  });
  testWidgets('academics shows both averages and subjects', (tester) async {
    await render(tester, const MarksScreen());
    expect(find.text('Midterm average'), findsOneWidget);
    expect(find.text('End-term average'), findsOneWidget);
    expect(find.text('Mathematics'), findsOneWidget);
    expect(tester.takeException(), isNull);
    await screenshot(tester, 'academics');
  });
  testWidgets('login scrolls with keyboard on small phones', (tester) async {
    await render(tester, const LoginScreen(), width: 320, scale: 1.3);
    tester.view.viewInsets = const FakeViewPadding(bottom: 300);
    addTearDown(tester.view.resetViewInsets);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byType(ElevatedButton));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    await render(tester, const LoginScreen());
    tester.view.viewInsets = const FakeViewPadding();
    await tester.pumpAndSettle();
    await screenshot(tester, 'login');
  });
}
