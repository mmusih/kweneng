import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/utils/grade_utils.dart';

void main() {
  test('grades match the supplied Excel formula', () {
    final expected = <double, String>{
      100: 'A*',
      89.01: 'A*',
      89: 'A',
      79.01: 'A',
      79: 'B',
      69.01: 'B',
      69: 'C',
      59.01: 'C',
      59: 'D',
      49.01: 'D',
      49: 'E',
      39.01: 'E',
      39: 'F',
      35: 'F',
      34.01: 'F',
      34: 'G',
      0: 'G',
    };

    for (final entry in expected.entries) {
      expect(gradeForScore(entry.key), entry.value, reason: '${entry.key}');
    }

    expect(gradeForScore(null), isNull);
  });
}
