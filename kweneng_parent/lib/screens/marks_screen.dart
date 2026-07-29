import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';

import '../providers/flutter_providers.dart';
import '../services/api_service.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Constants
// ─────────────────────────────────────────────────────────────────────────────

const _kBrand = Color(0xFF2C3E6B);
const _kBrandLight = Color(0xFFE8ECF5);

double? _toDouble(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value.trim());
  return null;
}

int? _toInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value.trim());
  return null;
}

String? _toStringOrNull(dynamic value) {
  if (value == null) return null;
  final text = value.toString();
  return text.isEmpty ? null : text;
}

bool _toBool(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  if (value is String) {
    final text = value.trim().toLowerCase();
    return text == 'true' || text == '1' || text == 'yes';
  }
  return false;
}

// ─────────────────────────────────────────────────────────────────────────────
// Models  — shaped to match the actual API response from index()
//
// API shape (confirmed from debug output):
// {
//   academic_year: { id, year_name },
//   children: [
//     {
//       id, name, admission_no, class, is_blocked,
//       terms: [
//         {
//           term_id, term_name,
//           subjects: [{ subject, midterm_score, endterm_score, ... }],
//           midterm_average, endterm_average,
//           midterm_position, endterm_position, trend
//         }
//       ]
//     }
//   ]
// }
// ─────────────────────────────────────────────────────────────────────────────

class MarksSubject {
  final String subject;
  final double? midtermScore;
  final double? endtermScore;
  final String? midtermGrade;
  final String? endtermGrade;

  const MarksSubject({
    required this.subject,
    this.midtermScore,
    this.endtermScore,
    this.midtermGrade,
    this.endtermGrade,
  });

  factory MarksSubject.fromJson(Map<String, dynamic> j) => MarksSubject(
    subject: j['subject'] as String? ?? 'Unknown',
    midtermScore: _toDouble(j['midterm_score']),
    endtermScore: _toDouble(j['endterm_score']),
    midtermGrade: _toStringOrNull(j['midterm_grade']),
    endtermGrade: _toStringOrNull(j['endterm_grade']),
  );
}

int? _parsePos(dynamic raw) {
  if (raw == null) return null;
  if (raw is Map) return _toInt(raw['position']);
  return _toInt(raw);
}

int? _parseClassSize(dynamic raw) {
  if (raw is Map) return _toInt(raw['class_size']);
  return null;
}

// One term with subjects already embedded — everything comes from index().
// No second network call needed.
class MarksTerm {
  final int termId;
  final String termName;
  final String? termStatus;
  final List<MarksSubject> subjects;
  final double? midtermAverage;
  final double? endtermAverage;
  final int? endtermPosition;
  final int? endtermClassSize;
  final String? trend;

  const MarksTerm({
    required this.termId,
    required this.termName,
    this.termStatus,
    required this.subjects,
    this.midtermAverage,
    this.endtermAverage,
    this.endtermPosition,
    this.endtermClassSize,
    this.trend,
  });

  bool get canDownloadReport =>
      termStatus == 'locked' || termStatus == 'finalized';

  String? get positionDisplay => endtermPosition != null
      ? '$endtermPosition${endtermClassSize != null ? "/$endtermClassSize" : ""}'
      : null;

  factory MarksTerm.fromJson(Map<String, dynamic> j) => MarksTerm(
    termId: _toInt(j['term_id']) ?? 0,
    termName: _toStringOrNull(j['term_name']) ?? 'Term',
    termStatus: _toStringOrNull(j['term_status']),
    subjects: (j['subjects'] as List? ?? [])
        .map((s) => MarksSubject.fromJson(s as Map<String, dynamic>))
        .toList(),
    midtermAverage: _toDouble(j['midterm_average']),
    endtermAverage: _toDouble(j['endterm_average']),
    endtermPosition: _parsePos(j['endterm_position']),
    endtermClassSize: _parseClassSize(j['endterm_position']),
    trend: _toStringOrNull(j['trend']),
  );
}

class MarksChild {
  final int id;
  final String name;
  final String admissionNo;
  final String? className;
  final bool isBlocked;
  final List<MarksTerm> terms;

  const MarksChild({
    required this.id,
    required this.name,
    required this.admissionNo,
    this.className,
    required this.isBlocked,
    required this.terms,
  });

