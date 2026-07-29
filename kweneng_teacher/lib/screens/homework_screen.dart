import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../providers/app_providers.dart';
import '../widgets/app_widgets.dart';

class HomeworkScreen extends ConsumerWidget {
  final List<TeachingAssignment> assignments;
  const HomeworkScreen({super.key, required this.assignments});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(homeworksProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Homework'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: () => ref.invalidate(homeworksProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load homework. $error',
          onRetry: () => ref.invalidate(homeworksProvider),
        ),
        data: (items) => items.isEmpty
            ? const EmptyView(
                icon: Icons.assignment_outlined,
                title: 'No homework sent yet',
                message:
                    'Tap the camera button to send homework to your assigned learners.',
              )
            : RefreshIndicator(
                onRefresh: () async {
                  ref.invalidate(homeworksProvider);
                  await ref.read(homeworksProvider.future);
                },
                child: ListView.builder(
                  padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
                  itemCount: items.length,
                  itemBuilder: (_, index) =>
                      _HomeworkTile(homework: items[index]),
                ),
              ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: assignments.isEmpty
            ? null
            : () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => SendHomeworkScreen(assignments: assignments),
                ),
              ),
        icon: const Icon(Icons.photo_camera_outlined),
        label: const Text('Send homework'),
      ),
    );
  }
}

class _HomeworkTile extends ConsumerWidget {
  final TeacherHomework homework;
  const _HomeworkTile({required this.homework});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final formatter = DateFormat('d MMM yyyy');

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        homework.title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${homework.schoolClass.name} • ${homework.subject.name}',
                        style: TextStyle(color: Colors.grey.shade700),
                      ),
                    ],
                  ),
                ),
                if (homework.canDelete)
                  IconButton(
                    tooltip: 'Delete homework',
                    onPressed: () => _confirmDelete(context, ref),
                    icon: const Icon(
                      Icons.delete_outline,
                      color: AppTheme.danger,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (homework.assignedDate != null)
                  InfoChip(
                    icon: Icons.today_outlined,
                    label: formatter.format(homework.assignedDate!),
                  ),
                if (homework.dueDate != null)
                  InfoChip(
                    icon: Icons.event_available_outlined,
                    label: 'Due ${formatter.format(homework.dueDate!)}',
                  ),
                if (homework.isGraded && homework.totalMarks != null)
                  InfoChip(
                    icon: Icons.grade_outlined,
                    label: 'Out of ${homework.totalMarks!.toStringAsFixed(1)}',
                  ),
                if (homework.attachmentRemoved)
                  const InfoChip(
                    icon: Icons.cleaning_services_outlined,
                    label: 'File cleaned',
                  ),
              ],
            ),
            if ((homework.description ?? '').isNotEmpty) ...[
              const SizedBox(height: 10),
              Text(
                homework.description!,
                style: TextStyle(color: Colors.grey.shade700),
              ),
            ],
            if (homework.hasImage &&
                homework.imageUrl != null &&
                !homework.attachmentRemoved) ...[
              const SizedBox(height: 12),
              FutureBuilder<Map<String, String>>(
                future: ref.read(apiProvider).authorizedHeaders(),
                builder: (context, snapshot) {
                  if (!snapshot.hasData) {
                    return Container(
                      height: 150,
                      alignment: Alignment.center,
                      child: const CircularProgressIndicator(),
                    );
                  }
                  return ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: Image.network(
                      homework.imageUrl!,
                      headers: snapshot.data!,
                      height: 190,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => Container(
                        height: 120,
                        alignment: Alignment.center,
                        color: Colors.grey.shade200,
                        child: const Text('Image preview unavailable'),
                      ),
                    ),
                  );
                },
              ),
            ],
          ],
        ),
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context, WidgetRef ref) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Delete homework?'),
        content: const Text(
          'This removes the uploaded file and hides the homework from parents. Use this when the wrong picture was uploaded.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (ok != true || !context.mounted) return;
    final done = await ref
        .read(homeworkDeleteProvider.notifier)
        .delete(homework.id);
    if (!context.mounted) return;
    final state = ref.read(homeworkDeleteProvider);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          done
              ? (state.message ?? 'Homework deleted.')
              : (state.error ?? 'Could not delete homework.'),
        ),
      ),
    );
  }
}

class SendHomeworkScreen extends ConsumerStatefulWidget {
  final List<TeachingAssignment> assignments;
  const SendHomeworkScreen({super.key, required this.assignments});

  @override
  ConsumerState<SendHomeworkScreen> createState() => _SendHomeworkScreenState();
}

class _SendHomeworkScreenState extends ConsumerState<SendHomeworkScreen> {
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _totalMarks = TextEditingController();
  final _picker = ImagePicker();

  TeachingAssignment? _assignment;
  File? _image;
  DateTime? _dueDate;
  bool _isGraded = false;

