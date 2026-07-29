// ============================================================
// Shared JSON parsing helpers
// ============================================================
int _intOrZero(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value?.toString().trim() ?? '') ?? 0;
}

double? _doubleOrNull(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  final text = value.toString().trim();
  if (text.isEmpty) return null;
  return double.tryParse(text.replaceAll(',', ''));
}

String? _stringOrNull(dynamic value) {
  if (value == null) return null;
  final text = value.toString().trim();
  return text.isEmpty ? null : text;
}

DateTime? _dateOrNull(dynamic value) {
  if (value == null) return null;
  if (value is DateTime) return value;
  final text = value.toString().trim();
  if (text.isEmpty) return null;
  return DateTime.tryParse(text);
}

bool _boolValue(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  final text = value?.toString().toLowerCase().trim();
  return text == '1' || text == 'true' || text == 'yes';
}

DateTime? _dateOnlyAsLocal(dynamic value) {
  if (value == null) return null;
  if (value is DateTime) return DateTime(value.year, value.month, value.day);

  final text = value.toString().trim();
  if (text.isEmpty) return null;

  final match = RegExp(r'^(\d{4})-(\d{2})-(\d{2})').firstMatch(text);
  if (match == null) return null;

  return DateTime(
    int.parse(match.group(1)!),
    int.parse(match.group(2)!),
    int.parse(match.group(3)!),
  );
}

DateTime _serverDateTimeAsLocal(dynamic value, {DateTime? fallback}) {
  if (value is DateTime) return value.toLocal();

  final text = value?.toString().trim() ?? '';
  if (text.isEmpty) return fallback ?? DateTime.now();

  // Laravel usually sends "YYYY-MM-DD HH:mm:ss" for local school time.
  // Parse that as local time, not UTC, so the calendar cannot shift dates.
  final plainLocal = RegExp(
    r'^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$',
  );
  if (plainLocal.hasMatch(text)) {
    final normalised = text.length == 10 ? text : text.replaceFirst(' ', 'T');
    return DateTime.parse(normalised);
  }

  return DateTime.parse(text).toLocal();
}

DateTime _eventStartFromJson(Map<String, dynamic> j) {
  final isAllDay = _boolValue(j['is_all_day'] ?? j['allDay']);
  final raw = isAllDay
      ? (j['start_date'] ?? j['start_datetime'] ?? j['start'])
      : (j['start_datetime'] ?? j['start'] ?? j['start_date']);

  if (isAllDay) {
    final dateOnly = _dateOnlyAsLocal(raw);
    if (dateOnly != null) return dateOnly;
  }

  return _serverDateTimeAsLocal(raw);
}

DateTime? _eventEndFromJson(Map<String, dynamic> j) {
  final isAllDay = _boolValue(j['is_all_day'] ?? j['allDay']);
  final raw = isAllDay
      ? (j['end_date'] ?? j['end_datetime'] ?? j['end'])
      : (j['end_datetime'] ?? j['end'] ?? j['end_date']);

  if (raw == null || raw.toString().trim().isEmpty) return null;

  if (isAllDay) {
    return _dateOnlyAsLocal(raw);
  }

  return _serverDateTimeAsLocal(raw);
}

// ============================================================
// models/user.dart
// ============================================================
class UserModel {
  final int id;
  final String name;
  final String email;
  final bool mustChangePassword;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.mustChangePassword,
  });

  factory UserModel.fromJson(Map<String, dynamic> j) {
    final rawMustChange = j['must_change_password'];

    return UserModel(
      id: j['id'] is int ? j['id'] : int.tryParse('${j['id']}') ?? 0,
      name: j['name'] ?? '',
      email: j['email'] ?? '',
      mustChangePassword:
          rawMustChange == true ||
          rawMustChange == 1 ||
          rawMustChange == '1' ||
          rawMustChange == 'true',
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'must_change_password': mustChangePassword,
  };
}

// ============================================================
// models/child.dart
// ============================================================
class ChildModel {
  final int id;
  final String name;
  final String admissionNo;
  final String? className;
  final String? photo;
  final bool isBlocked;
  final MarksOverview? marks;
  final double? attendanceRate;
  final BehaviourSummary? behaviour;
  final LibrarySummary? library;
  final StudentIdentity identity;
  final StudentProfileInfo profile;
  final EmergencyContactInfo emergencyContact;
  final HomeworkSummary homework;

