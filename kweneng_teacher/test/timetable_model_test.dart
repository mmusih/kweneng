import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_teacher/models/models.dart';

void main() {
  test('teacher timetable payload parses lessons and extended duration', () {
    final timetable = TimetableData.fromJson({
      'template': {'name': 'Main timetable', 'academic_year': '2026'},
      'selected_day_number': 1,
      'days': [
        {
          'day_number': 1,
          'name': 'Monday',
          'blocks': [
            {
              'kind': 'lesson',
              'period_name': 'Period 1',
              'start_time': '08:00',
              'end_time': '09:20',
              'duration_minutes': 80,
              'title': 'Mathematics',
              'class': 'Form 1A',
              'room': 'Room 1',
            },
          ],
        },
      ],
    });

    expect(timetable.isPublished, isTrue);
    expect(timetable.selectedDayNumber, 1);
    expect(timetable.days.single.blocks.single.durationMinutes, 80);
    expect(timetable.days.single.blocks.single.className, 'Form 1A');
  });
}
