import 'package:flutter_test/flutter_test.dart';
import 'package:kweneng_parent/models/flutter_models.dart';

void main() {
  test('dashboard child parses awards and prefect appointments', () {
    final child = ChildModel.fromJson({
      'id': 7,
      'name': 'Student One',
      'admission_no': 'S001',
      'is_blocked': false,
      'awards': [
        {
          'id': 11,
          'title': 'Academic Excellence',
          'position': 1,
          'percentage': 91.5,
          'badge': {'icon': 'trophy', 'color': '#D4AF37'},
        },
      ],
      'prefect_appointments': [
        {
          'id': 13,
          'title': 'Senior Prefect',
          'status': 'active',
          'duties': 'Support assembly',
        },
      ],
    });

    expect(child.awards.single.title, 'Academic Excellence');
    expect(child.awards.single.percentage, 91.5);
    expect(child.prefectAppointments.single.title, 'Senior Prefect');
    expect(child.prefectAppointments.single.duties, 'Support assembly');
  });
}