  const ChildModel({
    required this.id,
    required this.name,
    required this.admissionNo,
    this.className,
    this.photo,
    required this.isBlocked,
    this.marks,
    this.attendanceRate,
    this.behaviour,
    this.library,
    required this.identity,
    required this.profile,
    required this.emergencyContact,
    required this.homework,
  });

  factory ChildModel.fromJson(Map<String, dynamic> j) => ChildModel(
    id: _intOrZero(j['id']),
    name: j['name']?.toString() ?? 'Student',
    admissionNo: j['admission_no']?.toString() ?? '',
    className: j['class']?.toString(),
    photo: j['photo']?.toString(),
    isBlocked: j['is_blocked'] == true,
    marks: j['marks'] != null
        ? MarksOverview.fromJson(Map<String, dynamic>.from(j['marks'] as Map))
        : null,
    attendanceRate: _doubleOrNull(
      j['attendance_rate'] ??
          (j['attendance'] is Map
              ? ((j['attendance'] as Map)['attendance_rate'] ??
                    (j['attendance'] as Map)['rate'] ??
                    (j['attendance'] as Map)['percentage'])
              : null) ??
          (j['attendance_summary'] is Map
              ? ((j['attendance_summary'] as Map)['attendance_rate'] ??
                    (j['attendance_summary'] as Map)['rate'] ??
                    (j['attendance_summary'] as Map)['percentage'])
              : null),
    ),
    behaviour: j['behaviour'] != null
        ? BehaviourSummary.fromJson(
            Map<String, dynamic>.from(j['behaviour'] as Map),
          )
        : null,
    library: j['library'] != null
        ? LibrarySummary.fromJson(
            Map<String, dynamic>.from(j['library'] as Map),
          )
        : null,
    identity: StudentIdentity.fromJson(
      Map<String, dynamic>.from((j['identity'] as Map?) ?? const {}),
    ),
    profile: StudentProfileInfo.fromJson(
      j['profile'] is Map
          ? Map<String, dynamic>.from(j['profile'] as Map)
          : {
              'profile_complete': j['profile_complete'],
              'missing_fields': j['missing_fields'],
            },
    ),
    emergencyContact: EmergencyContactInfo.fromJson(
      Map<String, dynamic>.from((j['emergency_contact'] as Map?) ?? const {}),
    ),
    homework: HomeworkSummary.fromJson(
      Map<String, dynamic>.from((j['homework'] as Map?) ?? const {}),
    ),
  );
}

class StudentIdentity {
  final String? nationality;
  final String? documentType;
  final String? documentLabel;
  final String? documentNumber;
  final String? display;

  const StudentIdentity({
    this.nationality,
    this.documentType,
    this.documentLabel,
    this.documentNumber,
    this.display,
  });

  factory StudentIdentity.fromJson(Map<String, dynamic> j) => StudentIdentity(
    nationality: _stringOrNull(j['nationality']),
    documentType: _stringOrNull(
      j['document_type'] ?? j['identity_document_type'],
    ),
    documentLabel: _stringOrNull(j['document_label']),
    documentNumber: _stringOrNull(
      j['document_number'] ?? j['identity_document_number'],
    ),
    display: _stringOrNull(j['display']),
  );
}

class StudentProfileInfo {
  final bool complete;
  final List<String> missingFields;
  final DateTime? updatedByParentAt;

  const StudentProfileInfo({
    required this.complete,
    required this.missingFields,
    this.updatedByParentAt,
  });

  factory StudentProfileInfo.fromJson(Map<String, dynamic> j) =>
      StudentProfileInfo(
        complete: j['complete'] == true || j['profile_complete'] == true,
        missingFields: (j['missing_fields'] as List? ?? [])
            .map((item) => item.toString())
            .toList(),
        updatedByParentAt: _dateOrNull(j['profile_updated_by_parent_at']),
      );
}

class EmergencyContactInfo {
  final String? name;
  final String? relationship;
  final String? phone;
  final String? altPhone;
  final String? address;
  final String? medicalNotes;

  const EmergencyContactInfo({
    this.name,
    this.relationship,
    this.phone,
    this.altPhone,
    this.address,
    this.medicalNotes,
  });

