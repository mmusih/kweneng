import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:path_provider/path_provider.dart';

import '../core/constants.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  static String normalizeParentCode(String value) {
    final buffer = StringBuffer();
    for (final codeUnit in value.codeUnits) {
      final isDigit = codeUnit >= 48 && codeUnit <= 57;
      final isUpper = codeUnit >= 65 && codeUnit <= 90;
      final isLower = codeUnit >= 97 && codeUnit <= 122;

      if (isDigit || isUpper || isLower) {
        buffer.writeCharCode(isLower ? codeUnit - 32 : codeUnit);
      }

      if (buffer.length >= 10) break;
    }
    return buffer.toString();
  }

  final _storage = const FlutterSecureStorage();
  late final Dio _dio = _createDio();

  Dio _createDio() {
    final dio = Dio(
      BaseOptions(
        baseUrl: AppConstants.baseUrl,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.read(key: AppConstants.tokenKey);
          // ── DEBUG ──────────────────────────────────────────────────────────
          debugPrint('┌─ API REQUEST ───────────────────────────────────');
          debugPrint('│ URL   : ${options.uri}');
          debugPrint('│ METHOD: ${options.method}');
          debugPrint('│ TOKEN : ${token ?? "⚠️  NO TOKEN"}');
          debugPrint('└─────────────────────────────────────────────────');
          // ──────────────────────────────────────────────────────────────────
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onResponse: (response, handler) {
          // ── DEBUG ──────────────────────────────────────────────────────────
          debugPrint('┌─ API RESPONSE ──────────────────────────────────');
          debugPrint('│ URL   : ${response.requestOptions.uri}');
          debugPrint('│ STATUS: ${response.statusCode}');
          debugPrint('└─────────────────────────────────────────────────');
          // ──────────────────────────────────────────────────────────────────
          return handler.next(response);
        },
        onError: (error, handler) {
          // ── DEBUG ──────────────────────────────────────────────────────────
          debugPrint('┌─ API ERROR ─────────────────────────────────────');
          debugPrint('│ URL   : ${error.requestOptions.uri}');
          debugPrint(
            '│ STATUS: ${error.response?.statusCode ?? "no response"}',
          );
          debugPrint('│ ERROR : ${error.message}');
          debugPrint('│ BODY  : ${error.response?.data}');
          debugPrint('└─────────────────────────────────────────────────');
          // ──────────────────────────────────────────────────────────────────
          return handler.next(error);
        },
      ),
    );

    return dio;
  }

  // ── Auth ──────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final res = await _dio.post(
      '/auth/login',
      data: {'email': email, 'password': password, 'device_name': deviceName},
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<String> requestPasswordReset(String email) async {
    final res = await _dio.post(
      '/auth/forgot-password',
      data: {'email': email.trim()},
    );
    return res.data['message']?.toString() ??
        'If an account matches that email address, a password reset link has been sent.';
  }

  Future<Map<String, dynamic>> me() async {
    final res = await _dio.get('/auth/me');
    return Map<String, dynamic>.from(res.data['user'] ?? res.data);
  }

  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    final res = await _dio.post(
      '/auth/change-password',
      data: {
        'current_password': currentPassword,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>?> getStoredUser() async {
    final raw = await _storage.read(key: AppConstants.userKey);
    if (raw == null || raw.isEmpty) return null;
    final decoded = jsonDecode(raw);
    if (decoded is Map<String, dynamic>) return decoded;
    if (decoded is Map) return Map<String, dynamic>.from(decoded);
    return null;
  }

  Future<void> saveUser(Map<String, dynamic> user) async {
    await _storage.write(key: AppConstants.userKey, value: jsonEncode(user));
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } catch (_) {}
    await _storage.delete(key: AppConstants.tokenKey);
    await _storage.delete(key: AppConstants.userKey);
  }

  // ── First-time parent registration ───────────────────────────────────────

  Future<Map<String, dynamic>> verifyParentCode(String inviteCode) async {
    final res = await _dio.post(
      '/parent-register/verify-code',
      data: {'invite_code': normalizeParentCode(inviteCode)},
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> completeParentRegistration({
    required String inviteCode,
    required String email,
    required String phone,
    required String relationship,
    required bool useExistingAccount,
    String? name,
    String? password,
    String? passwordConfirmation,
    String? existingPassword,
    String deviceName = 'kweneng-android',
  }) async {
    final data = <String, dynamic>{
      'invite_code': normalizeParentCode(inviteCode),
      'email': email.trim(),
      'phone': phone.trim(),
      'relationship': relationship,
      'use_existing_account': useExistingAccount,
      'device_name': deviceName,
    };

    if (useExistingAccount) {
      data['existing_password'] = existingPassword?.trim() ?? '';
    } else {
      data['name'] = name?.trim() ?? '';
      data['password'] = password?.trim() ?? '';
      data['password_confirmation'] = passwordConfirmation?.trim() ?? '';
    }

    final res = await _dio.post('/parent-register/complete', data: data);
    return Map<String, dynamic>.from(res.data);
  }

  // ── Parent – general ──────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getDashboard() async {
    final res = await _dio.get('/parent/dashboard');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getTimetable(int studentId) async {
    final res = await _dio.get(
      '/parent/timetable',
      queryParameters: {'student_id': studentId},
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getEvents() async {
    final res = await _dio.get('/parent/events');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getAnnouncements() async {
    final res = await _dio.get('/parent/announcements');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getAnnouncement(int announcementId) async {
    final res = await _dio.get('/parent/announcements/$announcementId');
    return Map<String, dynamic>.from(res.data);
  }

  Future<void> markAnnouncementRead(int announcementId) async {
    await _dio.post('/parent/announcements/$announcementId/read');
  }

  Future<Map<String, dynamic>> acknowledgeAnnouncement(
    int announcementId,
  ) async {
    final res = await _dio.post(
      '/parent/announcements/$announcementId/acknowledge',
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getMarks() async {
    final res = await _dio.get('/parent/marks');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getChildMarks(
    int studentId,
    int academicYearId,
    int termId,
  ) async {
    final res = await _dio.get(
      '/parent/marks/$studentId/$academicYearId/$termId',
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getLibrary() async {
    final res = await _dio.get('/parent/library');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getLibraryHistory(int studentId) async {
    final res = await _dio.get('/parent/library/history/$studentId');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getFees() async {
    final res = await _dio.get('/parent/fees');
    return Map<String, dynamic>.from(res.data);
  }

  // ── Report card PDF ───────────────────────────────────────────────────────

  Future<File> downloadReportCard(int studentId, int termId) async {
    final response = await _dio.get<List<int>>(
      '/parent/report-card/$studentId/$termId',
      options: Options(responseType: ResponseType.bytes),
    );

    if (response.data == null) {
      throw Exception('Empty response from server.');
    }

    final dir = await getApplicationDocumentsDirectory();
    final file = File('${dir.path}/report_card_${studentId}_term_$termId.pdf');
    await file.writeAsBytes(response.data!);
    return file;
  }

  // ── Announcements – dismiss ───────────────────────────────────────────────

  Future<void> dismissAnnouncement(int announcementId) async {
    await _dio.post('/parent/announcements/$announcementId/dismiss');
  }

  // ── Homework ──────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getParentHomework({
    bool previewOnly = false,
  }) async {
    final res = await _dio.get(
      '/parent/homework',
      queryParameters: previewOnly ? {'preview_only': 1} : null,
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<File> downloadHomeworkAttachment(
    int homeworkId,
    String filename,
  ) async {
    final response = await _dio.get<List<int>>(
      '/parent/homework/$homeworkId/attachment',
      options: Options(responseType: ResponseType.bytes),
    );

    if (response.data == null) {
      throw Exception('Empty response from server.');
    }

    final dir = await getApplicationDocumentsDirectory();
    final safe = filename.replaceAll(RegExp(r'[^\w.\-]'), '_');
    final file = File('${dir.path}/homework_${homeworkId}_$safe');
    await file.writeAsBytes(response.data!);
    return file;
  }

  Future<Map<String, String>> authorizedHeaders() async {
    final token = await getToken();
    return {
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> updateChildProfile({
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
    final res = await _dio.put(
      '/parent/children/$studentId/profile',
      data: {
        'nationality': nationality.trim(),
        'identity_document_type': identityDocumentType,
        'identity_document_number': identityDocumentNumber.trim(),
        'emergency_contact_name': emergencyContactName.trim(),
        'emergency_contact_relationship': emergencyContactRelationship.trim(),
        'emergency_contact_phone': emergencyContactPhone.trim(),
        'emergency_contact_alt_phone': emergencyContactAltPhone?.trim(),
        'emergency_contact_address': emergencyContactAddress?.trim(),
        'medical_notes': medicalNotes?.trim(),
      },
    );
    return Map<String, dynamic>.from(res.data);
  }

  // ── Messages ──────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getMessages() async {
    final res = await _dio.get('/parent/messages');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> getMessage(int messageId) async {
    final res = await _dio.get('/parent/messages/$messageId');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> createMessage({
    required String subject,
    required String body,
  }) async {
    final res = await _dio.post(
      '/parent/messages',
      data: {'subject': subject, 'body': body},
    );
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> replyToMessage({
    required int messageId,
    required String body,
  }) async {
    final res = await _dio.post(
      '/parent/messages/$messageId/reply',
      data: {'body': body},
    );
    return Map<String, dynamic>.from(res.data);
  }

  // ── School Documents ──────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getSchoolDocuments() async {
    final res = await _dio.get('/parent/documents');
    return Map<String, dynamic>.from(res.data);
  }

  Future<File> downloadSchoolDocument(int documentId, String filename) async {
    final response = await _dio.get<List<int>>(
      '/parent/documents/$documentId/download',
      options: Options(responseType: ResponseType.bytes),
    );

    if (response.data == null) {
      throw Exception('Empty response from server.');
    }

    final dir = await getApplicationDocumentsDirectory();
    final safe = filename.replaceAll(RegExp(r'[^\w.\-]'), '_');
    final file = File('${dir.path}/doc_${documentId}_$safe');
    await file.writeAsBytes(response.data!);
    return file;
  }

  // ── FCM Device Token ────────────────────────────────────────────────────────

  Future<void> saveFcmToken({
    required String token,
    required String platform,
  }) async {
    await _dio.post(
      '/parent/device-token',
      data: {'token': token, 'platform': platform},
    );
  }

  // ── Parent Absence Notices ───────────────────────────────────────────────

  Future<Map<String, dynamic>> getAbsenceNotices() async {
    final res = await _dio.get('/parent/absence-notices');
    return Map<String, dynamic>.from(res.data);
  }

  Future<Map<String, dynamic>> submitAbsenceNotice({
    required int studentId,
    required String absenceDate,
    String? expectedReturnDate,
    required String reason,
    String? note,
  }) async {
    final res = await _dio.post(
      '/parent/absence-notices',
      data: {
        'student_id': studentId,
        'absence_date': absenceDate,
        'expected_return_date': expectedReturnDate,
        'reason': reason,
        'note': note,
      },
    );

    return Map<String, dynamic>.from(res.data);
  }
  // ── Token helpers ─────────────────────────────────────────────────────────

  Future<void> saveToken(String token) async {
    await _storage.write(key: AppConstants.tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: AppConstants.tokenKey);
  }

  Future<bool> hasToken() async {
    final token = await _storage.read(key: AppConstants.tokenKey);
    return token != null && token.isNotEmpty;
  }
}
