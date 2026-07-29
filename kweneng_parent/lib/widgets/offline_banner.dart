import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/flutter_providers.dart';

/// Shows a slim orange banner when the app is serving cached (offline) data.
///
/// Usage — add to the top of any screen's Column body:
///
///   body: Column(
///     children: [
///       OfflineBanner(cacheKey: CacheKeys.dashboard, isOffline: _isOffline),
///       Expanded(child: ...your content...),
///     ],
///   )
///
/// Set [isOffline] to true when the last provider fetch threw an error but
/// cached data was returned instead. Example:
///
///   final dataAsync = ref.watch(dashboardProvider);
///   final isOffline = dataAsync is AsyncError;  // or track via ref.listen
class OfflineBanner extends ConsumerWidget {
  final String cacheKey;
  final bool isOffline;

  const OfflineBanner({
    super.key,
    required this.cacheKey,
    this.isOffline = false,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (!isOffline) return const SizedBox.shrink();

    final freshnessAsync = ref.watch(cacheFreshnessProvider(cacheKey));

    final label = freshnessAsync.when(
      data: (v) => v,
      loading: () => null,
      error: (_, _) => null,
    );
    if (label == null) return const SizedBox.shrink();

    return Material(
      color: Colors.orange.shade700,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: Row(
          children: [
            const Icon(Icons.wifi_off, size: 16, color: Colors.white),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'Offline — showing data from $label',
                style: const TextStyle(color: Colors.white, fontSize: 13),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Standard no-cache error screen shown when offline AND nothing is cached.
/// Drop this inside your provider's error branch.
///
/// Usage:
///   dataAsync.when(
///     error: (e, _) => NoConnectionScreen(onRetry: () => ref.invalidate(myProvider)),
///     ...
///   )
class NoConnectionScreen extends StatelessWidget {
  final VoidCallback onRetry;
  const NoConnectionScreen({super.key, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off, size: 52, color: Colors.grey),
            const SizedBox(height: 16),
            const Text(
              'No internet connection',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'Connect to the internet to load data for the first time.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}