  factory EmergencyContactInfo.fromJson(Map<String, dynamic> j) =>
      EmergencyContactInfo(
        name: _stringOrNull(j['name'] ?? j['emergency_contact_name']),
        relationship: _stringOrNull(
          j['relationship'] ?? j['emergency_contact_relationship'],
        ),
        phone: _stringOrNull(j['phone'] ?? j['emergency_contact_phone']),
        altPhone: _stringOrNull(
          j['alt_phone'] ?? j['emergency_contact_alt_phone'],
        ),
        address: _stringOrNull(j['address'] ?? j['emergency_contact_address']),
        medicalNotes: _stringOrNull(j['medical_notes']),
      );
}

class HomeworkSummary {
  final int currentTermCount;
  final int unreadCount;

  const HomeworkSummary({
    required this.currentTermCount,
    required this.unreadCount,
  });

  factory HomeworkSummary.fromJson(Map<String, dynamic> j) => HomeworkSummary(
    currentTermCount: _intOrZero(j['current_term_count']),
    unreadCount: _intOrZero(j['unread_count']),
  );
}

class MarksOverview {
  final double? midtermAverage;
  final double? endtermAverage;
  final String? trend;
  final String? performanceLabel;
  final PositionInfo? midtermPosition;
  final PositionInfo? endtermPosition;

  const MarksOverview({
    this.midtermAverage,
    this.endtermAverage,
    this.trend,
    this.performanceLabel,
    this.midtermPosition,
    this.endtermPosition,
  });

  factory MarksOverview.fromJson(Map<String, dynamic> j) => MarksOverview(
    midtermAverage: (j['midterm_average'] as num?)?.toDouble(),
    endtermAverage: (j['endterm_average'] as num?)?.toDouble(),
    trend: j['trend'],
    performanceLabel: j['performance_label'],
    midtermPosition: j['midterm_position'] != null
        ? PositionInfo.fromJson(j['midterm_position'])
        : null,
    endtermPosition: j['endterm_position'] != null
        ? PositionInfo.fromJson(j['endterm_position'])
        : null,
  );
}

class PositionInfo {
  final int? position;
  final int? classSize;

  const PositionInfo({this.position, this.classSize});

  factory PositionInfo.fromJson(Map<String, dynamic> j) =>
      PositionInfo(position: j['position'], classSize: j['class_size']);

  String get display => position != null ? '$position/$classSize' : '—';
}

class BehaviourSummary {
  final String label;
  final int total;

  const BehaviourSummary({required this.label, required this.total});

  factory BehaviourSummary.fromJson(Map<String, dynamic> j) =>
      BehaviourSummary(label: j['label'] ?? 'Good', total: j['total'] ?? 0);
}

class LibrarySummary {
  final int borrowed;
  final int overdue;
  final List<BookBorrowing> books;

  const LibrarySummary({
    required this.borrowed,
    required this.overdue,
    required this.books,
  });

  factory LibrarySummary.fromJson(Map<String, dynamic> j) => LibrarySummary(
    borrowed: j['borrowed'] ?? 0,
    overdue: j['overdue'] ?? 0,
    books: (j['books'] as List? ?? [])
        .map((b) => BookBorrowing.fromJson(b))
        .toList(),
  );
}

class BookBorrowing {
  final String title;
  final String? dueAt;
  final bool overdue;

  const BookBorrowing({required this.title, this.dueAt, required this.overdue});

  factory BookBorrowing.fromJson(Map<String, dynamic> j) => BookBorrowing(
    title: j['title'] ?? 'Unknown',
    dueAt: j['due_at'],
    overdue: j['overdue'] ?? false,
  );
}

// ============================================================
// models/event.dart
// ============================================================
class EventModel {
  final int id;
  final String title;
  final String? description;
  final String type;
  final String typeLabel;
  final String typeColor;
  final DateTime startDatetime;
  final DateTime? endDatetime;
  final bool isAllDay;
  final int daysUntil;

  const EventModel({
    required this.id,
    required this.title,
    this.description,
    required this.type,
    required this.typeLabel,
    required this.typeColor,
    required this.startDatetime,
    this.endDatetime,
    required this.isAllDay,
    required this.daysUntil,
  });

