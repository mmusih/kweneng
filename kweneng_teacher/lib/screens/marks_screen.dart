import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/models.dart';
import '../providers/app_providers.dart';
import '../utils/grade_utils.dart';
import '../widgets/app_widgets.dart';

class MarksScreen extends ConsumerStatefulWidget {
  final TeachingAssignment assignment;
  const MarksScreen({super.key, required this.assignment});

  @override
  ConsumerState<MarksScreen> createState() => _MarksScreenState();
}

class _MarksScreenState extends ConsumerState<MarksScreen> {
  final List<MarkStudent> _students = [];
  bool _hydrated = false;

  MarkSheetArgs get _args => MarkSheetArgs(
    classId: widget.assignment.schoolClass.id,
    subjectId: widget.assignment.subject.id,
  );

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(markSheetProvider(_args));
    final saving = ref.watch(marksSaveProvider);

    return Scaffold(
      appBar: AppBar(title: Text(widget.assignment.label)),
      body: async.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load marks sheet. $error',
          onRetry: () => ref.invalidate(markSheetProvider(_args)),
        ),
        data: (data) {
          if (!_hydrated) {
            _students
              ..clear()
              ..addAll(
                data.students.map(
                  (s) => MarkStudent(
                    id: s.id,
                    admissionNo: s.admissionNo,
                    name: s.name,
                    midtermScore: s.midtermScore,
                    endtermScore: s.endtermScore,
                    remarks: s.remarks,
                  ),
                ),
              );
            _hydrated = true;
          }

          return Column(
            children: [
              _MarksHeader(data: data),
              Expanded(
                child: _students.isEmpty
                    ? const EmptyView(
                        icon: Icons.people_outline,
                        title: 'No learners',
                        message:
                            'No learners are assigned to you for this class and subject.',
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.fromLTRB(12, 8, 12, 100),
                        itemCount: _students.length,
                        itemBuilder: (_, index) => _MarkStudentTile(
                          student: _students[index],
                          midtermLocked:
                              data.term.midtermLocked || data.term.locked,
                          endtermLocked:
                              data.term.endtermLocked || data.term.locked,
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
          label: Text(saving.isLoading ? 'Saving...' : 'Save marks'),
        ),
      ),
    );
  }

  Future<void> _save() async {
    final ok = await ref
        .read(marksSaveProvider.notifier)
        .save(
          classId: widget.assignment.schoolClass.id,
          subjectId: widget.assignment.subject.id,
          students: _students,
        );
    if (!mounted) return;
    final state = ref.read(marksSaveProvider);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          ok
              ? (state.message ?? 'Marks saved.')
              : (state.error ?? 'Could not save marks.'),
        ),
      ),
    );
  }
}

class _MarksHeader extends StatelessWidget {
  final MarkSheetData data;
  const _MarksHeader({required this.data});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      color: Colors.white,
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: [
          InfoChip(
            icon: Icons.school_outlined,
            label: data.assignment.schoolClass.name,
          ),
          InfoChip(
            icon: Icons.menu_book_outlined,
            label: data.assignment.subject.name,
          ),
          InfoChip(icon: Icons.calendar_today_outlined, label: data.term.name),
          if (data.term.midtermLocked)
            const InfoChip(icon: Icons.lock_outline, label: 'Midterm locked'),
          if (data.term.endtermLocked)
            const InfoChip(icon: Icons.lock_outline, label: 'Endterm locked'),
        ],
      ),
    );
  }
}

class _MarkStudentTile extends StatefulWidget {
  final MarkStudent student;
  final bool midtermLocked;
  final bool endtermLocked;

  const _MarkStudentTile({
    required this.student,
    required this.midtermLocked,
    required this.endtermLocked,
  });

  @override
  State<_MarkStudentTile> createState() => _MarkStudentTileState();
}

class _MarkStudentTileState extends State<_MarkStudentTile> {
  late final TextEditingController _midterm;
  late final TextEditingController _endterm;
  late final TextEditingController _remarks;
  late bool _commentIsAutomatic;

  @override
  void initState() {
    super.initState();
    _midterm = TextEditingController(
      text: widget.student.midtermScore?.toStringAsFixed(1) ?? '',
    );
    _endterm = TextEditingController(
      text: widget.student.endtermScore?.toStringAsFixed(1) ?? '',
    );
    final generated = _generateTeacherComment(
      widget.student.midtermScore,
      widget.student.endtermScore,
    );
    _commentIsAutomatic =
        widget.student.remarks.trim().isEmpty ||
        _isGeneratedTeacherComment(widget.student.remarks);
    if (_commentIsAutomatic) {
      widget.student.remarks = generated;
    }
    _remarks = TextEditingController(text: widget.student.remarks);
  }

