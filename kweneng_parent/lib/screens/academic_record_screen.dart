import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';

import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';
import '../services/api_service.dart';

class AcademicRecordScreen extends ConsumerStatefulWidget {
  final int studentId;

  const AcademicRecordScreen({super.key, required this.studentId});

  @override
  ConsumerState<AcademicRecordScreen> createState() =>
      _AcademicRecordScreenState();
}

class _AcademicRecordScreenState
    extends ConsumerState<AcademicRecordScreen> {
  bool _downloading = false;

  @override
  Widget build(BuildContext context) {
    final record = ref.watch(academicRecordProvider(widget.studentId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Academic Record'),
        actions: [
          IconButton(
            tooltip: 'Download PDF',
            onPressed: _downloading ? null : _download,
            icon: _downloading
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.picture_as_pdf_outlined),
          ),
        ],
      ),
      body: record.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _RecordError(
          message: error.toString(),
          onRetry: () => ref.invalidate(
            academicRecordProvider(widget.studentId),
          ),
        ),
        data: (data) => RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(academicRecordProvider(widget.studentId));
            await ref.read(academicRecordProvider(widget.studentId).future);
          },
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
            children: [
              _StudentHeader(student: data.student),
              const SizedBox(height: 16),
              if (data.years.isEmpty)
                const _EmptyRecord()
              else
                ...data.years.map((year) => _YearCard(year: year)),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _download() async {
    setState(() => _downloading = true);
    try {
      final File file = await ApiService().downloadAcademicRecord(
        widget.studentId,
      );
      await OpenFilex.open(file.path);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not download the record. $error')),
        );
      }
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }
}

class _StudentHeader extends StatelessWidget {
  final AcademicRecordStudent student;

  const _StudentHeader({required this.student});

  @override
  Widget build(BuildContext context) => Card(
    color: Theme.of(context).colorScheme.primaryContainer,
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          CircleAvatar(
            radius: 26,
            child: Text(
              student.name.isEmpty ? '?' : student.name[0].toUpperCase(),
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  student.name,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
                ),
                Text(
                  [student.currentClass ?? '', student.admissionNo]
                      .where((value) => value.trim().isNotEmpty)
                      .join(' • '),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

class _YearCard extends StatelessWidget {
  final AcademicRecordYear year;

  const _YearCard({required this.year});

  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.only(bottom: 14),
    clipBehavior: Clip.antiAlias,
    child: ExpansionTile(
      initiallyExpanded: true,
      leading: const Icon(Icons.school_outlined),
      title: Text(
        year.name,
        style: const TextStyle(fontWeight: FontWeight.w900),
      ),
      subtitle: Text(
        [year.className ?? '', _friendlyStatus(year.status) ?? '']
            .where((value) => value.isNotEmpty)
            .join(' • '),
      ),
      children: year.terms.isEmpty
          ? const [
              Padding(
                padding: EdgeInsets.fromLTRB(16, 0, 16, 16),
                child: Text('No term results are recorded for this year.'),
              ),
            ]
          : year.terms.map((term) => _TermSection(term: term)).toList(),
    ),
  );
}

class _TermSection extends StatelessWidget {
  final AcademicRecordTerm term;

  const _TermSection({required this.term});

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(14, 0, 14, 16),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                term.name,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            if (term.average != null)
              Chip(label: Text('Average ${_score(term.average)}')),
          ],
        ),
        const SizedBox(height: 8),
        if (term.subjects.isEmpty)
          const Text('No subject results recorded.')
        else
          ...term.subjects.map((subject) => _SubjectCard(subject: subject)),
      ],
    ),
  );
}

class _SubjectCard extends StatelessWidget {
  final AcademicRecordSubject subject;

  const _SubjectCard({required this.subject});

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    margin: const EdgeInsets.only(bottom: 8),
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: const Color(0xFFF8FAFC),
      border: Border.all(color: const Color(0xFFE2E8F0)),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                subject.subject,
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
            if (subject.grade != null)
              Chip(
                visualDensity: VisualDensity.compact,
                label: Text('Grade ${subject.grade}'),
              ),
          ],
        ),
        if (subject.code != null)
          Text(subject.code!, style: const TextStyle(color: Colors.grey)),
        const SizedBox(height: 6),
        Wrap(
          spacing: 12,
          runSpacing: 6,
          children: [
            if (subject.midtermScore != null)
              Text('Midterm ${_score(subject.midtermScore)}'),
            if (subject.endtermScore != null)
              Text('Endterm ${_score(subject.endtermScore)}'),
            if (subject.average != null)
              Text(
                'Overall ${_score(subject.average)}',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
          ],
        ),
        if (subject.remarks != null) ...[
          const SizedBox(height: 8),
          Text(
            subject.remarks!,
            style: TextStyle(color: Colors.grey.shade700),
          ),
        ],
      ],
    ),
  );
}

class _EmptyRecord extends StatelessWidget {
  const _EmptyRecord();

  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.symmetric(vertical: 80),
    child: Column(
      children: [
        Icon(Icons.history_edu_outlined, size: 64, color: Colors.grey),
        SizedBox(height: 14),
        Text('No academic history has been recorded yet.'),
      ],
    ),
  );
}

class _RecordError extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _RecordError({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.error_outline, size: 52, color: Colors.red),
          const SizedBox(height: 12),
          Text(
            'Could not load this academic record.\n$message',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 14),
          FilledButton(onPressed: onRetry, child: const Text('Try again')),
        ],
      ),
    ),
  );
}

String _score(double? value) {
  if (value == null) return '—';
  final number = value == value.roundToDouble()
      ? value.toInt().toString()
      : value.toStringAsFixed(1);
  return '$number%';
}

String? _friendlyStatus(String? value) {
  if (value == null || value.isEmpty) return null;
  return value
      .split('_')
      .map(
        (part) => part.isEmpty
            ? part
            : '${part[0].toUpperCase()}${part.substring(1).toLowerCase()}',
      )
      .join(' ');
}
