import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class AbsenceNoticeScreen extends ConsumerStatefulWidget {
  const AbsenceNoticeScreen({super.key});

  @override
  ConsumerState<AbsenceNoticeScreen> createState() =>
      _AbsenceNoticeScreenState();
}

class _AbsenceNoticeScreenState extends ConsumerState<AbsenceNoticeScreen> {
  final _formKey = GlobalKey<FormState>();
  final _noteController = TextEditingController();

  int? _studentId;
  DateTime? _absenceDate;
  DateTime? _returnDate;
  String _reason = 'Sick';

  final List<String> _reasons = const [
    'Sick',
    'Medical appointment',
    'Family matter',
    'Travel',
    'Emergency',
    'Other',
  ];

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  String _dateLabel(DateTime? date) {
    if (date == null) return 'Select date';
    return date.toIso8601String().split('T').first;
  }

  Future<void> _pickAbsenceDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _absenceDate ?? now,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 2),
    );

    if (picked == null) return;

    setState(() {
      _absenceDate = picked;
      if (_returnDate != null && _returnDate!.isBefore(picked)) {
        _returnDate = picked;
      }
    });
  }

  Future<void> _pickReturnDate() async {
    final now = DateTime.now();
    final first = _absenceDate ?? DateTime(now.year - 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: _returnDate ?? _absenceDate ?? now,
      firstDate: first,
      lastDate: DateTime(now.year + 2),
    );

    if (picked == null) return;

    setState(() => _returnDate = picked);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_studentId == null) {
      _showSnack('Please select a child.');
      return;
    }

    if (_absenceDate == null) {
      _showSnack('Please select the absence date.');
      return;
    }

    final ok = await ref
        .read(submitAbsenceNoticeProvider.notifier)
        .submit(
          studentId: _studentId!,
          absenceDate: _absenceDate!,
          expectedReturnDate: _returnDate,
          reason: _reason,
          note: _noteController.text.trim(),
        );

    if (!mounted) return;

    if (ok) {
      _noteController.clear();
      setState(() {
        _absenceDate = null;
        _returnDate = null;
        _reason = 'Sick';
      });

      ref.invalidate(absenceNoticesProvider);
      ref.invalidate(dashboardProvider);

      _showSnack('Absence notice submitted successfully.');
    } else {
      final error =
          ref.read(submitAbsenceNoticeProvider).error ??
          'Failed to submit absence notice.';
      _showSnack(error);
    }
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final dataAsync = ref.watch(absenceNoticesProvider);
    final submitState = ref.watch(submitAbsenceNoticeProvider);

    return Scaffold(
      backgroundColor: AppTheme.surface,
      appBar: AppBar(
        title: const Text('Absence Notice'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: dataAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorState(
          message: 'Could not load absence notices.',
          onRetry: () => ref.invalidate(absenceNoticesProvider),
        ),
        data: (data) {
          if (_studentId == null && data.children.isNotEmpty) {
            _studentId = data.children.first.id;
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(absenceNoticesProvider),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _buildForm(data.children, submitState.isLoading),
                const SizedBox(height: 20),
                const Text(
                  'Previous Absence Notices',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 10),
                if (data.notices.isEmpty)
                  const _EmptyState()
                else
                  ...data.notices.map(
                    (notice) => _AbsenceNoticeCard(notice: notice),
                  ),
                const SizedBox(height: 80),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildForm(List<ChildModel> children, bool isLoading) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Report Student Absence',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              Text(
                'Notify the school that your child will be absent.',
                style: TextStyle(color: Colors.grey.shade600),
              ),
              const SizedBox(height: 16),

              DropdownButtonFormField<int>(
                initialValue: _studentId,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'Child',
                  border: OutlineInputBorder(),
                ),
                selectedItemBuilder: (context) {
                  return children.map((child) {
                    return Text(
                      child.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    );
                  }).toList();
                },
                items: children
                    .map(
                      (child) => DropdownMenuItem<int>(
                        value: child.id,
                        child: Text(
                          child.className == null || child.className!.isEmpty
                              ? child.name
                              : '${child.name} · ${child.className}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    )
                    .toList(),
                onChanged: isLoading
                    ? null
                    : (v) => setState(() => _studentId = v),
                validator: (v) => v == null ? 'Please select a child.' : null,
              ),

              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: isLoading ? null : _pickAbsenceDate,
                      icon: const Icon(Icons.event_busy),
                      label: Text(
                        'Absent: ${_dateLabel(_absenceDate)}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: isLoading ? null : _pickReturnDate,
                      icon: const Icon(Icons.event_available),
                      label: Text(
                        'Return: ${_dateLabel(_returnDate)}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              DropdownButtonFormField<String>(
                initialValue: _reason,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'Reason',
                  border: OutlineInputBorder(),
                ),
                items: _reasons
                    .map(
                      (reason) => DropdownMenuItem(
                        value: reason,
                        child: Text(
                          reason,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    )
                    .toList(),
                onChanged: isLoading
                    ? null
                    : (v) => setState(() => _reason = v ?? 'Sick'),
              ),

              const SizedBox(height: 12),

              TextFormField(
                controller: _noteController,
                minLines: 3,
                maxLines: 5,
                maxLength: 1000,
                decoration: const InputDecoration(
                  labelText: 'Optional note',
                  hintText: 'Add any extra information for the school.',
                  border: OutlineInputBorder(),
                ),
              ),

              const SizedBox(height: 12),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: isLoading ? null : _submit,
                  icon: isLoading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.send),
                  label: Text(
                    isLoading ? 'Submitting...' : 'Submit Absence Notice',
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AbsenceNoticeCard extends StatelessWidget {
  final ParentAbsenceNoticeModel notice;

  const _AbsenceNoticeCard({required this.notice});

  Color get _statusColor {
    switch (notice.status) {
      case 'resolved':
        return Colors.green;
      case 'seen':
        return Colors.blue;
      default:
        return Colors.orange;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: _statusColor.withValues(alpha: 0.12),
          child: Icon(Icons.event_busy, color: _statusColor),
        ),
        title: Text(
          notice.studentName,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Text(
            '${notice.absenceDate} · ${notice.reason}'
            '${notice.expectedReturnDate == null ? '' : '\nExpected return: ${notice.expectedReturnDate}'}'
            '${notice.note == null || notice.note!.isEmpty ? '' : '\n${notice.note}'}',
          ),
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: _statusColor.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(999),
          ),
          child: Text(
            notice.statusLabel,
            style: TextStyle(
              color: _statusColor,
              fontWeight: FontWeight.bold,
              fontSize: 11,
            ),
          ),
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Icon(Icons.event_available, size: 42, color: Colors.grey.shade400),
            const SizedBox(height: 8),
            const Text('No absence notices submitted yet.'),
          ],
        ),
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(message),
          const SizedBox(height: 8),
          ElevatedButton(onPressed: onRetry, child: const Text('Retry')),
        ],
      ),
    );
  }
}
