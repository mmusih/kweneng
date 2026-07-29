import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/api_service.dart';
import '../services/cache_service.dart';
import '../services/notification_service.dart';
import '../models/flutter_models.dart';

// ============================================================
// Core providers
// ============================================================
final apiServiceProvider = Provider<ApiService>((ref) => ApiService());
final cacheServiceProvider = Provider<CacheService>((ref) => CacheService());

// ============================================================
// Auth state
// ============================================================
class AuthState {
  final bool isAuthenticated;
  final bool isLoading;
  final UserModel? user;
  final String? error;

  const AuthState({
    this.isAuthenticated = false,
    this.isLoading = false,
    this.user,
    this.error,
  });

  bool get mustChangePassword => user?.mustChangePassword ?? false;

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    UserModel? user,
    String? error,
    bool clearError = false,
  }) {
    return AuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      user: user ?? this.user,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    _restoreSession();
    return const AuthState();
  }

  Future<void> _restoreSession() async {
    final api = ref.read(apiServiceProvider);
    final hasToken = await api.hasToken();

    if (!hasToken) return;

    try {
      final storedUser = await api.getStoredUser();
      UserModel? user;

      if (storedUser != null) {
        user = UserModel.fromJson(storedUser);
      } else {
        final userData = await api.me();
        await api.saveUser(userData);
        user = UserModel.fromJson(userData);
      }

      state = state.copyWith(
        isAuthenticated: true,
        isLoading: false,
        user: user,
        clearError: true,
      );

      await NotificationService.instance.syncTokenToServer();
    } catch (_) {
      await api.logout();
      state = const AuthState();
    }
  }

  Future<void> applyAuthResponse(Map<String, dynamic> res) async {
    final api = ref.read(apiServiceProvider);

    final token = res['token']?.toString();
    final rawUser = res['user'];

    if (token == null || token.isEmpty || rawUser == null) {
      throw Exception('Invalid authentication response from server.');
    }

    final userMap = Map<String, dynamic>.from(rawUser as Map);

    await api.saveToken(token);
    await api.saveUser(userMap);

    final user = UserModel.fromJson(userMap);

    state = state.copyWith(
      isAuthenticated: true,
      isLoading: false,
      user: user,
      clearError: true,
    );

    await NotificationService.instance.syncTokenToServer();
  }

  Future<bool> login(String email, String password) async {
    state = state.copyWith(isLoading: true, clearError: true);

    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.login(
        email: email.trim(),
        password: password.trim(),
        deviceName: 'kweneng-android',
      );

      await applyAuthResponse(res);
      return true;
    } catch (_) {
      state = state.copyWith(
        isLoading: false,
        error: 'Login failed. Please check your credentials.',
      );
      return false;
    }
  }

  Future<bool> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);

    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.changePassword(
        currentPassword: currentPassword,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );

      final rawUser = res['user'];
      Map<String, dynamic> userMap;

      if (rawUser is Map) {
        userMap = Map<String, dynamic>.from(rawUser);
      } else {
        userMap = await api.me();
      }

      await api.saveUser(userMap);
      final user = UserModel.fromJson(userMap);

      state = state.copyWith(
        isAuthenticated: true,
        isLoading: false,
        user: user,
        clearError: true,
      );

      await NotificationService.instance.syncTokenToServer();
      return true;
    } catch (_) {
      state = state.copyWith(
        isLoading: false,
        error:
            'Could not change password. Please check your current password and try again.',
      );
      return false;
    }
  }

  Future<void> logout() async {
    final api = ref.read(apiServiceProvider);
    final cache = ref.read(cacheServiceProvider);
    await api.logout();
    await cache.clearAll();
    state = const AuthState();
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(
  AuthNotifier.new,
);

// ============================================================
// Offline-first helper
// ============================================================
Future<T> _offlineFirst<T>({
  required Future<Map<String, dynamic>> Function() fetch,
  required String cacheKey,
  required T Function(Map<String, dynamic>) parse,
}) async {
  final cache = CacheService();
  try {
    final data = await fetch();
    await cache.set(cacheKey, data);
    return parse(data);
  } catch (_) {
    final cached = await cache.get(cacheKey);
    if (cached != null) {
      return parse(cached as Map<String, dynamic>);
    }
    rethrow;
  }
}

// ============================================================
// Dashboard
// ============================================================
final dashboardProvider = FutureProvider<DashboardData>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getDashboard(),
    cacheKey: CacheKeys.dashboard,
    parse: DashboardData.fromJson,
  );
});