  factory EventModel.fromJson(Map<String, dynamic> j) {
    final isAllDay = _boolValue(j['is_all_day'] ?? j['allDay']);

    return EventModel(
      id: _intOrZero(j['id']),
      title: j['title']?.toString() ?? 'Event',
      description: _stringOrNull(j['description']),
      type: j['type']?.toString() ?? 'other',
      typeLabel: j['type_label']?.toString() ?? j['type']?.toString() ?? '',
      typeColor: j['type_color']?.toString() ?? 'gray',
      startDatetime: _eventStartFromJson(j),
      endDatetime: _eventEndFromJson(j),
      isAllDay: isAllDay,
      daysUntil: _intOrZero(j['days_until']),
    );
  }
}

// ============================================================
// models/announcement.dart
// ============================================================
class AnnouncementModel {
  final int id;
  final String title;
  final String message;
  final String type;
  final String typeLabel;
  final String typeColor;
  final String typeIcon;
  final String author;
  final DateTime? publishAt;
  final DateTime createdAt;
  final bool isRead;
  final DateTime? readAt;
  final bool requiresAcknowledgement;
  final bool isAcknowledged;
  final DateTime? acknowledgedAt;

  const AnnouncementModel({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    required this.typeLabel,
    required this.typeColor,
    required this.typeIcon,
    required this.author,
    this.publishAt,
    required this.createdAt,
    this.isRead = false,
    this.readAt,
    this.requiresAcknowledgement = false,
    this.isAcknowledged = false,
    this.acknowledgedAt,
  });

  factory AnnouncementModel.fromJson(Map<String, dynamic> j) {
    DateTime? parseNullableDate(dynamic value) {
      if (value == null) return null;
      return DateTime.tryParse(value.toString());
    }

    return AnnouncementModel(
      id: j['id'] is int ? j['id'] : int.tryParse('${j['id']}') ?? 0,
      title: j['title'] ?? '',
      message: j['message'] ?? '',
      type: j['type'] ?? 'general',
      typeLabel: j['type_label'] ?? '',
      typeColor: j['type_color'] ?? 'gray',
      typeIcon: j['type_icon'] ?? '📢',
      author: j['author'] ?? 'Admin',
      publishAt: parseNullableDate(j['publish_at']),
      createdAt: parseNullableDate(j['created_at']) ?? DateTime.now(),
      isRead: j['is_read'] == true,
      readAt: parseNullableDate(j['read_at']),
      requiresAcknowledgement: j['requires_acknowledgement'] == true,
      isAcknowledged: j['is_acknowledged'] == true,
      acknowledgedAt: parseNullableDate(j['acknowledged_at']),
    );
  }

  DateTime get displayDate => publishAt ?? createdAt;

  bool get hasPendingAcknowledgement =>
      requiresAcknowledgement && !isAcknowledged;
}

// ============================================================
// models/dashboard.dart
// ============================================================
class DashboardData {
  final UserModel user;
  final AcademicYearInfo? academicYear;
  final TermInfo? currentTerm;
  final DashboardStats stats;
  final List<AnnouncementModel> importantAnnouncements;
  final List<AnnouncementModel> announcements;
  final List<EventModel> upcomingEvents;
  final List<ChildModel> children;

  const DashboardData({
    required this.user,
    this.academicYear,
    this.currentTerm,
    required this.stats,
    required this.importantAnnouncements,
    required this.announcements,
    required this.upcomingEvents,
    required this.children,
  });

  factory DashboardData.fromJson(Map<String, dynamic> j) => DashboardData(
    user: UserModel.fromJson(j['user']),
    academicYear: j['academic_year'] != null
        ? AcademicYearInfo.fromJson(j['academic_year'])
        : null,
    currentTerm: j['current_term'] != null
        ? TermInfo.fromJson(j['current_term'])
        : null,
    stats: DashboardStats.fromJson(j['stats']),
    importantAnnouncements: (j['important_announcements'] as List? ?? [])
        .map((a) => AnnouncementModel.fromJson(a))
        .toList(),
    announcements: (j['announcements'] as List? ?? [])
        .map((a) => AnnouncementModel.fromJson(a))
        .toList(),
    upcomingEvents: (j['upcoming_events'] as List? ?? [])
        .map((e) => EventModel.fromJson(e))
        .toList(),
    children: (j['children'] as List? ?? [])
        .map((c) => ChildModel.fromJson(c))
        .toList(),
  );
}

class AcademicYearInfo {
  final int id;
  final String yearName;

