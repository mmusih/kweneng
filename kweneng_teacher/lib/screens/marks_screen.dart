import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/models.dart';
import '../providers/app_providers.dart';
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

  @override
  void initState() {
    super.initState();
    _midterm = TextEditingController(
      text: widget.student.midtermScore?.toStringAsFixed(1) ?? '',
    );
    _endterm = TextEditingController(
      text: widget.student.endtermScore?.toStringAsFixed(1) ?? '',
    );
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
                    onChanged: (value) =>
                        widget.student.midtermScore = _score(value),
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
                    onChanged: (value) =>
                        widget.student.endtermScore = _score(value),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _remarks,
              minLines: 1,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Remarks (optional)',
              ),
              onChanged: (value) => widget.student.remarks = value,
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
}