final timetableProvider = FutureProvider.family<TimetableData, int>((
  ref,
  studentId,
) async {
  final data = await ref.read(apiServiceProvider).getTimetable(studentId);
  return TimetableData.fromJson(data);
});

// ============================================================
// Events
// ============================================================
class EventsData {
  final List<EventModel> upcoming;
  final List<EventModel> past;
  const EventsData({required this.upcoming, required this.past});
}

final eventsProvider = FutureProvider<EventsData>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getEvents(),
    cacheKey: CacheKeys.events,
    parse: (data) => EventsData(
      upcoming: (data['upcoming'] as List? ?? [])
          .map((e) => EventModel.fromJson(e))
          .toList(),
      past: (data['past'] as List? ?? [])
          .map((e) => EventModel.fromJson(e))
          .toList(),
    ),
  );
});

// ============================================================
// Announcements
// ============================================================
class AnnouncementsData {
  final List<AnnouncementModel> urgent;
  final List<AnnouncementModel> general;

  /// Unread notices count. Used by badges/counters.
  final int total;

  /// All visible notices count.
  final int allCount;

  const AnnouncementsData({
    required this.urgent,
    required this.general,
    required this.total,
    required this.allCount,
  });

  int get unreadCount => total;
}

final announcementsProvider = FutureProvider<AnnouncementsData>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getAnnouncements(),
    cacheKey: CacheKeys.announcements,
    parse: (data) => AnnouncementsData(
      urgent: (data['urgent'] as List? ?? [])
          .map((a) => AnnouncementModel.fromJson(Map<String, dynamic>.from(a)))
          .toList(),
      general: (data['general'] as List? ?? [])
          .map((a) => AnnouncementModel.fromJson(Map<String, dynamic>.from(a)))
          .toList(),
      total: data['unread_count'] as int? ?? data['total'] as int? ?? 0,
      allCount:
          data['all_count'] as int? ??
          ((data['urgent'] as List? ?? []).length +
              (data['general'] as List? ?? []).length),
    ),
  );
});

final announcementDetailProvider =
    FutureProvider.family<AnnouncementModel, int>((ref, announcementId) async {
      final api = ref.read(apiServiceProvider);
      final data = await api.getAnnouncement(announcementId);
      final raw = data['announcement'] ?? data;

      final announcement = AnnouncementModel.fromJson(
        Map<String, dynamic>.from(raw),
      );

      // Opening the detail endpoint marks the notice as read on the backend.
      ref.invalidate(announcementsProvider);
      ref.invalidate(dashboardProvider);

      return announcement;
    });

class AcknowledgeAnnouncementNotifier extends Notifier<Map<int, bool>> {
  @override
  Map<int, bool> build() => {};

  bool isLoading(int id) => state[id] ?? false;

  Future<bool> acknowledge(int announcementId) async {
    state = {...state, announcementId: true};

    try {
      final api = ref.read(apiServiceProvider);
      await api.acknowledgeAnnouncement(announcementId);

      final updated = Map<int, bool>.from(state);
      updated.remove(announcementId);
      state = updated;

      ref.invalidate(announcementDetailProvider(announcementId));
      ref.invalidate(announcementsProvider);
      ref.invalidate(dashboardProvider);

      return true;
    } catch (_) {
      final updated = Map<int, bool>.from(state);
      updated.remove(announcementId);
      state = updated;
      return false;
    }
  }
}

final acknowledgeAnnouncementProvider =
    NotifierProvider<AcknowledgeAnnouncementNotifier, Map<int, bool>>(
      AcknowledgeAnnouncementNotifier.new,
    );

// ── Dismiss announcement ──────────────────────────────────────────────────────
//
// Single notifier holds a Map<announcementId, isDismissing>.
// No family needed — one map covers all announcements.

class DismissNotifier extends Notifier<Map<int, bool>> {
  @override
  Map<int, bool> build() => {};

  /// Returns true if this announcement is currently being dismissed.
  bool isDismissing(int id) => state[id] ?? false;

  Future<void> dismiss(int announcementId) async {
    state = {...state, announcementId: true};
    try {
      final api = ref.read(apiServiceProvider);
      await api.dismissAnnouncement(announcementId);
      // Remove from map and refresh list.
      final updated = Map<int, bool>.from(state);
      updated.remove(announcementId);
      state = updated;
      ref.invalidate(announcementsProvider);
    } catch (_) {
      // On failure, stop spinning so the user can retry.
      final updated = Map<int, bool>.from(state);
      updated.remove(announcementId);
      state = updated;
    }
  }
}