  const AcademicYearInfo({required this.id, required this.yearName});

  factory AcademicYearInfo.fromJson(Map<String, dynamic> j) =>
      AcademicYearInfo(id: j['id'], yearName: j['year_name']);
}

class TermInfo {
  final int id;
  final String name;
  final String startDate;
  final String endDate;
  final int daysLeft;

  const TermInfo({
    required this.id,
    required this.name,
    required this.startDate,
    required this.endDate,
    required this.daysLeft,
  });

  factory TermInfo.fromJson(Map<String, dynamic> j) => TermInfo(
    id: j['id'],
    name: j['name'],
    startDate: j['start_date'],
    endDate: j['end_date'],
    daysLeft: j['days_left'] ?? 0,
  );
}

class DashboardStats {
  final int totalChildren;
  final int blockedChildren;
  final int accessibleChildren;
  final int unreadHomework;
  final int incompleteProfiles;

  const DashboardStats({
    required this.totalChildren,
    required this.blockedChildren,
    required this.accessibleChildren,
    required this.unreadHomework,
    required this.incompleteProfiles,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> j) => DashboardStats(
    totalChildren: _intOrZero(j['total_children']),
    blockedChildren: _intOrZero(j['blocked_children']),
    accessibleChildren: _intOrZero(j['accessible_children']),
    unreadHomework: _intOrZero(j['unread_homework']),
    incompleteProfiles: _intOrZero(j['incomplete_profiles']),
  );
}

// ============================================================
// models/parent_registration.dart
// ============================================================
class ParentRegistrationStudentPreview {
  final int id;
  final String name;
  final String? className;

  const ParentRegistrationStudentPreview({
    required this.id,
    required this.name,
    this.className,
  });

  factory ParentRegistrationStudentPreview.fromJson(Map<String, dynamic> j) {
    return ParentRegistrationStudentPreview(
      id: j['id'] is int ? j['id'] : int.tryParse('${j['id']}') ?? 0,
      name: j['name']?.toString() ?? 'Student',
      className: j['class']?.toString(),
    );
  }
}

class ParentCodeVerification {
  final String inviteCode;
  final ParentRegistrationStudentPreview student;

  const ParentCodeVerification({
    required this.inviteCode,
    required this.student,
  });

  factory ParentCodeVerification.fromJson(Map<String, dynamic> j) {
    return ParentCodeVerification(
      inviteCode: (j['invite_code'] ?? '').toString(),
      student: ParentRegistrationStudentPreview.fromJson(
        Map<String, dynamic>.from(j['student'] as Map),
      ),
    );
  }
}

// ============================================================
// Parent Homework
// ============================================================
class ParentHomeworkData {
  final List<ChildModel> children;
  final List<ParentHomeworkItem> homework;
  final int unreadCountBeforeOpen;

  const ParentHomeworkData({
    required this.children,
    required this.homework,
    required this.unreadCountBeforeOpen,
  });

  factory ParentHomeworkData.fromJson(Map<String, dynamic> j) =>
      ParentHomeworkData(
        children: (j['children'] as List? ?? [])
            .map(
              (child) =>
                  ChildModel.fromJson(Map<String, dynamic>.from(child as Map)),
            )
            .toList(),
        homework: (j['homework'] as List? ?? [])
            .map(
              (item) => ParentHomeworkItem.fromJson(
                Map<String, dynamic>.from(item as Map),
              ),
            )
            .toList(),
        unreadCountBeforeOpen: _intOrZero(j['unread_count_before_open']),
      );

  Map<String, Map<String, List<ParentHomeworkItem>>>
  get groupedByStudentSubject {
    final result = <String, Map<String, List<ParentHomeworkItem>>>{};
    for (final item in homework) {
      final studentKey = item.studentName.isEmpty
          ? 'Student ${item.studentId}'
          : item.studentName;
      final subjectKey = item.subjectName ?? 'General homework';
      result.putIfAbsent(
        studentKey,
        () => <String, List<ParentHomeworkItem>>{},
      );
      result[studentKey]!.putIfAbsent(subjectKey, () => <ParentHomeworkItem>[]);
      result[studentKey]![subjectKey]!.add(item);
    }
    return result;
  }
}

class ParentHomeworkItem {
  final int id;
  final int homeworkMarkId;
  final int studentId;
  final String studentName;
  final String? className;
  final String? subjectName;
  final String? teacherName;
  final String? termName;
  final String title;
  final String? description;
  final double? totalMarks;
  final double? marksObtained;
  final double? percentage;
  final String? grade;
  final String submissionStatus;
  final String submissionStatusLabel;
  final String? remarks;
  final DateTime? assignedDate;
  final DateTime? dueDate;
  final bool hasAttachment;
  final bool attachmentRemoved;
  final String? attachmentDownloadUrl;
  final bool isUnread;

