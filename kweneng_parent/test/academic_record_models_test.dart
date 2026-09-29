import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/models/flutter_models.dart';

void main() {
  test('academic record parses years, terms, grades and remarks', () {
    final record = StudentAcademicRecord.fromJson({
      'student': {
        'id': 4,
        'name': 'Student One',
        'admission_no': 'S004',
        'current_class': 'Form 4A',
      },
      'years': [
        {
          'id': 2,
          'name': '2026',
          'class': 'Form 4A',
          'terms': [
            {
              'id': 6,
              'name': 'Term 2',
              'average': 74.5,
              'subjects': [
                {
                  'subject': 'Mathematics',
                  'average': 74.5,
                  'grade': 'B',
                  'remarks': 'Steady progress.',
                },
              ],
            },
          ],
        },
      ],
    });

    expect(record.student.currentClass, 'Form 4A');
    expect(record.years.single.terms.single.average, 74.5);
    expect(record.years.single.terms.single.subjects.single.grade, 'B');
    expect(
      record.years.single.terms.single.subjects.single.remarks,
      'Steady progress.',
    );
  });
}