final dismissNotifierProvider =
    NotifierProvider<DismissNotifier, Map<int, bool>>(DismissNotifier.new);

// ============================================================
// Marks
// ============================================================
final marksProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getMarks(),
    cacheKey: CacheKeys.marks,
    parse: (data) => data,
  );
});

// ============================================================
// Library
// ============================================================
final libraryProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getLibrary(),
    cacheKey: CacheKeys.library,
    parse: (data) => data,
  );
});

// ============================================================
// Fees
// ============================================================
final feesProvider = FutureProvider<ParentFeesData>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getFees(),
    cacheKey: CacheKeys.fees,
    parse: ParentFeesData.fromJson,
  );
});

// ============================================================
// Messages
// ============================================================

class MessageReply {
  final int id;
  final String senderRole; // 'parent' | 'admin'
  final String body;
  final DateTime sentAt;

  const MessageReply({
    required this.id,
    required this.senderRole,
    required this.body,
    required this.sentAt,
  });

  factory MessageReply.fromJson(Map<String, dynamic> j) => MessageReply(
    id: j['id'],
    senderRole: j['sender_role'] ?? 'admin',
    body: j['body'] ?? '',
    sentAt: DateTime.tryParse(j['created_at'] ?? '') ?? DateTime.now(),
  );
}

class MessageThread {
  final int id;
  final String subject;
  final String body;
  final bool isReadByParent;
  final DateTime? lastReplyAt;
  final List<MessageReply> replies;

  const MessageThread({
    required this.id,
    required this.subject,
    required this.body,
    required this.isReadByParent,
    this.lastReplyAt,
    this.replies = const [],
  });

  factory MessageThread.fromJson(Map<String, dynamic> j) => MessageThread(
    id: j['id'],
    subject: j['subject'] ?? '(no subject)',
    body: j['body'] ?? '',
    isReadByParent: j['is_read_by_parent'] ?? true,
    lastReplyAt: DateTime.tryParse(j['last_reply_at'] ?? ''),
    replies: (j['replies'] as List? ?? [])
        .map((r) => MessageReply.fromJson(r))
        .toList(),
  );

  bool get hasUnread => !isReadByParent;
}

class MessagesData {
  final List<MessageThread> threads;
  final int unreadCount;
  const MessagesData({required this.threads, required this.unreadCount});
}

final messagesProvider = FutureProvider<MessagesData>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getMessages();
  final threads = (data['messages'] as List? ?? [])
      .map((m) => MessageThread.fromJson(m))
      .toList();
  return MessagesData(
    threads: threads,
    unreadCount: data['unread_count'] as int? ?? 0,
  );
});

final messageDetailProvider = FutureProvider.family<MessageThread, int>((
  ref,
  id,
) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getMessage(id);
  return MessageThread.fromJson(data['message'] ?? data);
});

// ── Send new message ──────────────────────────────────────────────────────────

class SendMessageState {
  final bool isLoading;
  final bool success;
  final String? error;
  const SendMessageState({
    this.isLoading = false,
    this.success = false,
    this.error,
  });
}

class SendMessageNotifier extends Notifier<SendMessageState> {
  @override
  SendMessageState build() => const SendMessageState();

  Future<bool> send({required String subject, required String body}) async {
    state = const SendMessageState(isLoading: true);
    try {
      final api = ref.read(apiServiceProvider);
      await api.createMessage(subject: subject, body: body);
      state = const SendMessageState(success: true);
      ref.invalidate(messagesProvider);
      return true;
    } catch (e) {
      state = SendMessageState(error: 'Failed to send: $e');
      return false;
    }
  }

  void reset() => state = const SendMessageState();
}

final sendMessageProvider =
    NotifierProvider<SendMessageNotifier, SendMessageState>(
      SendMessageNotifier.new,
    );

// ── Reply to thread ───────────────────────────────────────────────────────────
//
// Single notifier holds reply state for all threads: Map<messageId, ReplyState>

class ReplyState {
  final bool isLoading;
  final bool success;
  final String? error;
  const ReplyState({this.isLoading = false, this.success = false, this.error});
}

