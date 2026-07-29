import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Simple key-value JSON cache backed by SharedPreferences.
///
/// Each entry stores:
///   - the raw JSON string
///   - a timestamp so callers can show "last updated X ago"
class CacheService {
  static final CacheService _instance = CacheService._internal();
  factory CacheService() => _instance;
  CacheService._internal();

  static const String _tsPrefix = '__ts__';

  // ── write ──────────────────────────────────────────────────────────────────

  Future<void> set(String key, dynamic data) async {
    final prefs = await SharedPreferences.getInstance();
    final encoded = jsonEncode(data);
    await prefs.setString(key, encoded);
    await prefs.setInt('$_tsPrefix$key', DateTime.now().millisecondsSinceEpoch);
  }

  // ── read ───────────────────────────────────────────────────────────────────

  /// Returns the cached value, or null if nothing is stored.
  Future<dynamic> get(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(key);
    if (raw == null) return null;
    return jsonDecode(raw);
  }

  /// Returns when this key was last written, or null.
  Future<DateTime?> lastUpdated(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final ms = prefs.getInt('$_tsPrefix$key');
    return ms != null ? DateTime.fromMillisecondsSinceEpoch(ms) : null;
  }

  /// Human-readable "last updated" string, e.g. "2 min ago", "3 hours ago".
  Future<String?> lastUpdatedLabel(String key) async {
    final dt = await lastUpdated(key);
    if (dt == null) return null;
    final diff = DateTime.now().difference(dt);
    if (diff.inSeconds < 60) return 'just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes} min ago';
    if (diff.inHours < 24) return '${diff.inHours} hours ago';
    return '${diff.inDays} days ago';
  }

  // ── delete ─────────────────────────────────────────────────────────────────

  Future<void> delete(String key) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(key);
    await prefs.remove('$_tsPrefix$key');
  }

  /// Call on logout — wipes all cached data.
  Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}

// ── cache keys (single source of truth) ──────────────────────────────────────

class CacheKeys {
  static const String dashboard = 'cache_dashboard';
  static const String events = 'cache_events';
  static const String announcements = 'cache_announcements';
  static const String marks = 'cache_marks';
  static const String library = 'cache_library';
  static const String fees = 'cache_fees';
  static const String documents = 'cache_documents';
  static const String homework = 'cache_homework';
}
