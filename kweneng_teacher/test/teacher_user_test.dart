import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_teacher/models/models.dart';

void main() {
  test('teacher user preserves the required-password flag', () {
    final user = TeacherUser.fromJson({
      'id': 1,
      'teacher_id': 7,
      'name': 'Teacher One',
      'email': 'teacher@example.com',
      'must_change_password': true,
    });

    expect(user.mustChangePassword, isTrue);
    expect(user.toJson()['must_change_password'], isTrue);
    expect(user.copyWith(mustChangePassword: false).mustChangePassword, isFalse);
  });
}
