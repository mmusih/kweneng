import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';

import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';
import '../services/api_service.dart';

class AwardsScreen extends ConsumerStatefulWidget {
  const AwardsScreen({super.key});

  @override
  ConsumerState<AwardsScreen> createState() => _AwardsScreenState();
}

class _AwardsScreenState extends ConsumerState<AwardsScreen> {
  final Set<String> _downloads = {};

  @override
  Widget build(BuildContext context) {
    final dashboard = ref.watch(dashboardProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Awards & Leadership'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(dashboardProvider),
          ),
        ],
      ),
      body: dashboard.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _ErrorView(
          message: 'Could not load awards.\n$error',
          onRetry: () => ref.invalidate(dashboardProvider),
        ),
        data: (data) {
          final hasAchievements = data.children.any(
            (child) =>
                child.awards.isNotEmpty || child.prefectAppointments.isNotEmpty,
          );
          if (!hasAchievements) {
            return RefreshIndicator(
              onRefresh: () async => ref.refresh(dashboardProvider.future),
              child: ListView(
                children: const [
                  SizedBox(height: 120),
                  Icon(Icons.emoji_events_outlined, size: 64, color: Colors.grey),
                  SizedBox(height: 16),
                  Center(
                    child: Text(
                      'No published awards or leadership appointments yet.',
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: () async => ref.refresh(dashboardProvider.future),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: data.children
                  .where(
                    (child) =>
                        child.awards.isNotEmpty ||
                        child.prefectAppointments.isNotEmpty,
                  )
                  .map((child) => _childSection(child))
                  .toList(),
            ),
          );
        },
      ),
    );
  }

  Widget _childSection(ChildModel child) => Card(
    margin: const EdgeInsets.only(bottom: 16),
    clipBehavior: Clip.antiAlias,
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            child.name,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
          ),
          Text(
            [child.className, child.admissionNo]
                .where((value) => value != null && value.isNotEmpty)
                .join(' • '),
            style: const TextStyle(color: Colors.grey),
          ),
          if (child.prefectAppointments.isNotEmpty) ...[
            const SizedBox(height: 18),
            const _SectionTitle(
              icon: Icons.workspace_premium_outlined,
              label: 'Student leadership',
              color: Color(0xFF7C3AED),
            ),
            ...child.prefectAppointments.map(
              (prefect) => _PrefectCard(
                prefect: prefect,
                downloading: _downloads.contains('prefect-${prefect.id}'),
                onDownload: () => _downloadPrefect(child.id, prefect.id),
              ),
            ),
          ],
          if (child.awards.isNotEmpty) ...[
            const SizedBox(height: 18),
            const _SectionTitle(
              icon: Icons.emoji_events_outlined,
              label: 'Awards & achievements',
              color: Color(0xFFD97706),
            ),
            ...child.awards.map(
              (award) => _AwardCard(
                award: award,
                downloading: _downloads.contains('award-${award.id}'),
                onDownload: () => _downloadAward(child.id, award.id),
              ),
            ),
          ],
        ],
      ),
    ),
  );

  Future<void> _downloadAward(int studentId, int awardId) async {
    await _download(
      key: 'award-$awardId',
      getFile: () => ApiService().downloadAwardCertificate(
        studentId: studentId,
        awardId: awardId,
      ),
    );
  }

  Future<void> _downloadPrefect(int studentId, int prefectId) async {
    await _download(
      key: 'prefect-$prefectId',
      getFile: () => ApiService().downloadPrefectCertificate(
        studentId: studentId,
        prefectId: prefectId,
      ),
    );
  }

  Future<void> _download({
    required String key,
    required Future<File> Function() getFile,
  }) async {
    setState(() => _downloads.add(key));
    try {
      final file = await getFile();
      await OpenFilex.open(file.path);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not download the certificate. $error')),
        );
      }
    } finally {
      if (mounted) setState(() => _downloads.remove(key));
    }
  }
}

class _SectionTitle extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  const _SectionTitle({
    required this.icon,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Row(
      children: [
        Icon(icon, color: color),
        const SizedBox(width: 8),
        Text(label, style: TextStyle(fontWeight: FontWeight.w900, color: color)),
      ],
    ),
  );
}

class _AwardCard extends StatelessWidget {
  final StudentAward award;
  final bool downloading;
  final VoidCallback onDownload;
  const _AwardCard({
    required this.award,
    required this.downloading,
    required this.onDownload,
  });

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: const Color(0xFFFFFBEB),
      border: Border.all(color: const Color(0xFFFDE68A)),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(award.title, style: const TextStyle(fontWeight: FontWeight.w900)),
        const SizedBox(height: 3),
        Text(
          [award.academicYear, award.term]
              .where((value) => value != null && value.isNotEmpty)
              .join(' • '),
          style: const TextStyle(color: Colors.grey),
        ),
        if (award.position != null || award.percentage != null) ...[
          const SizedBox(height: 6),
          Text(
            [
              if (award.position != null) 'Position ${award.position}',
              if (award.percentage != null)
                '${award.percentage!.toStringAsFixed(1)}%',
            ].join(' • '),
            style: const TextStyle(
              color: Color(0xFFD97706),
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
        if (award.citation != null) ...[
          const SizedBox(height: 8),
          Text(award.citation!),
        ],
        const SizedBox(height: 10),
        FilledButton.icon(
          onPressed: downloading ? null : onDownload,
          icon: downloading
              ? const SizedBox.square(
                  dimension: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.download_outlined),
          label: const Text('Download certificate'),
        ),
      ],
    ),
  );
}

class _PrefectCard extends StatelessWidget {
  final PrefectAppointment prefect;
  final bool downloading;
  final VoidCallback onDownload;
  const _PrefectCard({
    required this.prefect,
    required this.downloading,
    required this.onDownload,
  });

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: const Color(0xFFF5F3FF),
      border: Border.all(color: const Color(0xFFDDD6FE)),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(prefect.title, style: const TextStyle(fontWeight: FontWeight.w900)),
        const SizedBox(height: 3),
        Text(
          [
            prefect.academicYear,
            _titleCase(prefect.status),
            if (prefect.appointedOn != null)
              DateFormat.yMMMd().format(prefect.appointedOn!),
          ].where((value) => value != null && value.isNotEmpty).join(' • '),
          style: const TextStyle(color: Colors.grey),
        ),
        if (prefect.duties != null) ...[
          const SizedBox(height: 8),
          Text('Duties: ${prefect.duties!}'),
        ],
        const SizedBox(height: 10),
        FilledButton.icon(
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFF7C3AED),
          ),
          onPressed: downloading ? null : onDownload,
          icon: downloading
              ? const SizedBox.square(
                  dimension: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.download_outlined),
          label: const Text('Download certificate'),
        ),
      ],
    ),
  );
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 12),
          FilledButton(onPressed: onRetry, child: const Text('Try again')),
        ],
      ),
    ),
  );
}

String _titleCase(String value) => value.isEmpty
    ? value
    : '${value[0].toUpperCase()}${value.substring(1).toLowerCase()}';