  factory MarksChild.fromJson(Map<String, dynamic> j) => MarksChild(
    id: _toInt(j['id']) ?? 0,
    name: _toStringOrNull(j['name']) ?? 'Unknown',
    admissionNo: _toStringOrNull(j['admission_no']) ?? '',
    className: _toStringOrNull(j['class']),
    isBlocked: _toBool(j['is_blocked']),
    terms: (j['terms'] as List? ?? [])
        .map((t) => MarksTerm.fromJson(t as Map<String, dynamic>))
        .toList(),
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Download state
// ─────────────────────────────────────────────────────────────────────────────

enum _DlStatus { idle, downloading, done, error }

class _DlState {
  final _DlStatus status;
  final String? filePath;
  final String? errorMsg;
  const _DlState({this.status = _DlStatus.idle, this.filePath, this.errorMsg});
}

class _DownloadNotifier extends Notifier<Map<String, _DlState>> {
  @override
  Map<String, _DlState> build() => <String, _DlState>{};
  void set(String key, _DlState value) => state = {...state, key: value};
}

final _downloadStateProvider =
    NotifierProvider<_DownloadNotifier, Map<String, _DlState>>(
      _DownloadNotifier.new,
    );

// ─────────────────────────────────────────────────────────────────────────────
// Root screen
// ─────────────────────────────────────────────────────────────────────────────

class MarksScreen extends ConsumerWidget {
  const MarksScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final marksAsync = ref.watch(marksProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text('Marks & Report Cards'),
        backgroundColor: _kBrand,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: marksAsync.when(
        loading: () =>
            const Center(child: CircularProgressIndicator(color: _kBrand)),
        error: (e, _) => _ErrorView(
          message: e.toString(),
          onRetry: () => ref.invalidate(marksProvider),
        ),
        data: (raw) {
          final children = _parseChildren(raw);
          if (children.isEmpty) {
            return const _EmptyView(message: 'No marks data available.');
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
            itemCount: children.length,
            itemBuilder: (_, i) => _ChildCard(child: children[i]),
          );
        },
      ),
    );
  }

  List<MarksChild> _parseChildren(Map<String, dynamic> raw) {
    try {
      final list = raw['children'] as List? ?? [];
      return list
          .whereType<Map<String, dynamic>>()
          .map(MarksChild.fromJson)
          .toList();
    } catch (e) {
      debugPrint('=== _parseChildren error: $e');
      return [];
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Per-child card
// ─────────────────────────────────────────────────────────────────────────────

class _ChildCard extends StatelessWidget {
  final MarksChild child;
  const _ChildCard({required this.child});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 20),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Child header
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: _kBrand,
                  child: Text(
                    child.name.isNotEmpty ? child.name[0].toUpperCase() : '?',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 20,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        child.name,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      if (child.className != null)
                        Text(
                          child.className!,
                          style: const TextStyle(
                            fontSize: 13,
                            color: Colors.grey,
                          ),
                        ),
                      Text(
                        child.admissionNo,
                        style: const TextStyle(
                          fontSize: 12,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),

            const SizedBox(height: 14),

            if (child.isBlocked)
              _BlockedBanner()
            else if (child.terms.isEmpty)
              const _EmptyView(message: 'No marks recorded yet.')
            else
              _TermList(childId: child.id, terms: child.terms),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// List of term tiles for one child
// ─────────────────────────────────────────────────────────────────────────────

class _TermList extends StatelessWidget {
  final int childId;
  final List<MarksTerm> terms;
  const _TermList({required this.childId, required this.terms});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Select a Term',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.grey,
          ),
        ),
        const SizedBox(height: 8),
        ...terms.map((term) => _TermTile(childId: childId, term: term)),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Term tile — taps to expand, shows subject table inline
// ─────────────────────────────────────────────────────────────────────────────

class _TermTile extends StatefulWidget {
  final int childId;
  final MarksTerm term;
  const _TermTile({required this.childId, required this.term});

  @override
  State<_TermTile> createState() => _TermTileState();
}

class _TermTileState extends State<_TermTile> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final hasSubjects = widget.term.subjects.isNotEmpty;

    return Column(
      children: [
        // Header row
        GestureDetector(
          onTap: () => setState(() => _expanded = !_expanded),
          child: Container(
            margin: const EdgeInsets.only(bottom: 8),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: _expanded ? _kBrand : _kBrandLight,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.assignment_outlined,
                  size: 18,
                  color: _expanded ? Colors.white : _kBrand,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    widget.term.termName,
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                      color: _expanded ? Colors.white : _kBrand,
                    ),
                  ),
                ),
                if (!hasSubjects)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: _expanded ? Colors.white24 : Colors.grey.shade200,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      'No marks',
                      style: TextStyle(
                        fontSize: 11,
                        color: _expanded ? Colors.white : Colors.grey.shade600,
                      ),
                    ),
                  )
                else
                  _TermStatusBadge(
                    status: widget.term.termStatus,
                    inverted: _expanded,
                  ),
                const SizedBox(width: 8),
                Icon(
                  _expanded
                      ? Icons.keyboard_arrow_up
                      : Icons.keyboard_arrow_down,
                  color: _expanded ? Colors.white : _kBrand,
                  size: 20,
                ),
              ],
            ),
          ),
        ),

        // Expanded content
        if (_expanded)
          Container(
            margin: const EdgeInsets.only(bottom: 12),
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey.shade200),
              borderRadius: BorderRadius.circular(10),
            ),
            child: _TermContent(childId: widget.childId, term: widget.term),
          ),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Term content: summary chips + subject table + download button
