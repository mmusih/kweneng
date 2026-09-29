import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:open_filex/open_filex.dart';

import '../providers/flutter_providers.dart';
import '../services/api_service.dart';
import '../utils/grade_utils.dart';
import '../widgets/portal_widgets.dart';

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
    midtermGrade: gradeForScore(_toDouble(j['midterm_score'])),
    endtermGrade: gradeForScore(_toDouble(j['endterm_score'])),
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
  final int? midtermPosition;
  final int? midtermClassSize;
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
    this.midtermPosition,
    this.midtermClassSize,
    this.trend,
  });

  bool get canDownloadReport =>
      termStatus == 'locked' || termStatus == 'finalized';

  String? get positionDisplay => endtermPosition != null
      ? '$endtermPosition${endtermClassSize != null ? "/$endtermClassSize" : ""}'
      : midtermPosition != null
      ? '$midtermPosition${midtermClassSize != null ? "/$midtermClassSize" : ""}'
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
    midtermPosition: _parsePos(j['midterm_position']),
    midtermClassSize: _parseClassSize(j['midterm_position']),
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

class MarksScreen extends ConsumerStatefulWidget {
  const MarksScreen({super.key});
  @override
  ConsumerState<MarksScreen> createState() => _MarksScreenState();
}

class _MarksScreenState extends ConsumerState<MarksScreen> {
  int? _termId;
  int? _termChildId;
  @override
  Widget build(BuildContext context) {
    final selectedId = ref.watch(selectedChildProvider);
    final dashboard = ref.watch(dashboardProvider).asData?.value;
    return Scaffold(
      appBar: AppBar(title: const Text('Academics')),
      body: ref
          .watch(marksProvider)
          .when(
            loading: () => const PortalLoading(),
            error: (_, _) => PortalError(
              title: 'Results could not be loaded',
              onRetry: () => ref.invalidate(marksProvider),
            ),
            data: (raw) {
              final children = (raw['children'] as List? ?? [])
                  .map(
                    (item) => MarksChild.fromJson(
                      Map<String, dynamic>.from(item as Map),
                    ),
                  )
                  .toList();
              if (children.isEmpty)
                return const _EmptyView(
                  message:
                      'No academic records yet. Published results will appear here.',
                );
              final child =
                  children.where((item) => item.id == selectedId).firstOrNull ??
                  children.first;
              final profile = dashboard?.children
                  .where((item) => item.id == child.id)
                  .firstOrNull;
              final term =
                  child.terms
                      .where(
                        (item) =>
                            item.termId ==
                            (_termChildId == child.id
                                ? _termId
                                : dashboard?.currentTerm?.id),
                      )
                      .firstOrNull ??
                  child.terms.lastOrNull;
              return RefreshIndicator(
                onRefresh: () async {
                  ref.invalidate(marksProvider);
                  await ref.read(marksProvider.future);
                },
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(20),
                  children: [
                    if (children.length > 1) ...[
                      ChildTabs(
                        children: children
                            .map(
                              (item) => ChildTabInfo(
                                id: item.id,
                                name: item.name,
                                className: item.className,
                                photo: dashboard?.children
                                    .where((profile) => profile.id == item.id)
                                    .firstOrNull
                                    ?.photo,
                              ),
                            )
                            .toList(),
                        selectedId: child.id,
                        onSelected: ref
                            .read(selectedChildProvider.notifier)
                            .select,
                      ),
                      const SizedBox(height: 18),
                    ],
                    PortalCard(
                      child: Row(
                        children: [
                          ChildAvatar(name: child.name, photo: profile?.photo),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  child.name,
                                  style: Theme.of(context).textTheme.titleLarge,
                                ),
                                Text(child.className ?? 'Class not assigned'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    if (child.isBlocked)
                      _BlockedBanner()
                    else ...[
                      if (term == null)
                        const PortalCard(
                          child: Text('No results have been recorded yet.'),
                        )
                      else ...[
                        DropdownButtonFormField<int>(
                          key: ValueKey('${child.id}-${term.termId}'),
                          initialValue: term.termId,
                          isExpanded: true,
                          decoration: InputDecoration(
                            labelText:
                                'Term · ${(raw['academic_year'] as Map?)?['year_name'] ?? ''}',
                          ),
                          items: child.terms
                              .map(
                                (item) => DropdownMenuItem(
                                  value: item.termId,
                                  child: Text(item.termName),
                                ),
                              )
                              .toList(),
                          onChanged: (id) => setState(() {
                            _termId = id;
                            _termChildId = child.id;
                          }),
                        ),
                        const SizedBox(height: 18),
                        _TermStatusBadge(
                          status: term.termStatus,
                          inverted: false,
                        ),
                        const SizedBox(height: 12),
                        PortalCard(
                          padding: EdgeInsets.zero,
                          child: _TermContent(childId: child.id, term: term),
                        ),
                      ],
                      const SizedBox(height: 18),
                      PortalCard(
                        padding: EdgeInsets.zero,
                        child: PortalLink(
                          icon: Icons.history_edu,
                          title: 'Full academic record',
                          subtitle: 'Results across academic years',
                          onTap: () => context.push(
                            '/children/${child.id}/academic-record',
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 18),
                    PortalCard(
                      padding: EdgeInsets.zero,
                      child: Column(
                        children: [
                          PortalLink(
                            icon: Icons.assignment_outlined,
                            title: 'Homework',
                            onTap: () => context.push('/homework'),
                          ),
                          PortalLink(
                            icon: Icons.schedule_outlined,
                            title: 'Timetable',
                            onTap: () => context.push('/timetable'),
                          ),
                          PortalLink(
                            icon: Icons.emoji_events_outlined,
                            title: 'Awards & leadership',
                            onTap: () => context.push('/awards'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
    );
  }
}

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
          Text(
            'Academic performance',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 12,
            runSpacing: 18,
            children: [
              PortalMetric(
                label: 'Midterm average',
                value: term.midtermAverage == null
                    ? 'Not recorded'
                    : '${term.midtermAverage!.toStringAsFixed(1)}%',
              ),
              PortalMetric(
                label: 'End-term average',
                value: term.endtermAverage == null
                    ? 'Not recorded'
                    : '${term.endtermAverage!.toStringAsFixed(1)}%',
              ),
              if (term.positionDisplay != null)
                PortalMetric(
                  label: 'Position in class',
                  value: term.positionDisplay!,
                ),
              if (term.trend != null && term.trend != 'N/A')
                PortalMetric(label: 'Performance trend', value: term.trend!),
            ],
          ),
          const SizedBox(height: 22),
          Text('Subjects', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 12), // Subject table or empty note
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
  String score(double? value, String? grade) => value == null
      ? 'Not entered'
      : '${value.toStringAsFixed(1)}%${grade == null ? "" : " · $grade"}';
  @override
  Widget build(BuildContext context) => Column(
    children: [
      for (final subject in subjects)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                subject.subject,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 20,
                runSpacing: 10,
                children: [
                  Text(
                    'Midterm: ${score(subject.midtermScore, subject.midtermGrade)}',
                  ),
                  Text(
                    'End-term: ${score(subject.endtermScore, subject.endtermGrade)}',
                  ),
                ],
              ),
              if ((subject.endtermScore ?? subject.midtermScore) != null) ...[
                const SizedBox(height: 12),
                Semantics(
                  label: '${subject.subject} latest score',
                  child: LinearProgressIndicator(
                    value:
                        ((subject.endtermScore ?? subject.midtermScore)! / 100)
                            .clamp(0, 1),
                    minHeight: 5,
                    borderRadius: BorderRadius.circular(4),
                    color: _kBrand,
                    backgroundColor: _kBrandLight,
                  ),
                ),
              ],
              const SizedBox(height: 14),
              const Divider(height: 1),
            ],
          ),
        ),
    ],
  );
}

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
              'Results access is restricted. '
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
