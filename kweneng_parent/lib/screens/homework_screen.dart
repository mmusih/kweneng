import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class HomeworkScreen extends ConsumerWidget {
  const HomeworkScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(homeworkProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Homework'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: () {
              ref.invalidate(homeworkProvider);
              ref.invalidate(dashboardProvider);
            },
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _ErrorState(
          message:
              'Could not load homework. Please make sure the latest Laravel homework API update has been uploaded to the server.',
          onRetry: () => ref.invalidate(homeworkProvider),
        ),
        data: (data) {
          if (data.homework.isEmpty) {
            return const _EmptyHomework();
          }

          final groups = data.groupedByStudentSubject;

          return RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(homeworkProvider);
              ref.invalidate(dashboardProvider);
              await ref.read(homeworkProvider.future);
            },
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
              children: [
                _HomeworkSummaryCard(data: data),
                const SizedBox(height: 16),
                ...groups.entries.expand((studentEntry) {
                  return [
                    _StudentHomeworkHeader(studentName: studentEntry.key),
                    const SizedBox(height: 8),
                    ...studentEntry.value.entries.map(
                      (subjectEntry) => _SubjectThread(
                        subjectName: subjectEntry.key,
                        items: subjectEntry.value,
                      ),
                    ),
                    const SizedBox(height: 12),
                  ];
                }),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _HomeworkSummaryCard extends StatelessWidget {
  final ParentHomeworkData data;
  const _HomeworkSummaryCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final subjects = data.homework
        .map((h) => h.subjectName ?? 'General')
        .toSet()
        .length;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2563EB), Color(0xFF0EA5E9)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2563EB).withValues(alpha: 0.20),
            blurRadius: 14,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.assignment_outlined,
              color: Colors.white,
              size: 28,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Homework thread',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${data.homework.length} item${data.homework.length == 1 ? '' : 's'} across $subjects subject${subjects == 1 ? '' : 's'}',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.82),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          if (data.unreadCountBeforeOpen > 0)
            Badge.count(
              count: data.unreadCountBeforeOpen > 99
                  ? 99
                  : data.unreadCountBeforeOpen,
              backgroundColor: Colors.orange,
              textColor: Colors.white,
            ),
        ],
      ),
    );
  }
}

class _StudentHomeworkHeader extends StatelessWidget {
  final String studentName;
  const _StudentHomeworkHeader({required this.studentName});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        CircleAvatar(
          radius: 14,
          backgroundColor: AppTheme.primary.withValues(alpha: 0.12),
          child: Text(
            studentName.isEmpty
                ? 'S'
                : studentName.substring(0, 1).toUpperCase(),
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            studentName,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
          ),
        ),
      ],
    );
  }
}

class _SubjectThread extends StatelessWidget {
  final String subjectName;
  final List<ParentHomeworkItem> items;

  const _SubjectThread({required this.subjectName, required this.items});

  @override
  Widget build(BuildContext context) {
    final sorted = [...items]
      ..sort((a, b) {
        final ad = a.assignedDate ?? DateTime.fromMillisecondsSinceEpoch(0);
        final bd = b.assignedDate ?? DateTime.fromMillisecondsSinceEpoch(0);
        return bd.compareTo(ad);
      });

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: ExpansionTile(
        initiallyExpanded: true,
        tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        leading: Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: const Color(0xFFECFDF5),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.menu_book_outlined, color: Color(0xFF059669)),
        ),
        title: Text(
          subjectName,
          style: const TextStyle(fontWeight: FontWeight.w800),
        ),
        subtitle: Text(
          '${sorted.length} homework item${sorted.length == 1 ? '' : 's'}',
        ),
        children: sorted
            .map((item) => _HomeworkThreadItem(item: item))
            .toList(),
      ),
    );
  }
}

class _HomeworkThreadItem extends ConsumerWidget {
  final ParentHomeworkItem item;
  const _HomeworkThreadItem({required this.item});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dateFormat = DateFormat('d MMM yyyy');

