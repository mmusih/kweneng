import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/constants.dart';

class ApiException implements Exception {
  final String message;
  const ApiException(this.message);
  @override
  String toString() => message;
}

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  final _storage = const FlutterSecureStorage();
  late final Dio dio = _createDio();

  Dio _createDio() {
    final client = Dio(
      BaseOptions(
        baseUrl: AppConstants.baseUrl,
        connectTimeout: const Duration(seconds: 20),
        receiveTimeout: const Duration(seconds: 30),
        headers: {'Accept': 'application/json'},
      ),
    );

    client.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await getToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) {
          return handler.next(error);
        },
      ),
    );

    return client;
  }

  String _messageFromError(Object error) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map) {
        if (data['message'] != null) return data['message'].toString();
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
          return first.toString();
        }
      }
      return error.message ?? 'Network request failed.';
    }
    return error.toString();
  }

  Future<T> guard<T>(Future<T> Function() run) async {
    try {
      return await run();
    } catch (e) {
      throw ApiException(_messageFromError(e));
    }
  }

  Future<Map<String, dynamic>> teacherLogin({
    required String email,
    required String password,
  }) async {
    return guard(() async {
      final res = await dio.post(
        '/auth/teacher-login',
        data: {
          'email': email.trim(),
          'password': password.trim(),
          'device_name': 'kweneng-teacher-android',
        },
      );
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<String> requestPasswordReset(String email) async {
    return guard(() async {
      final res = await dio.post(
        '/auth/forgot-password',
        data: {'email': email.trim()},
      );
      final data = Map<String, dynamic>.from(res.data as Map);
      return data['message']?.toString() ??
          'If an account matches that email address, a password reset link has been sent.';
    });
  }

  Future<void> logout() async {
    try {
      await dio.post('/auth/logout');
    } catch (_) {}
    await _storage.delete(key: AppConstants.tokenKey);
    await _storage.delete(key: AppConstants.userKey);
  }

  Future<void> saveAuth({
    required String token,
    required Map<String, dynamic> user,
  }) async {
    await _storage.write(key: AppConstants.tokenKey, value: token);
    await _storage.write(key: AppConstants.userKey, value: jsonEncode(user));
  }

  Future<Map<String, dynamic>?> storedUser() async {
    final raw = await _storage.read(key: AppConstants.userKey);
    if (raw == null || raw.isEmpty) return null;
    final decoded = jsonDecode(raw);
    return decoded is Map ? Map<String, dynamic>.from(decoded) : null;
  }

  Future<String?> getToken() => _storage.read(key: AppConstants.tokenKey);

  Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  Future<Map<String, String>> authorizedHeaders() async {
    final token = await getToken();
    return {
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> dashboard() async {
    return guard(() async {
      final res = await dio.get('/teacher/dashboard');
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> timetable() async {
    return guard(() async {
      final res = await dio.get('/teacher/timetable');
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> attendanceRegister({
    required int classId,
    required DateTime date,
  }) async {
    return guard(() async {
      final res = await dio.get(
        '/teacher/attendance/register',
        queryParameters: {'class_id': classId, 'date': _date(date)},
      );
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> saveAttendance({
    required int classId,
    required DateTime date,
    required List<Map<String, dynamic>> students,
  }) async {
    return guard(() async {
      final res = await dio.post(
        '/teacher/attendance/register',
        data: {'class_id': classId, 'date': _date(date), 'students': students},
      );
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> markSheet({
    required int classId,
    required int subjectId,
  }) async {
    return guard(() async {
      final res = await dio.get(
        '/teacher/marks/sheet',
        queryParameters: {'class_id': classId, 'subject_id': subjectId},
      );
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> saveMarks({
    required int classId,
    required int subjectId,
    required List<Map<String, dynamic>> marks,
  }) async {
    return guard(() async {
      final res = await dio.post(
        '/teacher/marks/sheet',
        data: {'class_id': classId, 'subject_id': subjectId, 'marks': marks},
      );
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> homeworks({int? classId, int? subjectId}) async {
    return guard(() async {
      final res = await dio.get(
        '/teacher/homeworks',
        queryParameters: {'class_id': ?classId, 'subject_id': ?subjectId},
      );
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<Map<String, dynamic>> sendHomework({
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
    return guard(() async {
      final form = FormData.fromMap({
        'class_id': classId,
        'subject_id': subjectId,
        'client_request_id': clientRequestId,
        if (title != null && title.trim().isNotEmpty) 'title': title.trim(),
        if (description != null && description.trim().isNotEmpty)
          'description': description.trim(),
        if (dueDate != null) 'due_date': _date(dueDate),
        'is_graded': isGraded ? 1 : 0,
        if (isGraded && totalMarks != null) 'total_marks': totalMarks,
        'image': await MultipartFile.fromFile(
          image.path,
          filename: image.uri.pathSegments.last,
        ),
      });
      final res = await dio.post('/teacher/homeworks', data: form);
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<void> deleteHomework(int homeworkId) async {
    await guard(() async {
      await dio.delete('/teacher/homeworks/$homeworkId');
    });
  }

  Future<List<Map<String, dynamic>>> schemes() async {
    return guard(() async {
      final res = await dio.get('/teacher/schemes');
      return (res.data as List? ?? [])
          .map((item) => Map<String, dynamic>.from(item as Map))
          .toList();
    });
  }

  Future<Map<String, dynamic>> scheme(int schemeId) async {
    return guard(() async {
      final res = await dio.get('/teacher/schemes/$schemeId');
      return Map<String, dynamic>.from(res.data as Map);
    });
  }

  Future<void> updateSchemeTopicStatus({
    required int itemId,
    required String status,
    String? teacherComment,
  }) async {
    await guard(() async {
      await dio.patch(
        '/teacher/scheme-items/$itemId',
        data: {'status': status, 'teacher_comment': ?teacherComment},
      );
    });
  }

  Future<void> toggleSchemeSubtopic(int subtopicId) async {
    await guard(() async {
      await dio.patch('/teacher/scheme-subtopics/$subtopicId/toggle');
    });
  }

  String _date(DateTime value) => value.toIso8601String().split('T').first;
}