  @override
  void initState() {
    super.initState();
    if (widget.assignments.length == 1) _assignment = widget.assignments.first;
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _totalMarks.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final sending = ref.watch(homeworkSendProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Send Homework')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        children: [
          _StepCard(
            title: '1. Select class and subject',
            child: DropdownButtonFormField<TeachingAssignment>(
              initialValue: _assignment,
              isExpanded: true,
              menuMaxHeight: 360,
              decoration: const InputDecoration(labelText: 'Class and subject'),
              selectedItemBuilder: (context) => widget.assignments
                  .map(
                    (a) => Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        a.label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              items: widget.assignments
                  .map(
                    (a) => DropdownMenuItem<TeachingAssignment>(
                      value: a,
                      child: Text(
                        a.label,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        softWrap: true,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _assignment = value),
            ),
          ),
          const SizedBox(height: 14),
          _StepCard(
            title: '2. Take a picture',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_image == null)
                  Container(
                    height: 170,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: const Center(
                      child: Icon(
                        Icons.photo_camera_outlined,
                        size: 54,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  )
                else
                  ClipRRect(
                    borderRadius: BorderRadius.circular(16),
                    child: Image.file(_image!, height: 230, fit: BoxFit.cover),
                  ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () => _pick(ImageSource.camera),
                        icon: const Icon(Icons.camera_alt_outlined),
                        label: const Text('Camera'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _pick(ImageSource.gallery),
                        icon: const Icon(Icons.photo_library_outlined),
                        label: const Text('Gallery'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          _StepCard(
            title: '3. Optional details',
            child: Column(
              children: [
                TextField(
                  controller: _title,
                  textCapitalization: TextCapitalization.sentences,
                  decoration: const InputDecoration(
                    labelText: 'Title (optional)',
                    hintText: 'Backend will use subject homework if empty',
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _description,
                  minLines: 2,
                  maxLines: 4,
                  textCapitalization: TextCapitalization.sentences,
                  decoration: const InputDecoration(
                    labelText: 'Note (optional)',
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        _dueDate == null
                            ? 'No due date selected'
                            : 'Due: ${DateFormat('d MMM yyyy').format(_dueDate!)}',
                      ),
                    ),
                    TextButton.icon(
                      onPressed: _pickDueDate,
                      icon: const Icon(Icons.calendar_today_outlined, size: 16),
                      label: const Text('Due date'),
                    ),
                  ],
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  value: _isGraded,
                  title: const Text('Track marks for this homework'),
                  subtitle: const Text(
                    'Optional: enter what the homework is out of.',
                  ),
                  onChanged: (value) => setState(() => _isGraded = value),
                ),
                if (_isGraded)
                  TextField(
                    controller: _totalMarks,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: const InputDecoration(labelText: 'Total marks'),
                  ),
              ],
            ),
          ),
          if ((sending.error ?? '').isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(sending.error!, style: const TextStyle(color: Colors.red)),
          ],
        ],
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(12),
        child: FilledButton.icon(
          onPressed: sending.isLoading ? null : _send,
          icon: sending.isLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.send_outlined),
          label: Text(sending.isLoading ? 'Sending...' : 'Send to parents'),
        ),
      ),
    );
  }

  Future<void> _pick(ImageSource source) async {
    final picked = await _picker.pickImage(
      source: source,
      imageQuality: 82,
      maxWidth: 1600,
    );
    if (picked != null) setState(() => _image = File(picked.path));
  }

  Future<void> _pickDueDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _dueDate ?? now,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: DateTime(now.year + 1),
    );
    if (picked != null) setState(() => _dueDate = picked);
  }

  Future<void> _send() async {
    final assignment = _assignment;
    final image = _image;
    if (assignment == null) {
      _snack('Select a class and subject.');
      return;
    }
    if (image == null) {
      _snack('Take or choose a homework picture.');
      return;
    }

    final total = _isGraded ? double.tryParse(_totalMarks.text.trim()) : null;
    if (_isGraded && (total == null || total <= 0)) {
      _snack('Enter valid total marks.');
      return;
    }

    final ok = await ref
        .read(homeworkSendProvider.notifier)
        .send(
          classId: assignment.schoolClass.id,
          subjectId: assignment.subject.id,
          clientRequestId: const Uuid().v4(),
          image: image,
          title: _title.text,
          description: _description.text,
          dueDate: _dueDate,
          isGraded: _isGraded,
          totalMarks: total,
        );

    if (!mounted) return;
    final state = ref.read(homeworkSendProvider);
    if (ok) {
      _snack(state.message ?? 'Homework sent.');
      Navigator.pop(context);
    } else {
      _snack(state.error ?? 'Could not send homework.');
    }
  }

  void _snack(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }
}

class _StepCard extends StatelessWidget {
  final String title;
  final Widget child;
  const _StepCard({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 12),
            child,
          ],
        ),
      ),
    );
  }
}
