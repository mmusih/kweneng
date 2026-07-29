import 'dart:io';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/models.dart';
import '../services/api_service.dart';

final apiProvider = Provider<ApiService>((ref) => ApiService());

class AuthState {
  final bool isAuthenticated;
  final bool isLoading;
  final TeacherUser? user;
  final String? error;

  const AuthState({
    this.isAuthenticated = false,
    this.isLoading = false,
    this.user,
    this.error,
  });

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    TeacherUser? user,
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
    _restore();
    return const AuthState(isLoading: true);
  }

  Future<void> _restore() async {
    final api = ref.read(apiProvider);
    if (!await api.hasToken()) {
      state = const AuthState();
      return;
    }

    final userMap = await api.storedUser();
    if (userMap == null) {
      await api.logout();
      state = const AuthState();
      return;
    }

    state = AuthState(
      isAuthenticated: true,
      user: TeacherUser.fromJson(userMap),
    );
  }

  Future<bool> login(String email, String password) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final api = ref.read(apiProvider);
      final res = await api.teacherLogin(email: email, password: password);
      final token = res['token']?.toString();
      final userMap = res['user'] is Map
          ? Map<String, dynamic>.from(res['user'] as Map)
          : null;
      if (token == null || token.isEmpty || userMap == null) {
        throw const ApiException('Invalid login response from server.');
      }
      await api.saveAuth(token: token, user: userMap);
      state = AuthState(
        isAuthenticated: true,
        user: TeacherUser.fromJson(userMap),
      );
      ref.invalidate(teacherDashboardProvider);
      return true;
    } catch (e) {
      state = AuthState(error: e.toString());
      return false;
    }
  }

  Future<void> logout() async {
    await ref.read(apiProvider).logout();
    ref.invalidate(teacherDashboardProvider);
    state = const AuthState();
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(
  AuthNotifier.new,
);

final teacherDashboardProvider = FutureProvider<TeacherDashboardData>((
  ref,
) async {
  final data = await ref.read(apiProvider).dashboard();
  return TeacherDashboardData.fromJson(data);
});

final teacherTimetableProvider = FutureProvider<TimetableData>((ref) async {
  final data = await ref.read(apiProvider).timetable();
  return TimetableData.fromJson(data);
});

class AttendanceArgs {
  final int classId;
  final DateTime date;
  const AttendanceArgs({required this.classId, required this.date});

  @override
  bool operator ==(Object other) =>
      other is AttendanceArgs &&
      other.classId == classId &&
      other.date.toIso8601String().split('T').first ==
          date.toIso8601String().split('T').first;

  @override
  int get hashCode =>
      Object.hash(classId, date.toIso8601String().split('T').first);
}

final attendanceProvider =
    FutureProvider.family<AttendanceRegisterData, AttendanceArgs>((
      ref,
      args,
    ) async {
      final data = await ref
          .read(apiProvider)
          .attendanceRegister(classId: args.classId, date: args.date);
      return AttendanceRegisterData.fromJson(data);
    });

class MarkSheetArgs {
  final int classId;
  final int subjectId;
  const MarkSheetArgs({required this.classId, required this.subjectId});

  @override
  bool operator ==(Object other) =>
      other is MarkSheetArgs &&
      other.classId == classId &&
      other.subjectId == subjectId;

  @override
  int get hashCode => Object.hash(classId, subjectId);
}

final markSheetProvider = FutureProvider.family<MarkSheetData, MarkSheetArgs>((
  ref,
  args,
) async {
  final data = await ref
      .read(apiProvider)
      .markSheet(classId: args.classId, subjectId: args.subjectId);
  return MarkSheetData.fromJson(data);
});

final homeworksProvider = FutureProvider<List<TeacherHomework>>((ref) async {
  final data = await ref.read(apiProvider).homeworks();
  return (data['homeworks'] as List? ?? [])
      .map(
        (item) =>
            TeacherHomework.fromJson(Map<String, dynamic>.from(item as Map)),
      )
      .toList();
});

final schemesProvider = FutureProvider<List<TeacherSchemeSummary>>((ref) async {
  final data = await ref.read(apiProvider).schemes();
  return data.map(TeacherSchemeSummary.fromJson).toList();
});

final schemeDetailProvider = FutureProvider.family<TeacherSchemeDetail, int>((
  ref,
  schemeId,
) async {
  final data = await ref.read(apiProvider).scheme(schemeId);
  return TeacherSchemeDetail.fromJson(data);
});

class MutationState {
  final bool isLoading;
  final String? error;
  final String? message;

  const MutationState({this.isLoading = false, this.error, this.message});
}

class AttendanceSaveNotifier extends Notifier<MutationState> {
  @override
  MutationState build() => const MutationState();

  Future<bool> save({
    required int classId,
    required DateTime date,
    required List<AttendanceStudent> students,
  }) async {
    state = const MutationState(isLoading: true);
    try {
      await ref
          .read(apiProvider)
          .saveAttendance(
            classId: classId,
            date: date,
            students: students
                .map(
                  (s) => {
                    'student_id': s.id,
                    'status': s.status,
                    'remarks': s.remarks,
                  },
                )
                .toList(),
          );
      ref.invalidate(
        attendanceProvider(AttendanceArgs(classId: classId, date: date)),
      );
      state = const MutationState(message: 'Attendance saved.');
      return true;
    } catch (e) {
      state = MutationState(error: e.toString());
      return false;
    }
  }
}

final attendanceSaveProvider =
    NotifierProvider<AttendanceSaveNotifier, MutationState>(
      AttendanceSaveNotifier.new,
    );

class MarksSaveNotifier extends Notifier<MutationState> {
  @override
  MutationState build() => const MutationState();

  Future<bool> save({
    required int classId,
    required int subjectId,
    required List<MarkStudent> students,
  }) async {
    state = const MutationState(isLoading: true);
    try {
      await ref
          .read(apiProvider)
          .saveMarks(
            classId: classId,
            subjectId: subjectId,
            marks: students
                .map(
                  (s) => {
                    'student_id': s.id,
                    'midterm_score': s.midtermScore,
                    'endterm_score': s.endtermScore,
                    'remarks': s.remarks,
                  },
                )
                .toList(),
          );
      ref.invalidate(
        markSheetProvider(
          MarkSheetArgs(classId: classId, subjectId: subjectId),
        ),
      );
      state = const MutationState(message: 'Marks saved.');
      return true;
    } catch (e) {
      state = MutationState(error: e.toString());
      return false;
    }
  }
}

final marksSaveProvider = NotifierProvider<MarksSaveNotifier, MutationState>(
  MarksSaveNotifier.new,
);

class HomeworkSendNotifier extends Notifier<MutationState> {
  @override
  MutationState build() => const MutationState();

  Future<bool> send({
    required int classId,
    required int subjectId,
    required String clientRequestId,
    required File image,
    String? title,
    String? description,
    DateTime? dueDate,
    bool isGraded = false,
    double? totalMarks,
  }) async {
    state = const MutationState(isLoading: true);
    try {
      await ref
          .read(apiProvider)
          .sendHomework(
            classId: classId,
            subjectId: subjectId,
            clientRequestId: clientRequestId,
            image: image,
            title: title,
            description: description,
            dueDate: dueDate,
            isGraded: isGraded,
            totalMarks: totalMarks,
          );
      ref.invalidate(homeworksProvider);
      ref.invalidate(teacherDashboardProvider);
      state = const MutationState(message: 'Homework sent.');
      return true;
    } catch (e) {
      state = MutationState(error: e.toString());
      return false;
    }
  }
}

final homeworkSendProvider =
    NotifierProvider<HomeworkSendNotifier, MutationState>(
      HomeworkSendNotifier.new,
    );

class HomeworkDeleteNotifier extends Notifier<MutationState> {
  @override
  MutationState build() => const MutationState();

  Future<bool> delete(int homeworkId) async {
    state = const MutationState(isLoading: true);
    try {
      await ref.read(apiProvider).deleteHomework(homeworkId);
      ref.invalidate(homeworksProvider);
      ref.invalidate(teacherDashboardProvider);
      state = const MutationState(message: 'Homework deleted.');
      return true;
    } catch (e) {
      state = MutationState(error: e.toString());
      return false;
    }
  }
}

final homeworkDeleteProvider =
    NotifierProvider<HomeworkDeleteNotifier, MutationState>(
      HomeworkDeleteNotifier.new,
    );
