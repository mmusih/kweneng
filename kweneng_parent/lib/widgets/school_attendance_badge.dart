import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../providers/flutter_providers.dart';

// This status must come from today's register, never the offline dashboard cache.
final todayAttendanceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final timer = Timer(const Duration(seconds: 30), ref.invalidateSelf);
      ref.onDispose(timer.cancel);
      return ref.read(apiServiceProvider).getTodayAttendance();
    });

class SchoolAttendanceBadge extends ConsumerStatefulWidget {
  final int childId;
  const SchoolAttendanceBadge({super.key, required this.childId});
  @override
  ConsumerState<SchoolAttendanceBadge> createState() =>
      _SchoolAttendanceBadgeState();
}

class _SchoolAttendanceBadgeState extends ConsumerState<SchoolAttendanceBadge>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.invalidate(todayAttendanceProvider);
    }
  }

  @override
  Widget build(BuildContext context) {
    final attendance = ref.watch(todayAttendanceProvider);
    var label = 'Checking attendance…';
    var colour = const Color(0xFFCDD9EA);
    if (attendance.hasError) {
      label = 'Attendance unavailable';
    } else if (!attendance.isLoading && attendance.hasValue) {
      final data = attendance.requireValue;
      final children = (data['children'] as List? ?? []).whereType<Map>();
      final matches = children.where(
        (entry) => entry['student_id'] == widget.childId,
      );
      final row = matches.firstOrNull;
      final schoolNow = DateTime.now().toUtc().add(const Duration(hours: 2));
      final checkedAt = DateTime.tryParse(data['checked_at']?.toString() ?? '');
      final fresh =
          checkedAt != null &&
          DateTime.now().toUtc().difference(checkedAt).abs() <
              const Duration(minutes: 2);
      if (row == null ||
          row['date'] != DateFormat('yyyy-MM-dd').format(schoolNow) ||
          !fresh) {
        label = 'Attendance unavailable';
      } else {
        label = row['label']?.toString() ?? 'Attendance not marked yet';
        if (row['status'] == 'absent' || row['status'] == 'excused') {
          colour = const Color(0xFFFF7777);
        } else if (row['status'] == 'present' || row['status'] == 'late') {
          final time = DateFormat('HH:mm').format(schoolNow);
          final end = row['school_end']?.toString() ?? '13:10';
          final inSchool =
              time.compareTo('07:30') >= 0 && time.compareTo(end) < 0;
          label = '${inSchool ? 'In school' : 'Present today'}: 07:30–$end';
          colour = inSchool ? const Color(0xFF6EE7A1) : const Color(0xFFCDD9EA);
        }
      }
    }
    return Semantics(
      label: label,
      excludeSemantics: true,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 5),
            child: Icon(Icons.circle, size: 8, color: colour),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                height: 1.4,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