class ReplyNotifier extends Notifier<Map<int, ReplyState>> {
  @override
  Map<int, ReplyState> build() => {};

  ReplyState stateFor(int messageId) => state[messageId] ?? const ReplyState();

  Future<bool> reply(int messageId, String body) async {
    state = {...state, messageId: const ReplyState(isLoading: true)};
    try {
      final api = ref.read(apiServiceProvider);
      await api.replyToMessage(messageId: messageId, body: body);
      state = {...state, messageId: const ReplyState(success: true)};
      ref.invalidate(messageDetailProvider(messageId));
      ref.invalidate(messagesProvider);
      return true;
    } catch (e) {
      state = {
        ...state,
        messageId: ReplyState(error: 'Failed to send reply: $e'),
      };
      return false;
    }
  }

  void reset(int messageId) {
    final updated = Map<int, ReplyState>.from(state);
    updated.remove(messageId);
    state = updated;
  }
}

final replyProvider = NotifierProvider<ReplyNotifier, Map<int, ReplyState>>(
  ReplyNotifier.new,
);

// ============================================================
// School Documents
// ============================================================

class SchoolDocument {
  final int id;
  final String title;
  final String category;
  final String categoryLabel;
  final String categoryIcon;
  final String originalFilename;
  final String? academicYear;

  const SchoolDocument({
    required this.id,
    required this.title,
    required this.category,
    required this.categoryLabel,
    required this.categoryIcon,
    required this.originalFilename,
    this.academicYear,
  });

  factory SchoolDocument.fromJson(Map<String, dynamic> j) => SchoolDocument(
    id: j['id'],
    title: j['title'] ?? 'Untitled',
    category: j['category'] ?? '',
    categoryLabel: j['category_label'] ?? j['category'] ?? '',
    categoryIcon: j['category_icon'] ?? '📄',
    originalFilename: j['original_filename'] ?? 'document',
    academicYear: j['academic_year']?['name'],
  );
}

class DocumentsData {
  final List<SchoolDocument> documents;
  final Map<String, List<SchoolDocument>> grouped;
  const DocumentsData({required this.documents, required this.grouped});
}

final schoolDocumentsProvider = FutureProvider<DocumentsData>((ref) async {
  final api = ref.read(apiServiceProvider);
  return _offlineFirst(
    fetch: () async => await api.getSchoolDocuments(),
    cacheKey: CacheKeys.documents,
    parse: (data) {
      final docs = (data['documents'] as List? ?? [])
          .map((d) => SchoolDocument.fromJson(d))
          .toList();
      final grouped = <String, List<SchoolDocument>>{};
      for (final d in docs) {
        grouped.putIfAbsent(d.categoryLabel, () => []).add(d);
      }
      return DocumentsData(documents: docs, grouped: grouped);
    },
  );
});

// ── Document download state ───────────────────────────────────────────────────
//
// Single notifier holds download state for all documents: Map<docId, DocDownloadState>

enum DocDownloadStatus { idle, downloading, done, error }

class DocDownloadState {
  final DocDownloadStatus status;
  final String? filePath;
  final String? errorMsg;
  const DocDownloadState({
    this.status = DocDownloadStatus.idle,
    this.filePath,
    this.errorMsg,
  });
}

class DocDownloadNotifier extends Notifier<Map<int, DocDownloadState>> {
  @override
  Map<int, DocDownloadState> build() => {};

  DocDownloadState stateFor(int docId) =>
      state[docId] ?? const DocDownloadState();

  Future<void> download(int docId, String filename) async {
    state = {
      ...state,
      docId: const DocDownloadState(status: DocDownloadStatus.downloading),
    };
    try {
      final file = await ApiService().downloadSchoolDocument(docId, filename);
      state = {
        ...state,
        docId: DocDownloadState(
          status: DocDownloadStatus.done,
          filePath: file.path,
        ),
      };
    } catch (e) {
      state = {
        ...state,
        docId: DocDownloadState(
          status: DocDownloadStatus.error,
          errorMsg: 'Download failed: $e',
        ),
      };
    }
  }

  void reset(int docId) {
    final updated = Map<int, DocDownloadState>.from(state);
    updated.remove(docId);
    state = updated;
  }
}

final docDownloadProvider =
    NotifierProvider<DocDownloadNotifier, Map<int, DocDownloadState>>(
      DocDownloadNotifier.new,
    );

