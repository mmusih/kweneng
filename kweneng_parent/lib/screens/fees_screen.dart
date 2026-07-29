import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class FeesScreen extends ConsumerWidget {
  const FeesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final feesAsync = ref.watch(feesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Fees'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: () => ref.invalidate(feesProvider),
          ),
        ],
      ),
      body: feesAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: 'Could not load fees.',
          onRetry: () => ref.invalidate(feesProvider),
        ),
        data: (data) {
          if (data.children.isEmpty) {
            return const _EmptyState();
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(feesProvider),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _FeesSummary(data: data),
                const SizedBox(height: 14),
                const _InfoNotice(),
                const SizedBox(height: 14),
                ...data.children.map((child) => _FeeChildCard(child: child)),
                const SizedBox(height: 24),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _FeesSummary extends StatelessWidget {
  final ParentFeesData data;
  const _FeesSummary({required this.data});

  @override
  Widget build(BuildContext context) {
    final hasOutstanding = data.totalOutstanding > 0;
    final bg = hasOutstanding
        ? const Color(0xFFFEF2F2)
        : const Color(0xFFECFDF5);
    final border = hasOutstanding
        ? const Color(0xFFFECACA)
        : const Color(0xFFA7F3D0);
    final fg = hasOutstanding ? AppTheme.danger : AppTheme.success;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: border),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.75),
              shape: BoxShape.circle,
            ),
            child: Icon(
              hasOutstanding
                  ? Icons.account_balance_wallet_outlined
                  : Icons.verified_outlined,
              color: fg,
              size: 26,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  hasOutstanding
                      ? 'Outstanding balance'
                      : 'No outstanding balance',
                  style: TextStyle(
                    color: fg,
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  hasOutstanding
                      ? data.formattedTotalOutstanding
                      : '${data.clearCount} ${data.clearCount == 1 ? 'student is' : 'students are'} clear',
                  style: const TextStyle(
                    color: Color(0xFF111827),
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (data.notAvailableCount > 0) ...[
                  const SizedBox(height: 3),
                  Text(
                    '${data.notAvailableCount} balance${data.notAvailableCount == 1 ? '' : 's'} not available',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoNotice extends StatelessWidget {
  const _InfoNotice();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFEFF6FF),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFBFDBFE)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.info_outline, size: 20, color: AppTheme.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Balances are shown from the latest school fee record. If you recently made a payment, it may only reflect after the accounts office updates the record.',
              style: TextStyle(
                fontSize: 12,
                height: 1.35,
                color: Colors.grey.shade700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FeeChildCard extends StatelessWidget {
  final ParentFeeBalanceModel child;
  const _FeeChildCard({required this.child});

  @override
  Widget build(BuildContext context) {
    final status = _statusPresentation(child);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 22,
                  backgroundColor: status.color.withValues(alpha: 0.14),
                  child: Text(
                    _initial(child.studentName),
                    style: TextStyle(
                      color: status.color,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        child.studentName,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if ((child.className ?? '').isNotEmpty)
                        Text(
                          child.className!,
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                    ],
                  ),
                ),
                _StatusPill(label: status.label, color: status.color),
              ],
            ),
            const SizedBox(height: 14),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: status.color.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Closing balance',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    child.formattedClosingBalance,
                    style: TextStyle(
                      fontSize: 24,
                      color: status.color,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            _MetaRow(
              icon: Icons.school_outlined,
              label: 'Academic year',
              value: _dashIfEmpty(child.academicYear),
            ),
            _MetaRow(
              icon: Icons.calendar_today_outlined,
              label: 'Term',
              value: _dashIfEmpty(child.term),
            ),
            _MetaRow(
              icon: Icons.update_outlined,
              label: 'Last updated',
              value: _dateLabel(child.lastUpdated),
            ),
          ],
        ),
      ),
    );
  }

  _StatusPresentation _statusPresentation(ParentFeeBalanceModel child) {
    if (child.isOutstanding) {
      return const _StatusPresentation(
        label: 'Outstanding',
        color: AppTheme.danger,
      );
    }

    if (child.isClear) {
      return const _StatusPresentation(label: 'Clear', color: AppTheme.success);
    }

    return const _StatusPresentation(
      label: 'Not available',
      color: Color(0xFF64748B),
    );
  }

  String _initial(String name) {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return 'S';
    return trimmed.substring(0, 1).toUpperCase();
  }

  String _dashIfEmpty(String? value) {
    final text = value?.trim() ?? '';
    return text.isEmpty ? '—' : text;
  }

  String _dateLabel(DateTime? value) {
    if (value == null) return '—';
    final day = value.day.toString().padLeft(2, '0');
    final month = value.month.toString().padLeft(2, '0');
    final year = value.year.toString();
    final hour = value.hour.toString().padLeft(2, '0');
    final minute = value.minute.toString().padLeft(2, '0');
    return '$day/$month/$year $hour:$minute';
  }
}

class _StatusPresentation {
  final String label;
  final Color color;

  const _StatusPresentation({required this.label, required this.color});
}

class _StatusPill extends StatelessWidget {
  final String label;
  final Color color;

  const _StatusPill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(30),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _MetaRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _MetaRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 7),
      child: Row(
        children: [
          Icon(icon, size: 16, color: Colors.grey.shade500),
          const SizedBox(width: 8),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          const Spacer(),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off, size: 48, color: Colors.grey),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 12),
            ElevatedButton(onPressed: onRetry, child: const Text('Retry')),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.account_balance_wallet_outlined,
              size: 48,
              color: Colors.grey,
            ),
            SizedBox(height: 12),
            Text(
              'No fee balances available.',
              textAlign: TextAlign.center,
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            SizedBox(height: 6),
            Text(
              'The school has not published fee balances for this account yet.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }
}
