import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_teacher/models/models.dart';
import 'package:kweneng_teacher/screens/forgot_password_screen.dart';

void main() {
  testWidgets('forgot password screen renders', (tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(home: TeacherForgotPasswordScreen()),
      ),
    );

    expect(find.text('Forgot your password?'), findsOneWidget);
    expect(find.text('Email reset link'), findsOneWidget);
    expect(find.byType(TextFormField), findsOneWidget);
  });

  test('scheme detail payload is parsed for the mobile progress view', () {
    final detail = TeacherSchemeDetail.fromJson({
      'id': 9,
      'title': 'Form 4 Mathematics',
      'class_name': 'Form 4A',
      'subject_name': 'Mathematics',
      'academic_year': '2026',
      'status': 'approved',
      'overall_pct': 35,
      'expected_pct': 40,
      'pacing_status': 'on_track',
      'terms': [
        {
          'term_id': 1,
          'term_name': 'Term 1',
          'weeks': [
            {
              'week': 2,
              'topics': [
                {
                  'id': 12,
                  'title': 'Algebra',
                  'week_number': 2,
                  'status': 'in_progress',
                  'is_behind': false,
                  'subtopics': [
                    {
                      'id': 22,
                      'title': 'Linear equations',
                      'status': 'completed',
                    },
                  ],
                },
              ],
            },
          ],
        },
      ],
    });

    expect(detail.summary.title, 'Form 4 Mathematics');
    expect(detail.terms.single.weeks.single.topics.single.title, 'Algebra');
    expect(
      detail
          .terms
          .single
          .weeks
          .single
          .topics
          .single
          .subtopics
          .single
          .isCompleted,
      isTrue,
    );
  });
}