  @override
  void dispose() {
    _midterm.dispose();
    _endterm.dispose();
    _remarks.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final midtermGrade = gradeForScore(widget.student.midtermScore);
    final endtermGrade = gradeForScore(widget.student.endtermScore);
    final overallGrade = gradeForScores(
      widget.student.midtermScore,
      widget.student.endtermScore,
    );

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.student.name,
              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15),
            ),
            if (widget.student.admissionNo.isNotEmpty)
              Text(
                widget.student.admissionNo,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _midterm,
                    enabled: !widget.midtermLocked,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(
                        RegExp(r'^\d{0,3}(\.\d{0,2})?'),
                      ),
                    ],
                    decoration: InputDecoration(
                      labelText: widget.midtermLocked
                          ? 'Midterm locked'
                          : 'Midterm',
                    ),
                    onChanged: (value) {
                      setState(() {
                        widget.student.midtermScore = _score(value);
                        _refreshAutomaticComment();
                      });
                    },
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _endterm,
                    enabled: !widget.endtermLocked,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(
                        RegExp(r'^\d{0,3}(\.\d{0,2})?'),
                      ),
                    ],
                    decoration: InputDecoration(
                      labelText: widget.endtermLocked
                          ? 'Endterm locked'
                          : 'Endterm',
                    ),
                    onChanged: (value) {
                      setState(() {
                        widget.student.endtermScore = _score(value);
                        _refreshAutomaticComment();
                      });
                    },
                  ),
                ),
              ],
            ),
            if (overallGrade != null) ...[
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  if (midtermGrade != null)
                    Chip(label: Text('Midterm: $midtermGrade')),
                  if (endtermGrade != null)
                    Chip(label: Text('Endterm: $endtermGrade')),
                  Chip(label: Text('Overall: $overallGrade')),
                ],
              ),
            ],
            const SizedBox(height: 10),
            TextField(
              controller: _remarks,
              minLines: 1,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Comment',
                helperText:
                    'Generated from marks in real time. Edit to use a custom comment.',
              ),
              onChanged: (value) {
                widget.student.remarks = value;
                _commentIsAutomatic = false;
              },
            ),
          ],
        ),
      ),
    );
  }

  double? _score(String value) {
    final score = double.tryParse(value);
    if (score == null) return null;
    if (score < 0) return 0;
    if (score > 100) return 100;
    return score;
  }

  void _refreshAutomaticComment() {
    if (!_commentIsAutomatic) return;

    final generated = _generateTeacherComment(
      widget.student.midtermScore,
      widget.student.endtermScore,
    );
    widget.student.remarks = generated;
    _remarks.value = TextEditingValue(
      text: generated,
      selection: TextSelection.collapsed(offset: generated.length),
    );
  }

  String _performancePhrase(double score) {
    if (score >= 90) return 'excellent performance';
    if (score >= 80) return 'very good performance';
    if (score >= 70) return 'good performance';
    if (score >= 60) return 'fair performance';
    if (score >= 50) return 'satisfactory performance';
    if (score >= 40) return 'below expectation performance';
    return 'weak performance';
  }

  String _generateTeacherComment(double? midterm, double? endterm) {
    if (midterm == null && endterm == null) return '';

    if (midterm != null && endterm == null) {
      return 'The learner showed ${_performancePhrase(midterm)} in the '
          'midterm assessment but did not write the end-of-term assessment.';
    }

    if (midterm == null && endterm != null) {
      return 'The learner showed ${_performancePhrase(endterm)} in the '
          'end-of-term assessment.';
    }

    final difference = endterm! - midterm!;

    if (difference >= 10) {
      return endterm >= 60
          ? 'The learner has shown clear improvement since midterm. This '
                'progress is encouraging; continued effort is needed.'
          : 'The learner has improved since midterm, but more effort is still '
                'needed to reach the expected standard.';
    }

    if (difference >= 3) {
      return endterm >= 70
          ? 'The learner has improved and is making steady progress. More '
                'consistent effort can lead to even better results.'
          : 'The learner has shown some improvement since midterm. Continued '
                'practice is encouraged.';
    }

    if (difference <= -10) {
      return 'The learner’s performance has declined significantly since '
          'midterm. Immediate improvement and greater commitment are required.';
    }

    if (difference <= -3) {
      return 'The learner’s performance has dropped since midterm. More focus '
          'and consistency are needed.';
    }

    if (endterm >= 80) {
      return 'The learner has maintained a very good standard throughout the '
          'term. Keep up the good work.';
    }

    if (endterm >= 60) {
      return 'The learner’s performance has remained fairly steady. More '
          'effort can lead to better results.';
    }

    if (endterm >= 40) {
      return 'The learner’s performance remains below expectation. More '
          'effort and support are required.';
    }

    return 'The learner’s performance is weak and requires immediate '
        'improvement.';
  }

  bool _isGeneratedTeacherComment(String comment) {
    final normalized = comment.trim();
    if (normalized.isEmpty) return false;

    const comparisonComments = {
      'The learner has shown clear improvement since midterm. This progress is encouraging; continued effort is needed.',
      'The learner has improved since midterm, but more effort is still needed to reach the expected standard.',
      'The learner has improved and is making steady progress. More consistent effort can lead to even better results.',
      'The learner has shown some improvement since midterm. Continued practice is encouraged.',
      'The learner’s performance has declined significantly since midterm. Immediate improvement and greater commitment are required.',
      'The learner’s performance has dropped since midterm. More focus and consistency are needed.',
      'The learner has maintained a very good standard throughout the term. Keep up the good work.',
      'The learner’s performance has remained fairly steady. More effort can lead to better results.',
      'The learner’s performance remains below expectation. More effort and support are required.',
      'The learner’s performance is weak and requires immediate improvement.',
    };

    if (comparisonComments.contains(normalized)) return true;

    return RegExp(
      r'^The learner showed (?:excellent performance|very good performance|good performance|fair performance|satisfactory performance|below expectation performance|weak performance) in the (?:midterm assessment but did not write the end-of-term assessment|end-of-term assessment)\.$',
    ).hasMatch(normalized);
  }
}
