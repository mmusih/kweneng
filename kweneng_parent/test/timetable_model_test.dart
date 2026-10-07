import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/models/flutter_models.dart';

void main() {
  test('day and afternoon schedules retain their independent cycles', () {
    Map<String, dynamic> schedule(String label, int day, String subject) => {
      'template': {'name': '$label timetable', 'schedule_label': label},
      'selected_day_number': day,
      'days': [
        {
          'day_number': day,
          'name': 'Day $day',
          'blocks': [
            {'kind': 'lesson', 'title': subject},
          ],
        },
      ],
    };
    final day = schedule('Day', 6, 'Mathematics');
    final afternoon = schedule('Afternoon / study', 1, 'Study');
    final timetable = TimetableData.fromJson({
      ...day,
      'schedules': [day, afternoon],
    });

    expect(timetable.isPublished, isTrue);
    expect(timetable.schedules, hasLength(2));
    expect(timetable.schedules[0].scheduleLabel, 'Day');
    expect(timetable.schedules[0].selectedDayNumber, 6);
    expect(timetable.schedules[1].scheduleLabel, 'Afternoon / study');
    expect(timetable.schedules[1].selectedDayNumber, 1);
    expect(timetable.schedules[1].days.single.blocks.single.title, 'Study');
  });

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
