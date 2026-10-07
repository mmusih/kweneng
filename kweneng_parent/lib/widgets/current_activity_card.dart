import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

/// Match school-local time to today's published schedules, never a fallback day.
List<TimetableBlock> scheduledNow(TimetableData data, DateTime schoolTime) {
  final date = DateFormat('yyyy-MM-dd').format(schoolTime);
  if (data.date != date) return [];
  final minutes = schoolTime.hour * 60 + schoolTime.minute;
  int? parseTime(String time) {
    final parts = time.split(':');
    if (parts.length < 2) return null;
    final hour = int.tryParse(parts[0]);
    final minute = int.tryParse(parts[1]);
    if (hour == null ||
        minute == null ||
        hour < 0 ||
        hour > 23 ||
        minute < 0 ||
        minute > 59) {
      return null;
    }
    return hour * 60 + minute;
  }

  return [
    for (final schedule in data.schedules.isEmpty ? [data] : data.schedules)
      if (schedule.isPublished &&
          (schedule.date == null || schedule.date == date))
        for (final day in schedule.days)
          if (day.dayNumber == schedule.selectedDayNumber)
            for (final block in day.blocks)
              if (parseTime(block.startTime) != null &&
                  parseTime(block.endTime) != null &&
                  minutes >= parseTime(block.startTime)! &&
                  minutes < parseTime(block.endTime)!)
                block,
  ];
}

class CurrentActivityCard extends ConsumerStatefulWidget {
  final int childId;
  const CurrentActivityCard({super.key, required this.childId});
  @override
  ConsumerState<CurrentActivityCard> createState() =>
      _CurrentActivityCardState();
}

class _CurrentActivityCardState extends ConsumerState<CurrentActivityCard>
    with WidgetsBindingObserver {
  Timer? _timer;
  DateTime get schoolTime =>
      DateTime.now().toUtc().add(const Duration(hours: 2));
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _timer = Timer.periodic(const Duration(minutes: 1), (_) {
      ref.invalidate(timetableProvider(widget.childId));
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.invalidate(timetableProvider(widget.childId));
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final timetable = ref.watch(timetableProvider(widget.childId));
    final description = timetable.when(
      skipLoadingOnRefresh: false,
      loading: () => 'Checking timetable…',
      error: (_, _) => 'Timetable unavailable · Tap to retry',
      data: (data) {
        final blocks = scheduledNow(data, schoolTime);
        if (blocks.isNotEmpty) {
          return blocks
              .map(
                (block) =>
                    '${block.title} · ${block.startTime}–${block.endTime}',
              )
              .join(' / ');
        }
        if (!data.isPublished) return 'Timetable not published yet';
        if (data.date != DateFormat('yyyy-MM-dd').format(schoolTime)) {
          return 'Today’s timetable is unavailable';
        }
        return 'No activity scheduled at this time';
      },
    );
    return Semantics(
      button: true,
      label: 'View timetable. Scheduled now: $description',
      child: Material(
        color: const Color(0xFFE7F5F2),
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: () {
            if (timetable.hasError) {
              ref.invalidate(timetableProvider(widget.childId));
            }
            context.push('/timetable');
          },
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: [
                const Icon(
                  Icons.auto_stories_rounded,
                  size: 22,
                  color: Color(0xFF218B7D),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Scheduled now',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF17675F),
                        ),
                      ),
                      Text(
                        description,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 13,
                          color: Color(0xFF19574F),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                const Icon(
                  Icons.chevron_right,
                  size: 18,
                  color: Color(0xFF17675F),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