// ============================================================
// Parent Absence Notices
// ============================================================
final absenceNoticesProvider = FutureProvider<AbsenceNoticesData>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getAbsenceNotices();
  return AbsenceNoticesData.fromJson(data);
});

class SubmitAbsenceNoticeState {
  final bool isLoading;
  final bool success;
  final String? error;

  const SubmitAbsenceNoticeState({
    this.isLoading = false,
    this.success = false,
    this.error,
  });
}

class SubmitAbsenceNoticeNotifier extends Notifier<SubmitAbsenceNoticeState> {
  @override
  SubmitAbsenceNoticeState build() => const SubmitAbsenceNoticeState();

  Future<bool> submit({
    required int studentId,
    required DateTime absenceDate,
    DateTime? expectedReturnDate,
    required String reason,
    String? note,
  }) async {
    state = const SubmitAbsenceNoticeState(isLoading: true);

    try {
      final api = ref.read(apiServiceProvider);

      await api.submitAbsenceNotice(
        studentId: studentId,
        absenceDate: absenceDate.toIso8601String().split('T').first,
        expectedReturnDate: expectedReturnDate
            ?.toIso8601String()
            .split('T')
            .first,
        reason: reason,
        note: note,
      );

      state = const SubmitAbsenceNoticeState(success: true);
      ref.invalidate(absenceNoticesProvider);
      ref.invalidate(dashboardProvider);
      return true;
    } catch (e) {
      state = SubmitAbsenceNoticeState(
        error: 'Failed to submit absence notice: $e',
      );
      return false;
    }
  }

  void reset() => state = const SubmitAbsenceNoticeState();
}

final submitAbsenceNoticeProvider =
    NotifierProvider<SubmitAbsenceNoticeNotifier, SubmitAbsenceNoticeState>(
      SubmitAbsenceNoticeNotifier.new,
    );

// ============================================================
// Cache freshness
// ============================================================
final cacheFreshnessProvider = FutureProvider.family<String?, String>((
  ref,
  key,
) async {
  return CacheService().lastUpdatedLabel(key);
});

// ============================================================
// Homework
// ============================================================
final homeworkProvider = FutureProvider<ParentHomeworkData>((ref) async {
  final api = ref.read(apiServiceProvider);
  final result = await _offlineFirst(
    fetch: () async => await api.getParentHomework(),
    cacheKey: CacheKeys.homework,
    parse: ParentHomeworkData.fromJson,
  );

  // Opening homework marks current records as read server-side.
  ref.invalidate(dashboardProvider);
  return result;
});

class ChildProfileUpdateState {
  final bool isLoading;
  final bool success;
  final String? error;

  const ChildProfileUpdateState({
    this.isLoading = false,
    this.success = false,
    this.error,
  });
}

class ChildProfileUpdateNotifier extends Notifier<ChildProfileUpdateState> {
  @override
  ChildProfileUpdateState build() => const ChildProfileUpdateState();

  Future<bool> update({
    required int studentId,
    required String nationality,
    required String identityDocumentType,
    required String identityDocumentNumber,
    required String emergencyContactName,
    required String emergencyContactRelationship,
    required String emergencyContactPhone,
    String? emergencyContactAltPhone,
    String? emergencyContactAddress,
    String? medicalNotes,
  }) async {
    state = const ChildProfileUpdateState(isLoading: true);

    try {
      final api = ref.read(apiServiceProvider);
      await api.updateChildProfile(
        studentId: studentId,
        nationality: nationality,
        identityDocumentType: identityDocumentType,
        identityDocumentNumber: identityDocumentNumber,
        emergencyContactName: emergencyContactName,
        emergencyContactRelationship: emergencyContactRelationship,
        emergencyContactPhone: emergencyContactPhone,
        emergencyContactAltPhone: emergencyContactAltPhone,
        emergencyContactAddress: emergencyContactAddress,
        medicalNotes: medicalNotes,
      );

      ref.invalidate(dashboardProvider);
      ref.invalidate(homeworkProvider);
      state = const ChildProfileUpdateState(success: true);
      return true;
    } catch (e) {
      state = ChildProfileUpdateState(error: 'Could not update profile. $e');
      return false;
    }
  }

  void reset() => state = const ChildProfileUpdateState();
}

final childProfileUpdateProvider =
    NotifierProvider<ChildProfileUpdateNotifier, ChildProfileUpdateState>(
      ChildProfileUpdateNotifier.new,
    );
