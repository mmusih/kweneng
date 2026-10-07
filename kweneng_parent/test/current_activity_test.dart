import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/models/flutter_models.dart';
import 'package:kweneng_parent/widgets/current_activity_card.dart';

void main() {
  TimetableData schedule({String date = '2026-10-06', int? day = 3}) =>
      TimetableData(
        date: date,
        templateName: 'School timetable',
        selectedDayNumber: day,
        days: const [
          TimetableDayData(
            dayNumber: 3,
            name: 'Day 3',
            blocks: [
              TimetableBlock(
                kind: 'lesson',
                periodName: 'Period 1',
                startTime: '08:00',
                endTime: '09:00',
                durationMinutes: 60,
                title: 'Mathematics',
              ),
            ],
          ),
        ],
      );
  test('current activity includes start, excludes end, and requires today', () {
    expect(
      scheduledNow(schedule(), DateTime(2026, 10, 6, 8)).single.title,
      'Mathematics',
    );
    expect(scheduledNow(schedule(), DateTime(2026, 10, 6, 9)), isEmpty);
    expect(scheduledNow(schedule(), DateTime(2026, 10, 6, 7, 59)), isEmpty);
    expect(
      scheduledNow(schedule(date: '2026-10-05'), DateTime(2026, 10, 6, 8)),
      isEmpty,
    );
    expect(
      scheduledNow(schedule(day: null), DateTime(2026, 10, 6, 8)),
      isEmpty,
    );
  });
  test('concurrent published schedules are included', () {
    final data = TimetableData(
      date: '2026-10-06',
      days: [],
      schedules: [schedule(), schedule()],
    );
    expect(scheduledNow(data, DateTime(2026, 10, 6, 8)), hasLength(2));
  });
}
