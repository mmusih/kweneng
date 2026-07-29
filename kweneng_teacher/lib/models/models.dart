class TeacherUser {
  final int id;
  final int teacherId;
  final String name;
  final String email;

  const TeacherUser({
    required this.id,
    required this.teacherId,
    required this.name,
    required this.email,
  });

  factory TeacherUser.fromJson(Map<String, dynamic> j) => TeacherUser(
    id: _int(j['id']),
    teacherId: _int(j['teacher_id']),
    name: j['name']?.toString() ?? 'Teacher',
    email: j['email']?.toString() ?? '',
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'teacher_id': teacherId,
    'name': name,
    'email': email,
  };
}

class AcademicYearInfo {
  final int id;
  final String name;
  final String? status;

  const AcademicYearInfo({required this.id, required this.name, this.status});

  factory AcademicYearInfo.fromJson(Map<String, dynamic> j) => AcademicYearInfo(
    id: _int(j['id']),
    name: j['name']?.toString() ?? j['year_name']?.toString() ?? '',
    status: j['status']?.toString(),
  );
}

class TermInfo {
  final int id;
  final String name;
  final bool locked;
  final bool midtermLocked;
  final bool endtermLocked;

  const TermInfo({
    required this.id,
    required this.name,
    required this.locked,
    required this.midtermLocked,
    required this.endtermLocked,
  });

  factory TermInfo.fromJson(Map<String, dynamic> j) {
    final locks = j['locks'] is Map
        ? Map<String, dynamic>.from(j['locks'] as Map)
        : const <String, dynamic>{};
    return TermInfo(
      id: _int(j['id']),
      name: j['name']?.toString() ?? '',
      locked: j['locked'] == true || locks['term'] == true,
      midtermLocked: j['midterm_locked'] == true || locks['midterm'] == true,
      endtermLocked: j['endterm_locked'] == true || locks['endterm'] == true,
    );
  }
}

class SchoolClassInfo {
  final int id;
  final String name;
  final String? level;
  final int studentCount;

  const SchoolClassInfo({
    required this.id,
    required this.name,
    this.level,
    this.studentCount = 0,
  });

  factory SchoolClassInfo.fromJson(Map<String, dynamic> j) => SchoolClassInfo(
    id: _int(j['id']),
    name: j['name']?.toString() ?? 'Class',
    level: j['level']?.toString(),
    studentCount: _int(j['student_count']),
  );
}

class SubjectInfo {
  final int id;
  final String name;
  final String? code;

  const SubjectInfo({required this.id, required this.name, this.code});

  factory SubjectInfo.fromJson(Map<String, dynamic> j) => SubjectInfo(
    id: _int(j['id']),
    name: j['name']?.toString() ?? 'Subject',
    code: j['code']?.toString(),
  );
}

class TeachingAssignment {
  final int id;
  final SchoolClassInfo schoolClass;
  final SubjectInfo subject;
  final bool isPrimary;

  const TeachingAssignment({
    required this.id,
    required this.schoolClass,
    required this.subject,
    this.isPrimary = false,
  });

  factory TeachingAssignment.fromJson(Map<String, dynamic> j) =>
      TeachingAssignment(
        id: _int(j['id']),
        schoolClass: SchoolClassInfo.fromJson(
          Map<String, dynamic>.from(j['class'] as Map),
        ),
        subject: SubjectInfo.fromJson(
          Map<String, dynamic>.from(j['subject'] as Map),
        ),
        isPrimary: j['is_primary'] == true,
      );

  String get label => '${schoolClass.name} • ${subject.name}';
}

class TeacherCounts {
  final int homeworks;
  final int classTeacherClasses;
  final int teachingAssignments;
  final int pendingAbsenceNotices;

  const TeacherCounts({
    required this.homeworks,
    required this.classTeacherClasses,
    required this.teachingAssignments,
    required this.pendingAbsenceNotices,
  });

