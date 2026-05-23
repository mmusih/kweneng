import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

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

    // Attach token to every request
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.read(key: AppConstants.tokenKey);
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) {
          return handler.next(error);
        },
      ),
    );

    return dio;
  }

  // -------------------------------------------------------------------------
  // Auth
  // -------------------------------------------------------------------------

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final res = await _dio.post(
      '/auth/login',
      data: {'email': email, 'password': password, 'device_name': deviceName},
    );
    return res.data;
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } catch (_) {}
    await _storage.delete(key: AppConstants.tokenKey);
    await _storage.delete(key: AppConstants.userKey);
  }

  // -------------------------------------------------------------------------
  // Parent
  // -------------------------------------------------------------------------

  Future<Map<String, dynamic>> getDashboard() async {
    final res = await _dio.get('/parent/dashboard');
    return res.data;
  }

  Future<Map<String, dynamic>> getEvents() async {
    final res = await _dio.get('/parent/events');
    return res.data;
  }

  Future<Map<String, dynamic>> getAnnouncements() async {
    final res = await _dio.get('/parent/announcements');
    return res.data;
  }

  Future<Map<String, dynamic>> getMarks() async {
    final res = await _dio.get('/parent/marks');
    return res.data;
  }

  Future<Map<String, dynamic>> getChildMarks(
    int studentId,
    int academicYearId,
    int termId,
  ) async {
    final res = await _dio.get(
      '/parent/marks/$studentId/$academicYearId/$termId',
    );
    return res.data;
  }

  Future<Map<String, dynamic>> getLibrary() async {
    final res = await _dio.get('/parent/library');
    return res.data;
  }

  Future<Map<String, dynamic>> getLibraryHistory(int studentId) async {
    final res = await _dio.get('/parent/library/history/$studentId');
    return res.data;
  }

  // -------------------------------------------------------------------------
  // Token helpers
  // -------------------------------------------------------------------------

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