  const ParentHomeworkItem({
    required this.id,
    required this.homeworkMarkId,
    required this.studentId,
    required this.studentName,
    this.className,
    this.subjectName,
    this.teacherName,
    this.termName,
    required this.title,
    this.description,
    this.totalMarks,
    this.marksObtained,
    this.percentage,
    this.grade,
    required this.submissionStatus,
    required this.submissionStatusLabel,
    this.remarks,
    this.assignedDate,
    this.dueDate,
    required this.hasAttachment,
    required this.attachmentRemoved,
    this.attachmentDownloadUrl,
    required this.isUnread,
  });

  factory ParentHomeworkItem.fromJson(Map<String, dynamic> j) =>
      ParentHomeworkItem(
        id: _intOrZero(j['id']),
        homeworkMarkId: _intOrZero(j['homework_mark_id']),
        studentId: _intOrZero(j['student_id']),
        studentName: j['student_name']?.toString() ?? 'Student',
        className: _stringOrNull(j['class_name']),
        subjectName: _stringOrNull(j['subject_name']),
        teacherName: _stringOrNull(j['teacher_name']),
        termName: _stringOrNull(j['term_name']),
        title: j['title']?.toString() ?? 'Homework',
        description: _stringOrNull(j['description']),
        totalMarks: _doubleOrNull(j['total_marks']),
        marksObtained: _doubleOrNull(j['marks_obtained']),
        percentage: _doubleOrNull(j['percentage']),
        grade: _stringOrNull(j['grade']),
        submissionStatus: j['submission_status']?.toString() ?? 'submitted',
        submissionStatusLabel:
            j['submission_status_label']?.toString() ?? 'Submitted',
        remarks: _stringOrNull(j['remarks']),
        assignedDate: _dateOrNull(j['assigned_date']),
        dueDate: _dateOrNull(j['due_date']),
        hasAttachment: j['has_attachment'] == true,
        attachmentRemoved: j['attachment_removed'] == true,
        attachmentDownloadUrl: _stringOrNull(j['attachment_download_url']),
        isUnread: j['is_unread'] == true,
      );

  bool get isLate {
    final due = dueDate;
    if (due == null) return false;
    final today = DateTime.now();
    final dueOnly = DateTime(due.year, due.month, due.day);
    final todayOnly = DateTime(today.year, today.month, today.day);
    return todayOnly.isAfter(dueOnly) && submissionStatus == 'not_submitted';
  }
}

// ============================================================
// Parent Absence Notices
// ============================================================
class ParentAbsenceNoticeModel {
  final int id;
  final int studentId;
  final String studentName;
  final String? className;
  final String absenceDate;
  final String? expectedReturnDate;
  final String reason;
  final String? note;
  final String status;
  final String statusLabel;
  final DateTime? submittedAt;
  final DateTime? seenAt;
  final DateTime? resolvedAt;

  const ParentAbsenceNoticeModel({
    required this.id,
    required this.studentId,
    required this.studentName,
    this.className,
    required this.absenceDate,
    this.expectedReturnDate,
    required this.reason,
    this.note,
    required this.status,
    required this.statusLabel,
    this.submittedAt,
    this.seenAt,
    this.resolvedAt,
  });

  factory ParentAbsenceNoticeModel.fromJson(Map<String, dynamic> j) =>
      ParentAbsenceNoticeModel(
        id: j['id'] is int ? j['id'] : int.tryParse('${j['id']}') ?? 0,
        studentId: j['student_id'] is int
            ? j['student_id']
            : int.tryParse('${j['student_id']}') ?? 0,
        studentName: j['student_name'] ?? '',
        className: j['class_name'],
        absenceDate: j['absence_date'] ?? '',
        expectedReturnDate: j['expected_return_date'],
        reason: j['reason'] ?? '',
        note: j['note'],
        status: j['status'] ?? 'pending',
        statusLabel: j['status_label'] ?? 'Pending',
        submittedAt: DateTime.tryParse(j['submitted_at'] ?? ''),
        seenAt: DateTime.tryParse(j['seen_at'] ?? ''),
        resolvedAt: DateTime.tryParse(j['resolved_at'] ?? ''),
      );
}

class AbsenceNoticesData {
  final List<ChildModel> children;
  final List<ParentAbsenceNoticeModel> notices;
  final List<String> reasons;

