import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';

import '../providers/flutter_providers.dart';

// ═══════════════════════════════════════════════════════════════════════════════
// Documents Screen
// ═══════════════════════════════════════════════════════════════════════════════

class DocumentsScreen extends ConsumerWidget {
  const DocumentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(schoolDocumentsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('School Documents'),
        backgroundColor: const Color(0xFF2C3E6B),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: () => ref.invalidate(schoolDocumentsProvider),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: 'Failed to load documents\n$e',
          onRetry: () => ref.invalidate(schoolDocumentsProvider),
        ),
        data: (data) {
          if (data.documents.isEmpty) {
            return const _EmptyState(
              icon: Icons.folder_open_outlined,
              title: 'No documents available',
              subtitle: 'The school has not published any documents yet.',
            );
          }
          return _DocumentList(data: data);
        },
      ),
    );
  }
}

// ── Grouped document list ─────────────────────────────────────────────────────

class _DocumentList extends StatelessWidget {
  final DocumentsData data;
  const _DocumentList({required this.data});

  @override
  Widget build(BuildContext context) {
    final categories = data.grouped.keys.toList();

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: categories.length,
      itemBuilder: (context, i) {
        final label = categories[i];
        final docs = data.grouped[label]!;
        // Find icon from first doc in category (they share the same icon).
        final icon = docs.first.categoryIcon;
        return _CategorySection(label: label, icon: icon, documents: docs);
      },
    );
  }
}

// ── Category section ──────────────────────────────────────────────────────────

class _CategorySection extends StatelessWidget {
  final String label;
  final String icon;
  final List<SchoolDocument> documents;

  const _CategorySection({
    required this.label,
    required this.icon,
    required this.documents,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Section header
        Padding(
          padding: const EdgeInsets.only(bottom: 10, top: 4),
          child: Row(
            children: [
              Text(icon, style: const TextStyle(fontSize: 18)),
              const SizedBox(width: 8),
              Text(
                label,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF2C3E6B),
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFF2C3E6B).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${documents.length}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: Color(0xFF2C3E6B),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),

        // Document cards
        ...documents.map((doc) => _DocumentCard(document: doc)),

        const SizedBox(height: 20),
      ],
    );
  }
}

// ── Document card ─────────────────────────────────────────────────────────────

class _DocumentCard extends ConsumerWidget {
  final SchoolDocument document;
  const _DocumentCard({required this.document});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dlState = ref.watch(
      docDownloadProvider.select(
        (m) => m[document.id] ?? const DocDownloadState(),
      ),
    );

    // Show snackbar on error.
    ref.listen(
      docDownloadProvider.select(
        (m) => m[document.id] ?? const DocDownloadState(),
      ),
      (_, next) {
        if (next.status == DocDownloadStatus.error && next.errorMsg != null) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(next.errorMsg!),
              backgroundColor: Colors.red.shade700,
            ),
          );
        }
      },
    );

    final ext = _extension(document.originalFilename);

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            // File-type icon
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: _extColor(ext).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Center(
                child: Text(
                  _extEmoji(ext),
                  style: const TextStyle(fontSize: 20),
                ),
              ),
            ),
            const SizedBox(width: 12),

            // Title + meta
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    document.title,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      Text(
                        ext.toUpperCase(),
                        style: TextStyle(
                          fontSize: 11,
                          color: _extColor(ext),
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      if (document.academicYear != null) ...[
                        Text(
                          '  ·  ',
                          style: TextStyle(color: Colors.grey.shade400),
                        ),
                        Text(
                          document.academicYear!,
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade500,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),

            // Action button
            _ActionButton(
              dlState: dlState,
              onDownload: () => ref
                  .read(docDownloadProvider.notifier)
                  .download(document.id, document.originalFilename),
              onOpen: () => OpenFilex.open(dlState.filePath!),
              onRetry: () {
                ref.read(docDownloadProvider.notifier).reset(document.id);
                ref
                    .read(docDownloadProvider.notifier)
                    .download(document.id, document.originalFilename);
              },
            ),
          ],
        ),
      ),
    );
  }

  String _extension(String filename) {
    final parts = filename.split('.');
    return parts.length > 1 ? parts.last.toLowerCase() : 'file';
  }

  String _extEmoji(String ext) {
    switch (ext) {
      case 'pdf':
        return '📄';
      case 'doc':
      case 'docx':
        return '📝';
      case 'xls':
      case 'xlsx':
        return '📊';
      default:
        return '📎';
    }
  }

  Color _extColor(String ext) {
    switch (ext) {
      case 'pdf':
        return Colors.red.shade600;
      case 'doc':
      case 'docx':
        return Colors.blue.shade600;
      case 'xls':
      case 'xlsx':
        return Colors.green.shade600;
      default:
        return Colors.grey.shade600;
    }
  }
}

// ── Action button (idle / downloading / done / error) ─────────────────────────

class _ActionButton extends StatelessWidget {
  final DocDownloadState dlState;
  final VoidCallback onDownload;
  final VoidCallback onOpen;
  final VoidCallback onRetry;

  const _ActionButton({
    required this.dlState,
    required this.onDownload,
    required this.onOpen,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    switch (dlState.status) {
      case DocDownloadStatus.downloading:
        return const SizedBox(
          width: 36,
          height: 36,
          child: Padding(
            padding: EdgeInsets.all(6),
            child: CircularProgressIndicator(strokeWidth: 2.5),
          ),
        );

      case DocDownloadStatus.done:
        return Tooltip(
          message: 'Open',
          child: IconButton(
            onPressed: onOpen,
            icon: const Icon(
              Icons.open_in_new_rounded,
              color: Color(0xFF2C3E6B),
            ),
          ),
        );

      case DocDownloadStatus.error:
        return Tooltip(
          message: 'Retry download',
          child: IconButton(
            onPressed: onRetry,
            icon: Icon(Icons.refresh_rounded, color: Colors.red.shade600),
          ),
        );

      case DocDownloadStatus.idle:
        return Tooltip(
          message: 'Download',
          child: IconButton(
            onPressed: onDownload,
            icon: const Icon(Icons.download_rounded, color: Color(0xFF2C3E6B)),
          ),
        );
    }
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Shared helpers
// ═══════════════════════════════════════════════════════════════════════════════

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
            const Icon(Icons.error_outline, size: 48, color: Colors.red),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: onRetry, child: const Text('Retry')),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _EmptyState({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 56, color: Colors.grey.shade300),
            const SizedBox(height: 16),
            Text(
              title,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
            ),
          ],
        ),
      ),
    );
  }
}
