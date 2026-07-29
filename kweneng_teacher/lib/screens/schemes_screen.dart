import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../providers/app_providers.dart';
import '../widgets/app_widgets.dart';

class SchemesScreen extends ConsumerWidget {
  const SchemesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final schemes = ref.watch(schemesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Schemes of Work'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: () => ref.invalidate(schemesProvider),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: schemes.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load schemes. $error',
          onRetry: () => ref.invalidate(schemesProvider),
        ),
        data: (items) {
          if (items.isEmpty) {
            return RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(schemesProvider);
                await ref.read(schemesProvider.future);
              },
              child: ListView(
                children: const [
                  SizedBox(height: 120),
                  Icon(Icons.route_outlined, size: 54, color: Colors.grey),
                  SizedBox(height: 16),
                  Text(
                    'No active schemes available',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                  Padding(
                    padding: EdgeInsets.all(16),
                    child: Text(
                      'Create and submit a scheme on the web portal. Approved and submitted schemes will appear here.',
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(schemesProvider);
              await ref.read(schemesProvider.future);
            },
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) =>
                  _SchemeCard(scheme: items[index]),
            ),
          );
        },
      ),
    );
  }
}

class _SchemeCard extends StatelessWidget {
  const _SchemeCard({required this.scheme});

  final TeacherSchemeSummary scheme;