// All data already available — no extra network call needed.
// ─────────────────────────────────────────────────────────────────────────────

class _TermContent extends ConsumerWidget {
  final int childId;
  final MarksTerm term;
  const _TermContent({required this.childId, required this.term});

  String get _dlKey => '${childId}_${term.termId}';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dl = ref.watch(_downloadStateProvider)[_dlKey] ?? const _DlState();

    return Padding(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Summary chips
          if (term.subjects.isNotEmpty)
            Wrap(
              spacing: 10,
              runSpacing: 6,
              children: [
                if (term.endtermAverage != null)
                  _Chip(
                    label: 'Avg',
                    value: '${term.endtermAverage!.toStringAsFixed(0)}%',
                    color: _gradeColor(term.endtermAverage!),
                  )
                else if (term.midtermAverage != null)
                  _Chip(
                    label: 'Midterm Avg',
                    value: '${term.midtermAverage!.toStringAsFixed(0)}%',
                    color: _gradeColor(term.midtermAverage!),
                  ),
                if (term.positionDisplay != null)
                  _Chip(
                    label: 'Position',
                    value: term.positionDisplay!,
                    color: _kBrand,
                  ),
                if (term.trend != null)
                  _Chip(
                    label: 'Trend',
                    value: term.trend!,
                    color: Colors.grey.shade700,
                  ),
              ],
            ),

          if (term.subjects.isNotEmpty) const SizedBox(height: 14),

          // Subject table or empty note
          if (term.subjects.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'No marks have been entered for this term yet.',
                style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
              ),
            )
          else
            _SubjectTable(subjects: term.subjects),

          const SizedBox(height: 16),

          // Download button or pending message
          if (term.canDownloadReport)
            _ReportCardButton(
              dlState: dl,
              onDownload: () => _download(ref),
              onOpen: () => OpenFilex.open(dl.filePath!),
            )
          else
            Row(
              children: [
                Icon(
                  Icons.lock_clock_outlined,
                  size: 16,
                  color: Colors.grey.shade400,
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    term.subjects.isEmpty
                        ? 'Report card will be available once marks are entered and the term is finalised.'
                        : 'Report card will be available once the term is finalised.',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                ),
              ],
            ),

