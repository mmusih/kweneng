import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/flutter_providers.dart';
import '../core/theme.dart';

class MarksScreen extends ConsumerWidget {
  const MarksScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final marksAsync = ref.watch(marksProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Marks & Results')),
      body: marksAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: ElevatedButton(
            onPressed: () => ref.invalidate(marksProvider),
            child: const Text('Retry'),
          ),
        ),
        data: (data) {
          final academicYear = data['academic_year'];
          final children = data['children'] as List? ?? [];

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(marksProvider),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Academic year header
                if (academicYear != null)
                  Container(
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: AppTheme.primary,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.school, color: Colors.white, size: 18),
                        const SizedBox(width: 8),
                        Text(
                          academicYear['year_name'] ?? '',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),

                // Children
                ...children.map((child) {
                  final isBlocked = child['is_blocked'] == true;
                  final terms = child['terms'] as List? ?? [];

                  return Card(
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Child header
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: const BoxDecoration(
                            color: Color(0xFFE8F1FA),
                            borderRadius: BorderRadius.vertical(
                              top: Radius.circular(12),
                            ),
                          ),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 18,
                                backgroundColor: AppTheme.primary,
                                child: Text(
                                  (child['name'] as String).substring(0, 1),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      child['name'] ?? '',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 14,
                                      ),
                                    ),
                                    Text(
                                      '${child['admission_no'] ?? ''} · ${child['class'] ?? 'N/A'}',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey.shade600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              if (isBlocked)
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFFCEBEB),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Text(
                                    'Blocked',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Color(0xFFA32D2D),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),

                        if (isBlocked)
                          const Padding(
                            padding: EdgeInsets.all(16),
                            child: Text(
                              'Results access is restricted due to an outstanding balance. Please contact the accounts office.',
                              style: TextStyle(
                                color: Color(0xFFA32D2D),
                                fontSize: 13,
                              ),
                            ),
                          )
                        else if (terms.isEmpty)
                          const Padding(
                            padding: EdgeInsets.all(16),
                            child: Text(
                              'No marks recorded yet.',
                              style: TextStyle(color: Colors.grey),
                            ),
                          )
                        else
                          ...terms.map((term) => _TermSection(term: term)),
                      ],
                    ),
                  );
                }),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _TermSection extends StatefulWidget {
  final Map<String, dynamic> term;
  const _TermSection({required this.term});

  @override
  State<_TermSection> createState() => _TermSectionState();
}

class _TermSectionState extends State<_TermSection> {
  bool _expanded = true;

  @override
  Widget build(BuildContext context) {
    final subjects = widget.term['subjects'] as List? ?? [];
    final midAvg = widget.term['midterm_average'];
    final endAvg = widget.term['endterm_average'];

    return Column(
      children: [
        // Term header
        InkWell(
          onTap: () => setState(() => _expanded = !_expanded),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Row(
              children: [
                Text(
                  widget.term['term_name'] ?? '',
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
                const Spacer(),
                if (midAvg != null)
                  Text(
                    'Mid: ${(midAvg as num).toStringAsFixed(1)}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                if (midAvg != null && endAvg != null)
                  const Text(' · ', style: TextStyle(color: Colors.grey)),
                if (endAvg != null)
                  Text(
                    'End: ${(endAvg as num).toStringAsFixed(1)}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                const SizedBox(width: 8),
                Icon(
                  _expanded ? Icons.expand_less : Icons.expand_more,
                  size: 18,
                  color: Colors.grey,
                ),
              ],
            ),
          ),
        ),

        if (_expanded && subjects.isNotEmpty)
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
            child: Table(
              columnWidths: const {
                0: FlexColumnWidth(3),
                1: FlexColumnWidth(1.5),
                2: FlexColumnWidth(1.5),
              },
              children: [
                // Header row
                TableRow(
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  children: const [
                    Padding(
                      padding: EdgeInsets.symmetric(vertical: 6, horizontal: 4),
                      child: Text(
                        'Subject',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: Colors.grey,
                        ),
                      ),
                    ),
                    Padding(
                      padding: EdgeInsets.symmetric(vertical: 6),
                      child: Text(
                        'Midterm',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: Colors.grey,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    Padding(
                      padding: EdgeInsets.symmetric(vertical: 6),
                      child: Text(
                        'Endterm',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: Colors.grey,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                ),
                // Subject rows
                ...subjects.map(
                  (s) => TableRow(
                    decoration: BoxDecoration(
                      border: Border(
                        bottom: BorderSide(color: Colors.grey.shade100),
                      ),
                    ),
                    children: [
                      Padding(
                        padding: const EdgeInsets.symmetric(
                          vertical: 8,
                          horizontal: 4,
                        ),
                        child: Text(
                          s['subject'] ?? '',
                          style: const TextStyle(fontSize: 13),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          s['midterm_score']?.toString() ?? '—',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                            color: _scoreColor(s['midterm_score']),
                          ),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          s['endterm_score']?.toString() ?? '—',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                            color: _scoreColor(s['endterm_score']),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        const Divider(height: 1),
      ],
    );
  }

  Color _scoreColor(dynamic score) {
    if (score == null) return Colors.grey;
    final s = (score as num).toDouble();
    if (s >= 70) return AppTheme.success;
    if (s >= 50) return AppTheme.warning;
    return AppTheme.danger;
  }
}
