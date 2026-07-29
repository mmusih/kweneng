import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class TimetableScreen extends ConsumerStatefulWidget {
  const TimetableScreen({super.key});

  @override
  ConsumerState<TimetableScreen> createState() => _TimetableScreenState();
}

class _TimetableScreenState extends ConsumerState<TimetableScreen> {
  int? selectedStudentId;
  int? selectedDay;

  @override
  Widget build(BuildContext context) {
    final dashboard = ref.watch(dashboardProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Timetable')),
      body: dashboard.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _ErrorView(
          message: 'Could not load your children. $error',
          onRetry: () => ref.invalidate(dashboardProvider),
        ),
        data: (data) {
          if (data.children.isEmpty) {
            return const _EmptyView(
              message: 'No children are linked to this account.',
            );
          }

          final childId = selectedStudentId ?? data.children.first.id;
          final child = data.children.firstWhere(
            (item) => item.id == childId,
            orElse: () => data.children.first,
          );
          final timetable = ref.watch(timetableProvider(child.id));

          return Column(
            children: [
              Container(
                color: Colors.white,
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                child: DropdownButtonFormField<int>(
                  initialValue: child.id,
                  decoration: const InputDecoration(
                    labelText: 'Child',
                    prefixIcon: Icon(Icons.person_outline),
                    border: OutlineInputBorder(),
                  ),
                  items: data.children
                      .map(
                        (item) => DropdownMenuItem(
                          value: item.id,
                          child: Text(
                            '${item.name}${item.className == null ? '' : ' • ${item.className}'}',
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() {
                    selectedStudentId = value;
                    selectedDay = null;
                  }),
                ),
              ),
              Expanded(
                child: timetable.when(
                  loading: () =>
                      const Center(child: CircularProgressIndicator()),
                  error: (error, _) => _ErrorView(
                    message: 'Could not load the timetable. $error',
                    onRetry: () => ref.invalidate(timetableProvider(child.id)),
                  ),
                  data: (value) => _buildTimetable(value, child.id),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildTimetable(TimetableData data, int studentId) {
    if (!data.isPublished) {
      return const _EmptyView(
        message: 'The school has not published a timetable yet.',
      );
    }

    if (data.days.isEmpty) {
      return const _EmptyView(
        message: 'No timetable days have been configured.',
      );
    }

    final activeDay =
        selectedDay ?? data.selectedDayNumber ?? data.days.first.dayNumber;
    final day = data.days.firstWhere(
      (item) => item.dayNumber == activeDay,
      orElse: () => data.days.first,
    );

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(timetableProvider(studentId));
        await ref.read(timetableProvider(studentId).future);
      },
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 110),
        children: [
          Text(
            data.templateName!,
            style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w900),
          ),
          if (data.academicYear != null)
            Text(
              data.academicYear!,
              style: const TextStyle(color: Colors.grey),
            ),
          const SizedBox(height: 16),
          SizedBox(
            height: 42,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: data.days.length,
              separatorBuilder: (_, _) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                final item = data.days[index];
                return ChoiceChip(
                  selected: item.dayNumber == day.dayNumber,
                  label: Text(item.name),
                  onSelected: (_) =>
                      setState(() => selectedDay = item.dayNumber),
                );
              },
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: Text(
                  day.name,
                  style: const TextStyle(
                    fontSize: 19,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              if (data.selectedDayNumber == day.dayNumber)
                const Chip(label: Text('Today')),
            ],
          ),
          const SizedBox(height: 8),
          if (day.blocks.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 30),
              child: Center(child: Text('No periods configured for this day.')),
            )
          else
            ...day.blocks.map((block) => _BlockCard(block: block)),
        ],
      ),
    );
  }
}

class _BlockCard extends StatelessWidget {
  final TimetableBlock block;
  const _BlockCard({required this.block});

  @override
  Widget build(BuildContext context) {
    final color = block.isLesson
        ? const Color(0xFF0284C7)
        : block.isEvent
        ? const Color(0xFFD97706)
        : const Color(0xFF64748B);

    final details = [
      if (block.teacher != null) block.teacher!,
      if (block.group != null) block.group!,
      if (block.room != null) block.room!,
    ].join(' • ');

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 62,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    block.startTime,
                    style: const TextStyle(fontWeight: FontWeight.w900),
                  ),
                  Text(
                    block.endTime,
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ],
              ),
            ),
            Container(width: 3, height: 70, color: color),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    block.periodName.toUpperCase(),
                    style: TextStyle(
                      color: color,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    block.title,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  if (details.isNotEmpty) ...[
                    const SizedBox(height: 5),
                    Text(
                      details,
                      style: TextStyle(color: Colors.grey.shade700),
                    ),
                  ],
                  if (block.notes != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 5),
                      child: Text(
                        block.notes!,
                        style: const TextStyle(fontSize: 12),
                      ),
                    ),
                ],
              ),
            ),
            Text(
              '${block.durationMinutes}m',
              style: const TextStyle(fontSize: 11, color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final String message;
  const _EmptyView({required this.message});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.calendar_view_week_outlined,
              size: 56,
              color: Colors.grey,
            ),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(onPressed: onRetry, child: const Text('Try again')),
          ],
        ),
      ),
    );
  }
}