          if (dl.status == _DlStatus.error && dl.errorMsg != null)
            Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Text(
                dl.errorMsg!,
                style: const TextStyle(color: Colors.red, fontSize: 12),
              ),
            ),
        ],
      ),
    );
  }

  Color _gradeColor(double avg) {
    if (avg >= 80) return Colors.green.shade700;
    if (avg >= 60) return Colors.orange.shade700;
    return Colors.red.shade700;
  }

  Future<void> _download(WidgetRef ref) async {
    final notifier = ref.read(_downloadStateProvider.notifier);
    notifier.set(_dlKey, const _DlState(status: _DlStatus.downloading));
    try {
      final file = await ApiService().downloadReportCard(childId, term.termId);
      notifier.set(
        _dlKey,
        _DlState(status: _DlStatus.done, filePath: file.path),
      );
    } catch (e) {
      notifier.set(
        _dlKey,
        _DlState(status: _DlStatus.error, errorMsg: 'Download failed: $e'),
      );
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Subject table
// ─────────────────────────────────────────────────────────────────────────────

class _SubjectTable extends StatelessWidget {
  final List<MarksSubject> subjects;
  const _SubjectTable({required this.subjects});

  static String _fmt(double? score, String? grade) {
    if (score == null) return '—';
    final s = score.toStringAsFixed(0);
    return grade != null ? '$s ($grade)' : s;
  }

  @override
  Widget build(BuildContext context) {
    return Table(
      columnWidths: const {
        0: FlexColumnWidth(3),
        1: FlexColumnWidth(2),
        2: FlexColumnWidth(2),
      },
      border: TableBorder.all(color: Colors.grey.shade200),
      children: [
        TableRow(
          decoration: const BoxDecoration(color: _kBrandLight),
          children: const [_TH('Subject'), _TH('Midterm'), _TH('Endterm')],
        ),
        for (var i = 0; i < subjects.length; i++)
          TableRow(
            decoration: BoxDecoration(
              color: i.isEven ? Colors.white : const Color(0xFFF8FAFC),
            ),
            children: [
              _TD(subjects[i].subject),
              _TD(_fmt(subjects[i].midtermScore, subjects[i].midtermGrade)),
              _TD(_fmt(subjects[i].endtermScore, subjects[i].endtermGrade)),
            ],
          ),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Term status badge
// ─────────────────────────────────────────────────────────────────────────────

class _TermStatusBadge extends StatelessWidget {
  final String? status;
  final bool inverted;
  const _TermStatusBadge({this.status, required this.inverted});

  @override
  Widget build(BuildContext context) {
    if (status == null) return const SizedBox.shrink();

    late final Color bg;
    late final Color text;
    late final String label;

    switch (status) {
      case 'finalized':
        bg = inverted ? Colors.green.shade200 : Colors.green.shade100;
        text = Colors.green.shade800;
        label = 'Finalised';
        break;
      case 'locked':
        bg = inverted ? Colors.orange.shade200 : Colors.orange.shade100;
        text = Colors.orange.shade800;
        label = 'Locked';
        break;
      case 'open':
        bg = inverted ? Colors.blue.shade200 : Colors.blue.shade100;
        text = Colors.blue.shade800;
        label = 'Open';
        break;
      default:
        bg = inverted ? Colors.grey.shade300 : Colors.grey.shade200;
        text = Colors.grey.shade700;
        label = status!;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: text,
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Download button
// ─────────────────────────────────────────────────────────────────────────────

class _ReportCardButton extends StatelessWidget {
  final _DlState dlState;
  final VoidCallback onDownload;
  final VoidCallback onOpen;

  const _ReportCardButton({
    required this.dlState,
    required this.onDownload,
    required this.onOpen,
  });

  @override
  Widget build(BuildContext context) {
    switch (dlState.status) {
      case _DlStatus.downloading:
        return OutlinedButton.icon(
          onPressed: null,
          icon: const SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          label: const Text('Downloading…'),
        );
      case _DlStatus.done:
        return Wrap(
          spacing: 10,
          runSpacing: 8,
          children: [
            FilledButton.icon(
              onPressed: onOpen,
              icon: const Icon(Icons.picture_as_pdf, size: 18),
              label: const Text('Open Report Card'),
              style: FilledButton.styleFrom(backgroundColor: _kBrand),
            ),
            TextButton(onPressed: onDownload, child: const Text('Re-download')),
          ],
        );
      case _DlStatus.error:
        return FilledButton.icon(
          onPressed: onDownload,
          icon: const Icon(Icons.refresh, size: 18),
          label: const Text('Retry Download'),
          style: FilledButton.styleFrom(backgroundColor: Colors.red.shade700),
        );
      case _DlStatus.idle:
        return OutlinedButton.icon(
          onPressed: onDownload,
          icon: const Icon(Icons.download, size: 18, color: _kBrand),
          label: const Text(
            'Download Report Card',
            style: TextStyle(color: _kBrand),
          ),
          style: OutlinedButton.styleFrom(
            side: const BorderSide(color: _kBrand),
          ),
        );
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Small reusable widgets
// ─────────────────────────────────────────────────────────────────────────────

class _BlockedBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.red.shade200),
      ),
      child: Row(
        children: [
          Icon(Icons.lock_outline, size: 18, color: Colors.red.shade700),
          const SizedBox(width: 8),
          const Expanded(
            child: Text(
              'Results are restricted due to an outstanding balance. '
              'Please contact the accounts office.',
              style: TextStyle(fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final String message;
  const _EmptyView({required this.message});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Text(
        message,
        style: const TextStyle(color: Colors.grey, fontSize: 13),
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
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.error_outline, size: 48, color: Colors.red),
          const SizedBox(height: 12),
          Text(
            'Failed to load marks\n$message',
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 13),
          ),
          const SizedBox(height: 12),
          ElevatedButton(onPressed: onRetry, child: const Text('Retry')),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  const _Chip({required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontSize: 12, color: Colors.black87),
          children: [
            TextSpan(
              text: '$label  ',
              style: const TextStyle(color: Colors.grey),
            ),
            TextSpan(
              text: value,
              style: TextStyle(fontWeight: FontWeight.bold, color: color),
            ),
          ],
        ),
      ),
    );
  }
}

class _TH extends StatelessWidget {
  final String text;
  const _TH(this.text);
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 7),
    child: Text(
      text,
      style: const TextStyle(
        fontWeight: FontWeight.bold,
        fontSize: 12,
        color: _kBrand,
      ),
    ),
  );
}

class _TD extends StatelessWidget {
  final String text;
  const _TD(this.text);
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
    child: Text(text, style: const TextStyle(fontSize: 12)),
  );
}