    return Container(
      margin: const EdgeInsets.only(top: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: item.isUnread
            ? const Color(0xFFEFF6FF)
            : const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: item.isUnread
              ? const Color(0xFF93C5FD)
              : const Color(0xFFE5E7EB),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  item.title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              if (item.isUnread)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 7,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF2563EB),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: const Text(
                    'NEW',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [
              _MiniChip(
                icon: Icons.person_outline,
                text: item.teacherName ?? 'Teacher',
              ),
              if (item.assignedDate != null)
                _MiniChip(
                  icon: Icons.today_outlined,
                  text: dateFormat.format(item.assignedDate!),
                ),
              if (item.dueDate != null)
                _MiniChip(
                  icon: Icons.event_available_outlined,
                  text: 'Due ${dateFormat.format(item.dueDate!)}',
                  alert: item.isLate,
                ),
              _MiniChip(
                icon: Icons.task_alt_outlined,
                text: item.submissionStatusLabel,
              ),
            ],
          ),
          if ((item.description ?? '').isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              item.description!,
              style: TextStyle(color: Colors.grey.shade700, height: 1.35),
            ),
          ],
          if (item.totalMarks != null) ...[
            const SizedBox(height: 10),
            _MarksLine(item: item),
          ],
          if ((item.remarks ?? '').isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              'Teacher note: ${item.remarks}',
              style: const TextStyle(fontSize: 12, fontStyle: FontStyle.italic),
            ),
          ],
          const SizedBox(height: 10),
          _AttachmentArea(item: item),
        ],
      ),
    );
  }
}

class _AttachmentArea extends ConsumerWidget {
  final ParentHomeworkItem item;
  const _AttachmentArea({required this.item});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (item.attachmentRemoved) {
      return const _RemovedAttachmentNotice();
    }

    if (!item.hasAttachment || item.attachmentDownloadUrl == null) {
      return const SizedBox.shrink();
    }

    final api = ref.read(apiServiceProvider);

    return FutureBuilder<Map<String, String>>(
      future: api.authorizedHeaders(),
      builder: (context, snapshot) {
        final headers = snapshot.data;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: AspectRatio(
                aspectRatio: 4 / 3,
                child: headers == null
                    ? Container(
                        color: Colors.grey.shade200,
                        child: const Center(child: CircularProgressIndicator()),
                      )
                    : Image.network(
                        item.attachmentDownloadUrl!,
                        headers: headers,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => Container(
                          color: Colors.grey.shade200,
                          alignment: Alignment.center,
                          child: const Text('Attachment preview unavailable'),
                        ),
                      ),
              ),
            ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: () async {
                try {
                  final file = await api.downloadHomeworkAttachment(
                    item.id,
                    'homework_${item.id}.jpg',
                  );
                  if (context.mounted) await OpenFilex.open(file.path);
                } catch (_) {
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Could not open attachment.')),
                  );
                }
              },
              icon: const Icon(Icons.download_outlined),
              label: const Text('Open attachment'),
            ),
          ],
        );
      },
    );
  }
}

class _RemovedAttachmentNotice extends StatelessWidget {
  const _RemovedAttachmentNotice();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: const Row(
        children: [
          Icon(Icons.info_outline, size: 18, color: Color(0xFFD97706)),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              'The uploaded file was removed after term close to save storage. The homework record remains available.',
              style: TextStyle(fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}

class _MarksLine extends StatelessWidget {
  final ParentHomeworkItem item;
  const _MarksLine({required this.item});

  @override
  Widget build(BuildContext context) {
    final obtained = item.marksObtained?.toStringAsFixed(1) ?? '—';
    final total = item.totalMarks?.toStringAsFixed(1) ?? '—';
    final percentage = item.percentage != null
        ? ' (${item.percentage!.toStringAsFixed(1)}%)'
        : '';

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        children: [
          const Icon(Icons.grade_outlined, size: 18, color: Color(0xFF7C3AED)),
          const SizedBox(width: 8),
          Expanded(child: Text('Marks: $obtained / $total$percentage')),
          if ((item.grade ?? '').isNotEmpty)
            Text(
              item.grade!,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
        ],
      ),
    );
  }
}

class _MiniChip extends StatelessWidget {
  final IconData icon;
  final String text;
  final bool alert;

  const _MiniChip({required this.icon, required this.text, this.alert = false});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: alert ? const Color(0xFFFEF2F2) : Colors.white,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(
          color: alert ? const Color(0xFFFCA5A5) : const Color(0xFFE5E7EB),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 13,
            color: alert ? const Color(0xFFDC2626) : const Color(0xFF64748B),
          ),
          const SizedBox(width: 4),
          Text(
            text,
            style: TextStyle(
              fontSize: 11,
              color: alert ? const Color(0xFF991B1B) : const Color(0xFF475569),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyHomework extends StatelessWidget {
  const _EmptyHomework();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.assignment_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 12),
            const Text(
              'No homework yet',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            Text(
              'Homework sent by teachers will appear here by subject.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600),
            ),
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
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_outlined, size: 52, color: Colors.grey),
            const SizedBox(height: 12),
            Text(message, style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            FilledButton(onPressed: onRetry, child: const Text('Try again')),
          ],
        ),
      ),
    );
  }
}