  const AbsenceNoticesData({
    required this.children,
    required this.notices,
    required this.reasons,
  });

  factory AbsenceNoticesData.fromJson(Map<String, dynamic> j) =>
      AbsenceNoticesData(
        children: (j['children'] as List? ?? [])
            .map((c) => ChildModel.fromJson(c))
            .toList(),
        notices: (j['notices'] as List? ?? [])
            .map((n) => ParentAbsenceNoticeModel.fromJson(n))
            .toList(),
        reasons: (j['reasons'] as List? ?? [])
            .map((r) => r.toString())
            .toList(),
      );
}

// ============================================================
// Parent Fees
// ============================================================
class ParentFeeBalanceModel {
  final int studentId;
  final String studentName;
  final String? className;
  final double? closingBalance;
  final String formattedClosingBalance;
  final String status;
  final String? academicYear;
  final String? term;
  final DateTime? lastUpdated;

  const ParentFeeBalanceModel({
    required this.studentId,
    required this.studentName,
    this.className,
    this.closingBalance,
    required this.formattedClosingBalance,
    required this.status,
    this.academicYear,
    this.term,
    this.lastUpdated,
  });

  factory ParentFeeBalanceModel.fromJson(Map<String, dynamic> j) {
    final balance = _doubleOrNull(j['closing_balance']);

    return ParentFeeBalanceModel(
      studentId: _intOrZero(j['student_id']),
      studentName: j['student_name']?.toString() ?? 'Student',
      className: j['class_name']?.toString(),
      closingBalance: balance,
      formattedClosingBalance:
          j['formatted_closing_balance']?.toString() ??
          (balance == null
              ? 'Not available'
              : 'P${balance.toStringAsFixed(2)}'),
      status: j['status']?.toString() ?? 'not_available',
      academicYear: j['academic_year']?.toString(),
      term: j['term']?.toString(),
      lastUpdated: _dateOrNull(j['last_updated']),
    );
  }

  bool get isOutstanding =>
      status == 'outstanding' || (closingBalance ?? 0) > 0;
  bool get isClear => status == 'clear' || closingBalance == 0;
  bool get isNotAvailable =>
      status == 'not_available' || closingBalance == null;

  static int _intOrZero(dynamic value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  static double? _doubleOrNull(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString());
  }

  static DateTime? _dateOrNull(dynamic value) {
    if (value == null) return null;
    return DateTime.tryParse(value.toString());
  }
}

class ParentFeesData {
  final List<ParentFeeBalanceModel> children;

  const ParentFeesData({required this.children});

  factory ParentFeesData.fromJson(Map<String, dynamic> j) => ParentFeesData(
    children: (j['children'] as List? ?? [])
        .map(
          (child) => ParentFeeBalanceModel.fromJson(
            Map<String, dynamic>.from(child as Map),
          ),
        )
        .toList(),
  );

  double get totalOutstanding => children.fold<double>(0, (sum, child) {
    final balance = child.closingBalance ?? 0;
    return balance > 0 ? sum + balance : sum;
  });

  int get outstandingCount => children.where((c) => c.isOutstanding).length;
  int get clearCount => children.where((c) => c.isClear).length;
  int get notAvailableCount => children.where((c) => c.isNotAvailable).length;

  String get formattedTotalOutstanding =>
      'P${totalOutstanding.toStringAsFixed(2)}';
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
          : _intOrZero(j['selected_day_number']),
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
    dayNumber: _intOrZero(j['day_number']),
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
    durationMinutes: _intOrZero(j['duration_minutes']),
    title: j['title']?.toString() ?? 'Free period',
    teacher: _stringOrNull(j['teacher']),
    className: _stringOrNull(j['class']),
    group: _stringOrNull(j['group']),
    room: _stringOrNull(j['room']),
    notes: _stringOrNull(j['notes']),
  );
}
