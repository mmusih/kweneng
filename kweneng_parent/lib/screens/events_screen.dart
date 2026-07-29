import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:table_calendar/table_calendar.dart';
import '../providers/flutter_providers.dart';
import '../models/flutter_models.dart';
import '../core/theme.dart';

class EventsScreen extends ConsumerStatefulWidget {
  const EventsScreen({super.key});

  @override
  ConsumerState<EventsScreen> createState() => _EventsScreenState();
}

class _EventsScreenState extends ConsumerState<EventsScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabs;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final eventsAsync = ref.watch(eventsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Events'),
        bottom: TabBar(
          controller: _tabs,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(text: 'List'),
            Tab(text: 'Calendar'),
          ],
        ),
      ),
      body: eventsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off, size: 48, color: Colors.grey),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () => ref.invalidate(eventsProvider),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
        data: (data) {
          // Build event map for calendar markers
          final eventMap = <DateTime, List<EventModel>>{};
          for (final e in [...data.upcoming, ...data.past]) {
            final day = DateTime(
              e.startDatetime.year,
              e.startDatetime.month,
              e.startDatetime.day,
            );
            eventMap[day] = [...(eventMap[day] ?? []), e];
          }

          return TabBarView(
            controller: _tabs,
            children: [
              // ── List tab ──────────────────────────────────
              RefreshIndicator(
                onRefresh: () async => ref.invalidate(eventsProvider),
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (data.upcoming.isNotEmpty) ...[
                      const _ListHeader(title: 'Upcoming'),
                      ...data.upcoming.map((e) => _EventListTile(event: e)),
                    ],
                    if (data.past.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const _ListHeader(title: 'Past Events', muted: true),
                      ...data.past.map(
                        (e) => _EventListTile(event: e, muted: true),
                      ),
                    ],
                    if (data.upcoming.isEmpty && data.past.isEmpty)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: Text(
                            'No events found.',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ),
                      ),
                  ],
                ),
              ),

              // ── Calendar tab ──────────────────────────────
              RefreshIndicator(
                onRefresh: () async => ref.invalidate(eventsProvider),
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: Column(
                    children: [
                      TableCalendar<EventModel>(
                        firstDay: DateTime.now().subtract(
                          const Duration(days: 365),
                        ),
                        lastDay: DateTime.now().add(const Duration(days: 365)),
                        focusedDay: _focusedDay,
                        selectedDayPredicate: (d) => isSameDay(d, _selectedDay),
                        eventLoader: (day) {
                          final key = DateTime(day.year, day.month, day.day);
                          return eventMap[key] ?? [];
                        },
                        onDaySelected: (selected, focused) {
                          setState(() {
                            _selectedDay = selected;
                            _focusedDay = focused;
                          });
                        },
                        calendarStyle: CalendarStyle(
                          todayDecoration: BoxDecoration(
                            color: AppTheme.primaryLight.withValues(alpha: 0.4),
                            shape: BoxShape.circle,
                          ),
                          selectedDecoration: const BoxDecoration(
                            color: AppTheme.primary,
                            shape: BoxShape.circle,
                          ),
                          markerDecoration: const BoxDecoration(
                            color: Colors.purple,
                            shape: BoxShape.circle,
                          ),
                        ),
                        headerStyle: const HeaderStyle(
                          formatButtonVisible: false,
                          titleCentered: true,
                        ),
                      ),
                      // Events for selected day
                      if (_selectedDay != null) ...[
                        const Divider(),
                        Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 4,
                          ),
                          child: Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              'Events on ${_selectedDay!.day}/${_selectedDay!.month}/${_selectedDay!.year}',
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        ),
                        ...() {
                          final key = DateTime(
                            _selectedDay!.year,
                            _selectedDay!.month,
                            _selectedDay!.day,
                          );
                          final dayEvents = eventMap[key] ?? [];
                          if (dayEvents.isEmpty) {
                            return [
                              const Padding(
                                padding: EdgeInsets.all(16),
                                child: Text(
                                  'No events on this day.',
                                  style: TextStyle(color: Colors.grey),
                                ),
                              ),
                            ];
                          }
                          return dayEvents
                              .map((e) => _EventListTile(event: e))
                              .toList();
                        }(),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _ListHeader extends StatelessWidget {
  final String title;
  final bool muted;
  const _ListHeader({required this.title, this.muted = false});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.8,
          color: muted ? Colors.grey : AppTheme.primary,
        ),
      ),
    );
  }
}

class _EventListTile extends StatelessWidget {
  final EventModel event;
  final bool muted;
  const _EventListTile({required this.event, this.muted = false});

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: muted ? 0.6 : 1.0,
      child: Card(
        margin: const EdgeInsets.only(bottom: 8),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              // Date
              SizedBox(
                width: 40,
                child: Column(
                  children: [
                    Text(
                      _monthShort(event.startDatetime.month),
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: Colors.purple,
                      ),
                    ),
                    Text(
                      event.startDatetime.day.toString(),
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primary,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      event.title,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      event.isAllDay
                          ? 'All day'
                          : _formatTime(event.startDatetime),
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade500,
                      ),
                    ),
                    if (event.description != null &&
                        event.description!.isNotEmpty)
                      Text(
                        event.description!,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade400,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.purple.shade50,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  event.typeLabel,
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.purple.shade700,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _monthShort(int month) {
    const m = [
      'JAN',
      'FEB',
      'MAR',
      'APR',
      'MAY',
      'JUN',
      'JUL',
      'AUG',
      'SEP',
      'OCT',
      'NOV',
      'DEC',
    ];
    return m[month - 1];
  }

  String _formatTime(DateTime dt) {
    final h = dt.hour > 12 ? dt.hour - 12 : (dt.hour == 0 ? 12 : dt.hour);
    final min = dt.minute.toString().padLeft(2, '0');
    return '$h:$min ${dt.hour >= 12 ? 'PM' : 'AM'}';
  }
}
