import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../providers/app_providers.dart';
import '../widgets/app_widgets.dart';

class TimetableScreen extends ConsumerStatefulWidget {
  const TimetableScreen({super.key});

  @override
  ConsumerState<TimetableScreen> createState() => _TimetableScreenState();
}

class _TimetableScreenState extends ConsumerState<TimetableScreen> {
  int? selectedDay;

  @override
  Widget build(BuildContext context) {
    final timetable = ref.watch(teacherTimetableProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My timetable')),
      body: timetable.when(
        loading: () => const LoadingView(),
        error: (error, _) => ErrorView(
          message: 'Could not load your timetable. $error',
          onRetry: () => ref.invalidate(teacherTimetableProvider),
        ),
        data: _buildTimetable,
      ),
    );
  }

  Widget _buildTimetable(TimetableData data) {
    if (!data.isPublished) {
      return const _EmptyTimetable(
        message: 'The school has not published a timetable yet.',
      );
    }

    if (data.days.isEmpty) {
      return const _EmptyTimetable(
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
        ref.invalidate(teacherTimetableProvider);
        await ref.read(teacherTimetableProvider.future);
      },
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
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
                final selected = item.dayNumber == day.dayNumber;
                return ChoiceChip(
                  selected: selected,
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
        ? AppTheme.primary
        : block.isEvent
        ? AppTheme.warning
        : Colors.blueGrey;

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
                  if (block.isLesson) ...[
                    const SizedBox(height: 5),
                    Text(
                      [
                        block.className ?? block.group,
                        block.room,
                      ].whereType<String>().join(' • '),
                      style: TextStyle(color: Colors.grey.shade700),
                    ),
                    if (block.notes != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 5),
                        child: Text(
                          block.notes!,
                          style: const TextStyle(fontSize: 12),
                        ),
                      ),
                  ],
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

class _EmptyTimetable extends StatelessWidget {
  final String message;
  const _EmptyTimetable({required this.message});

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
