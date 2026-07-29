import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../providers/app_providers.dart';
import '../widgets/app_widgets.dart';

class AttendanceScreen extends ConsumerStatefulWidget {
  final SchoolClassInfo schoolClass;
  const AttendanceScreen({super.key, required this.schoolClass});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  DateTime _date = DateTime.now();
  final List<AttendanceStudent> _students = [];
  AttendanceArgs? _loadedArgs;

  AttendanceArgs get _args =>
      AttendanceArgs(classId: widget.schoolClass.id, date: _date);

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(attendanceProvider(_args));
    final saving = ref.watch(attendanceSaveProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.schoolClass.name} Register'),
        actions: [
          IconButton(
            tooltip: 'Change date',
            onPressed: _pickDate,
            icon: const Icon(Icons.calendar_month_outlined),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load register. $error',
          onRetry: () => ref.invalidate(attendanceProvider(_args)),
        ),
        data: (data) {
          if (_loadedArgs != _args) {
            _students
              ..clear()
              ..addAll(
                data.students.map(
                  (s) => AttendanceStudent(
                    id: s.id,
                    admissionNo: s.admissionNo,
                    name: s.name,
                    status: s.status,
                    remarks: s.remarks,
                    saved: s.saved,
                    parentAbsenceNotice: s.parentAbsenceNotice,
                  ),
                ),
              );
            _loadedArgs = _args;
          }

          return Column(
            children: [
              _RegisterTools(
                date: _date,
                students: _students,
                onPickDate: _pickDate,
                onBulk: _bulkStatus,
              ),
              Expanded(
                child: _students.isEmpty
                    ? const EmptyView(
                        icon: Icons.people_outline,
                        title: 'No learners',
                        message: 'No students found for this class.',
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.fromLTRB(12, 8, 12, 100),
                        itemCount: _students.length,
                        itemBuilder: (context, index) => _StudentAttendanceTile(
                          student: _students[index],
                          onChanged: () => setState(() {}),
                        ),
                      ),
              ),
            ],
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(12),
        child: FilledButton.icon(
          onPressed: saving.isLoading || _students.isEmpty ? null : _save,
          icon: saving.isLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.save_outlined),
          label: Text(saving.isLoading ? 'Saving...' : 'Save register'),
        ),
      ),
    );
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(DateTime.now().year - 1),
      lastDate: DateTime(DateTime.now().year + 1),
    );
    if (picked != null) {
      setState(() {
        _date = picked;
        _loadedArgs = null;
      });
    }
  }

  void _bulkStatus(String status) {
    setState(() {
      for (final student in _students) {
        student.status = status;
      }
    });
  }

  Future<void> _save() async {
    final ok = await ref
        .read(attendanceSaveProvider.notifier)
        .save(classId: widget.schoolClass.id, date: _date, students: _students);
    if (!mounted) return;
    final state = ref.read(attendanceSaveProvider);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          ok
              ? (state.message ?? 'Attendance saved.')
              : (state.error ?? 'Could not save attendance.'),
        ),
      ),
    );
  }
}

class _RegisterTools extends StatelessWidget {
  final DateTime date;
  final List<AttendanceStudent> students;
  final VoidCallback onPickDate;
  final ValueChanged<String> onBulk;

  const _RegisterTools({
    required this.date,
    required this.students,
    required this.onPickDate,
    required this.onBulk,
  });

  @override
  Widget build(BuildContext context) {
    final counts = <String, int>{
      'present': 0,
      'absent': 0,
      'late': 0,
      'excused': 0,
    };
    for (final student in students) {
      counts[student.status] = (counts[student.status] ?? 0) + 1;
    }

    return Container(
      padding: const EdgeInsets.all(12),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  DateFormat('EEEE, d MMM yyyy').format(date),
                  style: const TextStyle(fontWeight: FontWeight.w900),
                ),
              ),
              TextButton.icon(
                onPressed: onPickDate,
                icon: const Icon(Icons.calendar_today_outlined, size: 16),
                label: const Text('Date'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [
              InfoChip(
                icon: Icons.check_circle_outline,
                label: 'P ${counts['present']}',
                color: AppTheme.success,
              ),
              InfoChip(
                icon: Icons.cancel_outlined,
                label: 'A ${counts['absent']}',
                color: AppTheme.danger,
              ),
              InfoChip(
                icon: Icons.schedule_outlined,
                label: 'L ${counts['late']}',
                color: AppTheme.warning,
              ),
              InfoChip(
                icon: Icons.verified_outlined,
                label: 'E ${counts['excused']}',
                color: AppTheme.primary,
              ),
            ],
          ),
          const SizedBox(height: 10),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _BulkButton(
                  label: 'All Present',
                  status: 'present',
                  onBulk: onBulk,
                ),
                _BulkButton(
                  label: 'All Absent',
                  status: 'absent',
                  onBulk: onBulk,
                ),
                _BulkButton(label: 'All Late', status: 'late', onBulk: onBulk),
                _BulkButton(
                  label: 'All Excused',
                  status: 'excused',
                  onBulk: onBulk,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _BulkButton extends StatelessWidget {
  final String label;
  final String status;
  final ValueChanged<String> onBulk;
  const _BulkButton({
    required this.label,
    required this.status,
    required this.onBulk,
  });

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(right: 8),
    child: OutlinedButton(onPressed: () => onBulk(status), child: Text(label)),
  );
}

class _StudentAttendanceTile extends StatelessWidget {
  final AttendanceStudent student;
  final VoidCallback onChanged;

  const _StudentAttendanceTile({
    required this.student,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  child: Text(
                    student.name.isEmpty
                        ? 'S'
                        : student.name.substring(0, 1).toUpperCase(),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        student.name,
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                      if (student.admissionNo.isNotEmpty)
                        Text(
                          student.admissionNo,
                          style: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 12,
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            if (student.parentAbsenceNotice != null) ...[
              const SizedBox(height: 10),
              _ParentNotice(notice: student.parentAbsenceNotice!),
            ],
            const SizedBox(height: 12),
            SegmentedButton<String>(
              showSelectedIcon: false,
              segments: const [
                ButtonSegment(value: 'present', label: Text('P')),
                ButtonSegment(value: 'absent', label: Text('A')),
                ButtonSegment(value: 'late', label: Text('L')),
                ButtonSegment(value: 'excused', label: Text('E')),
              ],
              selected: {student.status},
              onSelectionChanged: (values) {
                student.status = values.first;
                onChanged();
              },
            ),
            const SizedBox(height: 10),
            TextFormField(
              initialValue: student.remarks,
              minLines: 1,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Remarks (optional)',
                isDense: true,
              ),
              onChanged: (value) => student.remarks = value,
            ),
          ],
        ),
      ),
    );
  }
}

class _ParentNotice extends StatelessWidget {
  final ParentAbsenceNoticeInfo notice;
  const _ParentNotice({required this.notice});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Parent reported absence',
            style: TextStyle(
              fontWeight: FontWeight.w900,
              color: Color(0xFF92400E),
            ),
          ),
          const SizedBox(height: 4),
          Text(notice.reason),
          if ((notice.note ?? '').isNotEmpty)
            Text(notice.note!, style: TextStyle(color: Colors.grey.shade700)),
        ],
      ),
    );
  }
}