  factory TeacherCounts.fromJson(Map<String, dynamic> j) => TeacherCounts(
    homeworks: _int(j['homeworks']),
    classTeacherClasses: _int(j['class_teacher_classes']),
    teachingAssignments: _int(j['teaching_assignments']),
    pendingAbsenceNotices: _int(j['pending_absence_notices']),
  );
}

class TeacherDashboardData {
  final TeacherUser teacher;
  final AcademicYearInfo? academicYear;
  final TermInfo? term;
  final List<SchoolClassInfo> classTeacherClasses;
  final List<TeachingAssignment> teachingAssignments;
  final TeacherCounts counts;

  const TeacherDashboardData({
    required this.teacher,
    this.academicYear,
    this.term,
    required this.classTeacherClasses,
    required this.teachingAssignments,
    required this.counts,
  });

  factory TeacherDashboardData.fromJson(
    Map<String, dynamic> j,
  ) => TeacherDashboardData(
    teacher: TeacherUser.fromJson(
      Map<String, dynamic>.from(j['teacher'] as Map),
    ),
    academicYear: j['academic_year'] is Map
        ? AcademicYearInfo.fromJson(
            Map<String, dynamic>.from(j['academic_year'] as Map),
          )
        : null,
    term: j['term'] is Map
        ? TermInfo.fromJson(Map<String, dynamic>.from(j['term'] as Map))
        : null,
    classTeacherClasses: (j['class_teacher_classes'] as List? ?? [])
        .map(
          (item) =>
              SchoolClassInfo.fromJson(Map<String, dynamic>.from(item as Map)),
        )
        .toList(),
    teachingAssignments: (j['teaching_assignments'] as List? ?? [])
        .map(
          (item) => TeachingAssignment.fromJson(
            Map<String, dynamic>.from(item as Map),
          ),
        )
        .toList(),
    counts: TeacherCounts.fromJson(
      Map<String, dynamic>.from((j['counts'] as Map?) ?? const {}),
    ),
  );
}

class ParentAbsenceNoticeInfo {
  final int id;
  final String reason;
  final String? note;
  final DateTime? absenceDate;
  final DateTime? expectedReturnDate;
  final String status;
  final String? reportedBy;

  const ParentAbsenceNoticeInfo({
    required this.id,
    required this.reason,
    this.note,
    this.absenceDate,
    this.expectedReturnDate,
    required this.status,
    this.reportedBy,
  });

  factory ParentAbsenceNoticeInfo.fromJson(Map<String, dynamic> j) =>
      ParentAbsenceNoticeInfo(
        id: _int(j['id']),
        reason: j['reason']?.toString() ?? '',
        note: _string(j['note']),
        absenceDate: _date(j['absence_date']),
        expectedReturnDate: _date(j['expected_return_date']),
        status: j['status']?.toString() ?? 'pending',
        reportedBy: _string(j['reported_by']),
      );
}

class AttendanceStudent {
  final int id;
  final String admissionNo;
  final String name;
  String status;
  String remarks;
  final bool saved;
  final ParentAbsenceNoticeInfo? parentAbsenceNotice;

  AttendanceStudent({
    required this.id,
    required this.admissionNo,
    required this.name,
    required this.status,
    required this.remarks,
    required this.saved,
    this.parentAbsenceNotice,
  });

  factory AttendanceStudent.fromJson(Map<String, dynamic> j) =>
      AttendanceStudent(
        id: _int(j['id']),
        admissionNo: j['admission_no']?.toString() ?? '',
        name: j['name']?.toString() ?? 'Student',
        status: j['status']?.toString() ?? 'present',
        remarks: j['remarks']?.toString() ?? '',
        saved: j['saved'] == true,
        parentAbsenceNotice: j['parent_absence_notice'] is Map
            ? ParentAbsenceNoticeInfo.fromJson(
                Map<String, dynamic>.from(j['parent_absence_notice'] as Map),
              )
            : null,
      );
}

