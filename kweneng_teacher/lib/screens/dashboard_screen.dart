import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../providers/app_providers.dart';
import '../widgets/app_widgets.dart';
import 'attendance_screen.dart';
import 'homework_screen.dart';
import 'marks_screen.dart';
import 'schemes_screen.dart';
import 'timetable_screen.dart';

class TeacherDashboardScreen extends ConsumerWidget {
  const TeacherDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(teacherDashboardProvider);

    return Scaffold(
      body: dashboard.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load teacher dashboard. $error',
          onRetry: () => ref.invalidate(teacherDashboardProvider),
        ),
        data: (data) => RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(teacherDashboardProvider);
            await ref.read(teacherDashboardProvider.future);
          },
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              _Header(data: data),
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _QuickActions(data: data),
                    const SizedBox(height: 18),
                    _TeachingAssignments(data: data),
                    const SizedBox(height: 90),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Header extends ConsumerWidget {
  final TeacherDashboardData data;
  const _Header({required this.data});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 52, 18, 24),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppTheme.primaryDark, AppTheme.primary],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Good day,',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.72),
                      ),
                    ),
                    Text(
                      data.teacher.name.split(' ').first,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) async {
                  if (value == 'logout') {
                    await ref.read(authProvider.notifier).logout();
                  }
                },
                itemBuilder: (_) => const [
                  PopupMenuItem(value: 'logout', child: Text('Log out')),
                ],
                child: CircleAvatar(
                  backgroundColor: Colors.white,
                  child: Text(
                    data.teacher.name.isEmpty
                        ? 'T'
                        : data.teacher.name.substring(0, 1).toUpperCase(),
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (data.academicYear != null)
                _HeaderPill(
                  icon: Icons.school_outlined,
                  text: data.academicYear!.name,
                ),
              if (data.term != null)
                _HeaderPill(
                  icon: Icons.calendar_today_outlined,
                  text: data.term!.name,
                ),
              _HeaderPill(
                icon: Icons.menu_book_outlined,
                text:
                    '${data.counts.teachingAssignments} assignment${data.counts.teachingAssignments == 1 ? '' : 's'}',
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _MiniStat(
                  label: 'Classes',
                  value: '${data.counts.classTeacherClasses}',
                  icon: Icons.groups_outlined,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _MiniStat(
                  label: 'Homework',
                  value: '${data.counts.homeworks}',
                  icon: Icons.assignment_outlined,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _MiniStat(
                  label: 'Absences',
                  value: '${data.counts.pendingAbsenceNotices}',
                  icon: Icons.event_busy_outlined,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeaderPill extends StatelessWidget {
  final IconData icon;
  final String text;
  const _HeaderPill({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 15),
          const SizedBox(width: 5),
          Text(
            text,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  const _MiniStat({
    required this.label,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.13),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: Colors.white.withValues(alpha: 0.70), size: 18),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 23,
              fontWeight: FontWeight.w900,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.65),
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActions extends ConsumerWidget {
  final TeacherDashboardData data;
  const _QuickActions({required this.data});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canRegister = data.classTeacherClasses.isNotEmpty;
    final canTeach = data.teachingAssignments.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Today’s tools',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
        ),
        const SizedBox(height: 10),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 1.10,
          children: [
            _ActionCard(
              enabled: true,
              icon: Icons.calendar_view_week_outlined,
              title: 'Timetable',
              subtitle: 'Today and full cycle',
              color: const Color(0xFF0284C7),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const TimetableScreen()),
              ),
            ),
            _ActionCard(
              enabled: canRegister,
              icon: Icons.fact_check_outlined,
              title: 'Register',
              subtitle: canRegister ? 'Take attendance' : 'No class assigned',
              color: AppTheme.success,
              onTap: () => _openAttendance(context, data),
            ),
            _ActionCard(
              enabled: canTeach,
              icon: Icons.edit_note_outlined,
              title: 'Marks',
              subtitle: canTeach ? 'Enter marks' : 'No subjects',
              color: AppTheme.primary,
              onTap: () => _openMarks(context, data),
            ),
            _ActionCard(
              enabled: canTeach,
              icon: Icons.photo_camera_outlined,
              title: 'Homework',
              subtitle: canTeach ? 'Photo and send' : 'No subjects',
              color: const Color(0xFF0EA5E9),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) =>
                      HomeworkScreen(assignments: data.teachingAssignments),
                ),
              ),
            ),
            _ActionCard(
              enabled: true,
              icon: Icons.route_outlined,
              title: 'Schemes',
              subtitle: 'Track syllabus progress',
              color: const Color(0xFF7C3AED),
              onTap: () => Navigator.of(
                context,
              ).push(MaterialPageRoute(builder: (_) => const SchemesScreen())),
            ),
            _ActionCard(
              enabled: true,
              icon: Icons.refresh,
              title: 'Refresh',
              subtitle: 'Sync latest data',
              color: AppTheme.warning,
              onTap: () => ref.invalidate(teacherDashboardProvider),
            ),
          ],
        ),
      ],
    );
  }

  void _openAttendance(BuildContext context, TeacherDashboardData data) async {
    final selected = await _pickClass(context, data.classTeacherClasses);
    if (selected == null || !context.mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => AttendanceScreen(schoolClass: selected),
      ),
    );
  }

  void _openMarks(BuildContext context, TeacherDashboardData data) async {
    final selected = await _pickAssignment(context, data.teachingAssignments);
    if (selected == null || !context.mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => MarksScreen(assignment: selected)),
    );
  }

  Future<SchoolClassInfo?> _pickClass(
    BuildContext context,
    List<SchoolClassInfo> classes,
  ) async {
    if (classes.length == 1) return classes.first;
    return showModalBottomSheet<SchoolClassInfo>(
      context: context,
      showDragHandle: true,
      builder: (_) => ListView(
        shrinkWrap: true,
        children: classes
            .map(
              (c) => ListTile(
                title: Text(c.name),
                subtitle: Text('${c.studentCount} learners'),
                onTap: () => Navigator.pop(context, c),
              ),
            )
            .toList(),
      ),
    );
  }

  Future<TeachingAssignment?> _pickAssignment(
    BuildContext context,
    List<TeachingAssignment> assignments,
  ) async {
    if (assignments.length == 1) return assignments.first;
    return showModalBottomSheet<TeachingAssignment>(
      context: context,
      showDragHandle: true,
      builder: (_) => ListView(
        shrinkWrap: true,
        children: assignments
            .map(
              (a) => ListTile(
                title: Text(a.schoolClass.name),
                subtitle: Text(a.subject.name),
                onTap: () => Navigator.pop(context, a),
              ),
            )
            .toList(),
      ),
    );
  }
}

class _ActionCard extends StatelessWidget {
  final bool enabled;
  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  const _ActionCard({
    required this.enabled,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: enabled ? onTap : null,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: const Color(0xFFE5E7EB)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  icon,
                  color: enabled ? color : Colors.grey,
                  size: 26,
                ),
              ),
              const Spacer(),
              Text(
                title,
                style: TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 16,
                  color: enabled ? const Color(0xFF0F172A) : Colors.grey,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TeachingAssignments extends StatelessWidget {
  final TeacherDashboardData data;
  const _TeachingAssignments({required this.data});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Teaching assignments',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 8),
            if (data.teachingAssignments.isEmpty)
              const Text('No subject-class assignments found.')
            else
              ...data.teachingAssignments.map(
                (a) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(
                    child: Icon(Icons.menu_book_outlined),
                  ),
                  title: Text(
                    a.schoolClass.name,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  subtitle: Text(a.subject.name),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => MarksScreen(assignment: a),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