  @override
  Widget build(BuildContext context) {
    final pacingColor = _pacingColor(scheme.pacingStatus);

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => SchemeDetailScreen(schemeId: scheme.id),
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const CircleAvatar(
                    backgroundColor: Color(0xFFEDE9FE),
                    child: Icon(Icons.route_outlined, color: Color(0xFF7C3AED)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          scheme.title,
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          '${scheme.className} • ${scheme.subjectName}',
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: LinearProgressIndicator(
                      value: (scheme.overallPct / 100).clamp(0, 1),
                      minHeight: 8,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    '${scheme.overallPct.toStringAsFixed(0)}%',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _Label(text: _label(scheme.status), color: AppTheme.primary),
                  _Label(text: _label(scheme.pacingStatus), color: pacingColor),
                  _Label(
                    text: 'Expected ${scheme.expectedPct.toStringAsFixed(0)}%',
                    color: Colors.blueGrey,
                  ),
                ],
              ),
              if (scheme.lastProgressAt != null) ...[
                const SizedBox(height: 10),
                Text(
                  'Last updated ${DateFormat('d MMM y, HH:mm').format(scheme.lastProgressAt!.toLocal())}',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class SchemeDetailScreen extends ConsumerStatefulWidget {
  const SchemeDetailScreen({super.key, required this.schemeId});

  final int schemeId;

  @override
  ConsumerState<SchemeDetailScreen> createState() => _SchemeDetailScreenState();
}

class _SchemeDetailScreenState extends ConsumerState<SchemeDetailScreen> {
  final Set<int> _busyTopics = {};
  final Set<int> _busySubtopics = {};

  Future<void> _refresh() async {
    ref.invalidate(schemeDetailProvider(widget.schemeId));
    ref.invalidate(schemesProvider);
    await ref.read(schemeDetailProvider(widget.schemeId).future);
  }

  Future<void> _updateTopic(TeacherSchemeTopic topic, String status) async {
    setState(() => _busyTopics.add(topic.id));

    try {
      await ref
          .read(apiProvider)
          .updateSchemeTopicStatus(
            itemId: topic.id,
            status: status,
            teacherComment: topic.teacherComment,
          );
      await _refresh();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Topic progress updated.')),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not update topic. $error')),
        );
      }
    } finally {
      if (mounted) setState(() => _busyTopics.remove(topic.id));
    }
  }

  Future<void> _toggleSubtopic(TeacherSchemeSubtopic subtopic) async {
    setState(() => _busySubtopics.add(subtopic.id));

    try {
      await ref.read(apiProvider).toggleSchemeSubtopic(subtopic.id);
      await _refresh();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not update subtopic. $error')),
        );
      }
    } finally {
      if (mounted) setState(() => _busySubtopics.remove(subtopic.id));
    }
  }

  @override
  Widget build(BuildContext context) {
    final detail = ref.watch(schemeDetailProvider(widget.schemeId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Scheme Progress'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _refresh,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: detail.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load this scheme. $error',
          onRetry: _refresh,
        ),
        data: (scheme) => RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _SchemeHeader(summary: scheme.summary),
              const SizedBox(height: 16),
              if (scheme.terms.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(24),
                    child: Text(
                      'This scheme has no planned term or week topics yet.',
                      textAlign: TextAlign.center,
                    ),
                  ),
                )
              else
                ...scheme.terms.map(
                  (term) => _TermCard(
                    term: term,
                    busyTopics: _busyTopics,
                    busySubtopics: _busySubtopics,
                    onTopicStatusChanged: _updateTopic,
                    onSubtopicChanged: _toggleSubtopic,
                  ),
                ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}

class _SchemeHeader extends StatelessWidget {
  const _SchemeHeader({required this.summary});

  final TeacherSchemeSummary summary;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppTheme.primaryDark, Color(0xFF7C3AED)],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            summary.title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            '${summary.className} • ${summary.subjectName}',
            style: TextStyle(color: Colors.white.withValues(alpha: 0.8)),
          ),
          const SizedBox(height: 18),
          LinearProgressIndicator(
            value: (summary.overallPct / 100).clamp(0, 1),
            minHeight: 9,
            borderRadius: BorderRadius.circular(999),
            backgroundColor: Colors.white.withValues(alpha: 0.2),
            color: Colors.white,
          ),
          const SizedBox(height: 8),
          Text(
            '${summary.overallPct.toStringAsFixed(0)}% complete • ${summary.expectedPct.toStringAsFixed(0)}% expected',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _TermCard extends StatelessWidget {
  const _TermCard({
    required this.term,
    required this.busyTopics,
    required this.busySubtopics,
    required this.onTopicStatusChanged,
    required this.onSubtopicChanged,
  });

  final TeacherSchemeTerm term;
  final Set<int> busyTopics;
  final Set<int> busySubtopics;
  final Future<void> Function(TeacherSchemeTopic, String) onTopicStatusChanged;
  final Future<void> Function(TeacherSchemeSubtopic) onSubtopicChanged;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 14),
      child: ExpansionTile(
        initiallyExpanded: true,
        title: Text(
          term.name,
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
        subtitle: Text(
          '${term.weeks.length} planned week${term.weeks.length == 1 ? '' : 's'}',
        ),
        children: term.weeks
            .map(
              (week) => Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Text(
                        'Week ${week.week}',
                        style: const TextStyle(
                          color: AppTheme.primary,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    ...week.topics.map(
                      (topic) => _TopicCard(
                        topic: topic,
                        busy: busyTopics.contains(topic.id),
                        busySubtopics: busySubtopics,
                        onStatusChanged: (status) =>
                            onTopicStatusChanged(topic, status),
                        onSubtopicChanged: onSubtopicChanged,
                      ),
                    ),
                  ],
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}

class _TopicCard extends StatelessWidget {
  const _TopicCard({
    required this.topic,
    required this.busy,
    required this.busySubtopics,
    required this.onStatusChanged,
    required this.onSubtopicChanged,
  });

  static const statuses = <String, String>{
    'not_started': 'Not started',
    'in_progress': 'In progress',
    'completed': 'Completed',
    'moved': 'Moved',
    'skipped': 'Skipped',
    'needs_reteaching': 'Needs reteaching',
  };

  final TeacherSchemeTopic topic;
  final bool busy;
  final Set<int> busySubtopics;
  final ValueChanged<String> onStatusChanged;
  final Future<void> Function(TeacherSchemeSubtopic) onSubtopicChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: topic.isBehind
              ? const Color(0xFFFCA5A5)
              : const Color(0xFFE2E8F0),
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
                  topic.title,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
              if (busy)
                const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              else
                PopupMenuButton<String>(
                  tooltip: 'Change topic status',
                  onSelected: onStatusChanged,
                  itemBuilder: (_) => statuses.entries
                      .map(
                        (entry) => PopupMenuItem(
                          value: entry.key,
                          child: Row(
                            children: [
                              if (entry.key == topic.status)
                                const Icon(Icons.check, size: 18)
                              else
                                const SizedBox(width: 18),
                              const SizedBox(width: 8),
                              Text(entry.value),
                            ],
                          ),
                        ),
                      )
                      .toList(),
                  child: _Label(
                    text: statuses[topic.status] ?? _label(topic.status),
                    color: _statusColor(topic.status),
                  ),
                ),
            ],
          ),
          if (topic.isBehind) ...[
            const SizedBox(height: 8),
            const Text(
              'Behind planned schedule',
              style: TextStyle(
                color: AppTheme.danger,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
          if ((topic.teacherComment ?? '').isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              topic.teacherComment!,
              style: TextStyle(color: Colors.grey.shade600),
            ),
          ],
          if (topic.subtopics.isNotEmpty) ...[
            const Divider(height: 22),
            ...topic.subtopics.map(
              (subtopic) => CheckboxListTile(
                value: subtopic.isCompleted,
                dense: true,
                contentPadding: EdgeInsets.zero,
                controlAffinity: ListTileControlAffinity.leading,
                title: Text(subtopic.title),
                secondary: busySubtopics.contains(subtopic.id)
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : null,
                onChanged: busySubtopics.contains(subtopic.id)
                    ? null
                    : (_) => onSubtopicChanged(subtopic),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _Label extends StatelessWidget {
  const _Label({required this.text, required this.color});

  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Text(
        text,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

String _label(String value) {
  return value
      .split('_')
      .map(
        (word) => word.isEmpty
            ? word
            : '${word[0].toUpperCase()}${word.substring(1)}',
      )
      .join(' ');
}

Color _pacingColor(String status) {
  return switch (status) {
    'ahead' => const Color(0xFF2563EB),
    'on_track' => AppTheme.success,
    'behind' => AppTheme.warning,
    'critical' => AppTheme.danger,
    _ => Colors.blueGrey,
  };
}

Color _statusColor(String status) {
  return switch (status) {
    'completed' => AppTheme.success,
    'in_progress' => AppTheme.primary,
    'needs_reteaching' => AppTheme.danger,
    'skipped' || 'moved' => AppTheme.warning,
    _ => Colors.blueGrey,
  };
}