class AttendanceRegisterData {
  final SchoolClassInfo schoolClass;
  final DateTime date;
  final TermInfo term;
  final List<String> statuses;
  final List<AttendanceStudent> students;

  const AttendanceRegisterData({
    required this.schoolClass,
    required this.date,
    required this.term,
    required this.statuses,
    required this.students,
  });

  factory AttendanceRegisterData.fromJson(Map<String, dynamic> j) =>
      AttendanceRegisterData(
        schoolClass: SchoolClassInfo.fromJson(
          Map<String, dynamic>.from(j['class'] as Map),
        ),
        date: _date(j['date']) ?? DateTime.now(),
        term: TermInfo.fromJson(Map<String, dynamic>.from(j['term'] as Map)),
        statuses:
            (j['statuses'] as List? ?? ['present', 'absent', 'late', 'excused'])
                .map((s) => s.toString())
                .toList(),
        students: (j['students'] as List? ?? [])
            .map(
              (item) => AttendanceStudent.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}

class MarkStudent {
  final int id;
  final String admissionNo;
  final String name;
  double? midtermScore;
  double? endtermScore;
  String remarks;

  MarkStudent({
    required this.id,
    required this.admissionNo,
    required this.name,
    this.midtermScore,
    this.endtermScore,
    this.remarks = '',
  });

  factory MarkStudent.fromJson(Map<String, dynamic> j) => MarkStudent(
    id: _int(j['id']),
    admissionNo: j['admission_no']?.toString() ?? '',
    name: j['name']?.toString() ?? 'Student',
    midtermScore: _double(j['midterm_score']),
    endtermScore: _double(j['endterm_score']),
    remarks: j['remarks']?.toString() ?? '',
  );
}

class MarkSheetData {
  final TeachingAssignment assignment;
  final TermInfo term;
  final List<MarkStudent> students;

  const MarkSheetData({
    required this.assignment,
    required this.term,
    required this.students,
  });

  factory MarkSheetData.fromJson(Map<String, dynamic> j) => MarkSheetData(
    assignment: TeachingAssignment.fromJson({
      'id': _int((j['assignment'] as Map?)?['id']),
      'class': (j['assignment'] as Map?)?['class'] ?? const {},
      'subject': (j['assignment'] as Map?)?['subject'] ?? const {},
      'is_primary': false,
    }),
    term: TermInfo.fromJson(Map<String, dynamic>.from(j['term'] as Map)),
    students: (j['students'] as List? ?? [])
        .map(
          (item) =>
              MarkStudent.fromJson(Map<String, dynamic>.from(item as Map)),
        )
        .toList(),
  );
}

class TeacherHomework {
  final int id;
  final String title;
  final String? description;
  final SchoolClassInfo schoolClass;
  final SubjectInfo subject;
  final TermInfo term;
  final bool isGraded;
  final double? totalMarks;
  final DateTime? assignedDate;
  final DateTime? dueDate;
  final DateTime? publishedAt;
  final bool hasImage;
  final String? imageUrl;
  final bool attachmentRemoved;
  final bool canDelete;

  const TeacherHomework({
    required this.id,
    required this.title,
    this.description,
    required this.schoolClass,
    required this.subject,
    required this.term,
    required this.isGraded,
    this.totalMarks,
    this.assignedDate,
    this.dueDate,
    this.publishedAt,
    required this.hasImage,
    this.imageUrl,
    required this.attachmentRemoved,
    required this.canDelete,
  });

  factory TeacherHomework.fromJson(Map<String, dynamic> j) => TeacherHomework(
    id: _int(j['id']),
    title: j['title']?.toString() ?? 'Homework',
    description: _string(j['description']),
    schoolClass: SchoolClassInfo.fromJson(
      Map<String, dynamic>.from(j['class'] as Map),
    ),
    subject: SubjectInfo.fromJson(
      Map<String, dynamic>.from(j['subject'] as Map),
    ),
    term: TermInfo.fromJson(Map<String, dynamic>.from(j['term'] as Map)),
    isGraded: j['is_graded'] == true,
    totalMarks: _double(j['total_marks']),
    assignedDate: _date(j['assigned_date']),
    dueDate: _date(j['due_date']),
    publishedAt: _date(j['published_at']),
    hasImage: j['has_image'] == true,
    imageUrl: _string(j['image_url']),
    attachmentRemoved: j['attachment_removed'] == true,
    canDelete: j['can_delete'] == true,
  );
}

class TeacherSchemeSummary {
  final int id;
  final String title;
  final String className;
  final String subjectName;
  final String academicYear;
  final String status;
  final double overallPct;
  final double expectedPct;
  final String pacingStatus;
  final DateTime? lastProgressAt;

  const TeacherSchemeSummary({
    required this.id,
    required this.title,
    required this.className,
    required this.subjectName,
    required this.academicYear,
    required this.status,
    required this.overallPct,
    required this.expectedPct,
    required this.pacingStatus,
    this.lastProgressAt,
  });

  factory TeacherSchemeSummary.fromJson(Map<String, dynamic> j) =>
      TeacherSchemeSummary(
        id: _int(j['id']),
        title: j['title']?.toString() ?? 'Scheme of Work',
        className: j['class_name']?.toString() ?? 'Class not available',
        subjectName: j['subject_name']?.toString() ?? 'Subject not available',
        academicYear: j['academic_year']?.toString() ?? '',
        status: j['status']?.toString() ?? 'draft',
        overallPct: _double(j['overall_pct']) ?? 0,
        expectedPct: _double(j['expected_pct']) ?? 0,
        pacingStatus: j['pacing_status']?.toString() ?? 'no_plan',
        lastProgressAt: _date(j['last_progress_at']),
      );
}

class TeacherSchemeDetail {
  final TeacherSchemeSummary summary;
  final List<TeacherSchemeTerm> terms;

  const TeacherSchemeDetail({required this.summary, required this.terms});

  factory TeacherSchemeDetail.fromJson(Map<String, dynamic> j) =>
      TeacherSchemeDetail(
        summary: TeacherSchemeSummary.fromJson(j),
        terms: (j['terms'] as List? ?? [])
            .map(
              (item) => TeacherSchemeTerm.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}

class TeacherSchemeTerm {
  final int id;
  final String name;
  final List<TeacherSchemeWeek> weeks;

  const TeacherSchemeTerm({
    required this.id,
    required this.name,
    required this.weeks,
  });

  factory TeacherSchemeTerm.fromJson(Map<String, dynamic> j) =>
      TeacherSchemeTerm(
        id: _int(j['term_id']),
        name: j['term_name']?.toString() ?? 'Term',
        weeks: (j['weeks'] as List? ?? [])
            .map(
              (item) => TeacherSchemeWeek.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}

class TeacherSchemeWeek {
  final int week;
  final List<TeacherSchemeTopic> topics;

  const TeacherSchemeWeek({required this.week, required this.topics});

  factory TeacherSchemeWeek.fromJson(Map<String, dynamic> j) =>
      TeacherSchemeWeek(
        week: _int(j['week']),
        topics: (j['topics'] as List? ?? [])
            .map(
              (item) => TeacherSchemeTopic.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}

class TeacherSchemeTopic {
  final int id;
  final String title;
  final int weekNumber;
  final String status;
  final bool isBehind;
  final String? teacherComment;
  final List<TeacherSchemeSubtopic> subtopics;

  const TeacherSchemeTopic({
    required this.id,
    required this.title,
    required this.weekNumber,
    required this.status,
    required this.isBehind,
    this.teacherComment,
    required this.subtopics,
  });

  factory TeacherSchemeTopic.fromJson(Map<String, dynamic> j) =>
      TeacherSchemeTopic(
        id: _int(j['id']),
        title: j['title']?.toString() ?? 'Topic',
        weekNumber: _int(j['week_number']),
        status: j['status']?.toString() ?? 'not_started',
        isBehind: j['is_behind'] == true,
        teacherComment: _string(j['teacher_comment']),
        subtopics: (j['subtopics'] as List? ?? [])
            .map(
              (item) => TeacherSchemeSubtopic.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
      );
}

class TeacherSchemeSubtopic {
  final int id;
  final String title;
  final String status;

  const TeacherSchemeSubtopic({
    required this.id,
    required this.title,
    required this.status,
  });

  bool get isCompleted => status == 'completed';

  factory TeacherSchemeSubtopic.fromJson(Map<String, dynamic> j) =>
      TeacherSchemeSubtopic(
        id: _int(j['id']),
        title: j['title']?.toString() ?? 'Subtopic',
        status: j['status']?.toString() ?? 'not_started',
      );
}

class TimetableData {
  final String? templateName;
  final String? academicYear;
  final int? selectedDayNumber;
  final List<TimetableDayData> days;

  const TimetableData({
    this.templateName,
    this.academicYear,
    this.selectedDayNumber,
    required this.days,
  });

  bool get isPublished => templateName != null;

  factory TimetableData.fromJson(Map<String, dynamic> j) {
    final template = j['template'] is Map
        ? Map<String, dynamic>.from(j['template'] as Map)
        : null;

    return TimetableData(
      templateName: template?['name']?.toString(),
      academicYear: template?['academic_year']?.toString(),
      selectedDayNumber: j['selected_day_number'] == null
          ? null
          : _int(j['selected_day_number']),
      days: (j['days'] as List? ?? [])
          .map(
            (item) => TimetableDayData.fromJson(
              Map<String, dynamic>.from(item as Map),
            ),
          )
          .toList(),
    );
  }
}

class TimetableDayData {
  final int dayNumber;
  final String name;
  final List<TimetableBlock> blocks;

  const TimetableDayData({
    required this.dayNumber,
    required this.name,
    required this.blocks,
  });

  factory TimetableDayData.fromJson(Map<String, dynamic> j) => TimetableDayData(
    dayNumber: _int(j['day_number']),
    name: j['name']?.toString() ?? 'Day',
    blocks: (j['blocks'] as List? ?? [])
        .map(
          (item) =>
              TimetableBlock.fromJson(Map<String, dynamic>.from(item as Map)),
        )
        .toList(),
  );
}

class TimetableBlock {
  final String kind;
  final String periodName;
  final String startTime;
  final String endTime;
  final int durationMinutes;
  final String title;
  final String? subject;
  final String? teacher;
  final String? className;
  final String? group;
  final String? room;
  final String? notes;

  const TimetableBlock({
    required this.kind,
    required this.periodName,
    required this.startTime,
    required this.endTime,
    required this.durationMinutes,
    required this.title,
    this.subject,
    this.teacher,
    this.className,
    this.group,
    this.room,
    this.notes,
  });

  bool get isLesson => kind == 'lesson';
  bool get isEvent => kind == 'event';

  factory TimetableBlock.fromJson(Map<String, dynamic> j) => TimetableBlock(
    kind: j['kind']?.toString() ?? 'free',
    periodName: j['period_name']?.toString() ?? '',
    startTime: j['start_time']?.toString() ?? '',
    endTime: j['end_time']?.toString() ?? '',
    durationMinutes: _int(j['duration_minutes']),
    title: j['title']?.toString() ?? 'Free period',
    subject: _string(j['subject']),
    teacher: _string(j['teacher']),
    className: _string(j['class']),
    group: _string(j['group']),
    room: _string(j['room']),
    notes: _string(j['notes']),
  );
}

int _int(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double? _double(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}

DateTime? _date(dynamic value) {
  if (value == null) return null;
  return DateTime.tryParse(value.toString());
}

String? _string(dynamic value) {
  final raw = value?.toString().trim();
  if (raw == null || raw.isEmpty || raw == 'null') return null;
  return raw;
}
