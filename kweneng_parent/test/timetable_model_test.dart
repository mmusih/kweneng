import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/models/flutter_models.dart';

void main() {
  test('parent timetable payload parses individualized group lessons', () {
    final timetable = TimetableData.fromJson({
      'template': {'name': 'Main timetable', 'academic_year': '2026'},
      'selected_day_number': 2,
      'days': [
        {
          'day_number': 2,
          'name': 'Tuesday',
          'blocks': [
            {
              'kind': 'lesson',
              'period_name': 'Period 3',
              'start_time': '09:40',
              'end_time': '10:20',
              'duration_minutes': 40,
              'title': 'Computer Studies',
              'teacher': 'Teacher One',
              'group': 'Computer Studies Option',
            },
          ],
        },
      ],
    });

    final lesson = timetable.days.single.blocks.single;
    expect(timetable.isPublished, isTrue);
    expect(lesson.isLesson, isTrue);
    expect(lesson.group, 'Computer Studies Option');
    expect(lesson.teacher, 'Teacher One');
  });
}
