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

  factory UserModel.fromJson(Map<String, dynamic> j) => UserModel(
    id: j['id'],
    name: j['name'],
    email: j['email'],
    mustChangePassword: j['must_change_password'] ?? false,
  );
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
  });

  factory ChildModel.fromJson(Map<String, dynamic> j) => ChildModel(
    id: j['id'],
    name: j['name'],
    admissionNo: j['admission_no'] ?? '',
    className: j['class'],
    photo: j['photo'],
    isBlocked: j['is_blocked'] ?? false,
    marks: j['marks'] != null ? MarksOverview.fromJson(j['marks']) : null,
    attendanceRate: (j['attendance_rate'] as num?)?.toDouble(),
    behaviour: j['behaviour'] != null
        ? BehaviourSummary.fromJson(j['behaviour'])
        : null,
    library: j['library'] != null
        ? LibrarySummary.fromJson(j['library'])
        : null,
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

  factory EventModel.fromJson(Map<String, dynamic> j) => EventModel(
    id: j['id'],
    title: j['title'],
    description: j['description'],
    type: j['type'] ?? 'other',
    typeLabel: j['type_label'] ?? j['type'] ?? '',
    typeColor: j['type_color'] ?? 'gray',
    startDatetime: DateTime.parse(j['start_datetime']),
    endDatetime: j['end_datetime'] != null
        ? DateTime.parse(j['end_datetime'])
        : null,
    isAllDay: j['is_all_day'] ?? false,
    daysUntil: j['days_until'] ?? 0,
  );
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
  });

  factory AnnouncementModel.fromJson(Map<String, dynamic> j) =>
      AnnouncementModel(
        id: j['id'],
        title: j['title'],
        message: j['message'],
        type: j['type'] ?? 'general',
        typeLabel: j['type_label'] ?? '',
        typeColor: j['type_color'] ?? 'gray',
        typeIcon: j['type_icon'] ?? '📢',
        author: j['author'] ?? 'Admin',
        publishAt: j['publish_at'] != null
            ? DateTime.parse(j['publish_at'])
            : null,
        createdAt: DateTime.parse(j['created_at']),
      );

  DateTime get displayDate => publishAt ?? createdAt;
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

  const DashboardStats({
    required this.totalChildren,
    required this.blockedChildren,
    required this.accessibleChildren,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> j) => DashboardStats(
    totalChildren: j['total_children'] ?? 0,
    blockedChildren: j['blocked_children'] ?? 0,
    accessibleChildren: j['accessible_children'] ?? 0,
  );
}
