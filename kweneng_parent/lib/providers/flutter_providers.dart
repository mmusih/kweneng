import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/api_service.dart';
import '../models/flutter_models.dart';

// ============================================================
// API Service provider
// ============================================================
final apiServiceProvider = Provider<ApiService>((ref) => ApiService());

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

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    UserModel? user,
    String? error,
  }) => AuthState(
    isAuthenticated: isAuthenticated ?? this.isAuthenticated,
    isLoading: isLoading ?? this.isLoading,
    user: user ?? this.user,
    error: error,
  );
}

// Riverpod 3.x uses Notifier instead of StateNotifier
class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    _checkToken();
    return const AuthState();
  }

  Future<void> _checkToken() async {
    final api = ref.read(apiServiceProvider);
    final hasToken = await api.hasToken();
    if (hasToken) {
      state = state.copyWith(isAuthenticated: true);
    }
  }

  Future<bool> login(String email, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.login(
        email: email,
        password: password,
        deviceName: 'kweneng-android',
      );
      await api.saveToken(res['token']);
      final user = UserModel.fromJson(res['user']);
      state = state.copyWith(
        isAuthenticated: true,
        isLoading: false,
        user: user,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Login failed. Please check your credentials.',
      );
      return false;
    }
  }

  Future<void> logout() async {
    final api = ref.read(apiServiceProvider);
    await api.logout();
    state = const AuthState();
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(
  AuthNotifier.new,
);

// ============================================================
// Dashboard
// ============================================================
final dashboardProvider = FutureProvider<DashboardData>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getDashboard();
  return DashboardData.fromJson(data);
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
  final data = await api.getEvents();
  return EventsData(
    upcoming: (data['upcoming'] as List)
        .map((e) => EventModel.fromJson(e))
        .toList(),
    past: (data['past'] as List).map((e) => EventModel.fromJson(e)).toList(),
  );
});

// ============================================================
// Announcements
// ============================================================
class AnnouncementsData {
  final List<AnnouncementModel> urgent;
  final List<AnnouncementModel> general;
  final int total;
  const AnnouncementsData({
    required this.urgent,
    required this.general,
    required this.total,
  });
}

final announcementsProvider = FutureProvider<AnnouncementsData>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getAnnouncements();
  return AnnouncementsData(
    urgent: (data['urgent'] as List)
        .map((a) => AnnouncementModel.fromJson(a))
        .toList(),
    general: (data['general'] as List)
        .map((a) => AnnouncementModel.fromJson(a))
        .toList(),
    total: data['total'] ?? 0,
  );
});

// ============================================================
// Marks
// ============================================================
final marksProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiServiceProvider);
  return await api.getMarks();
});

// ============================================================
// Library
// ============================================================
final libraryProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiServiceProvider);
  return await api.getLibrary();
});
