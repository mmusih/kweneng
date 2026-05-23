import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/flutter_providers.dart';
import '../core/theme.dart';

class LibraryScreen extends ConsumerWidget {
  const LibraryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final libraryAsync = ref.watch(libraryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Library')),
      body: libraryAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: ElevatedButton(
            onPressed: () => ref.invalidate(libraryProvider),
            child: const Text('Retry'),
          ),
        ),
        data: (data) {
          final children = data['children'] as List? ?? [];
          final totalBorrowed = data['total_borrowed'] ?? 0;
          final totalOverdue = data['total_overdue'] ?? 0;

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(libraryProvider),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Summary banner
                Container(
                  padding: const EdgeInsets.all(14),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: totalOverdue > 0
                        ? const Color(0xFFFEF2F2)
                        : const Color(0xFFE8F1FA),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: totalOverdue > 0
                          ? const Color(0xFFFECACA)
                          : const Color(0xFFBFDBFE),
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.menu_book,
                        color: totalOverdue > 0
                            ? AppTheme.danger
                            : AppTheme.primary,
                        size: 28,
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '$totalBorrowed book${totalBorrowed == 1 ? '' : 's'} borrowed',
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              fontSize: 15,
                              color: totalOverdue > 0
                                  ? AppTheme.danger
                                  : AppTheme.primary,
                            ),
                          ),
                          if (totalOverdue > 0)
                            Text(
                              '$totalOverdue overdue — please return immediately',
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppTheme.danger,
                              ),
                            )
                          else
                            const Text(
                              'All books on time',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey,
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),

                // Per-child sections
                ...children.map((child) {
                  final books = child['books'] as List? ?? [];
                  final borrowed = child['borrowed'] ?? 0;
                  final overdue = child['overdue'] ?? 0;

                  return Card(
                    margin: const EdgeInsets.only(bottom: 14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Child header
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: const BoxDecoration(
                            color: Color(0xFFE8F1FA),
                            borderRadius: BorderRadius.vertical(
                              top: Radius.circular(12),
                            ),
                          ),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 18,
                                backgroundColor: AppTheme.primary,
                                child: Text(
                                  (child['student_name'] as String).substring(
                                    0,
                                    1,
                                  ),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      child['student_name'] ?? '',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 14,
                                      ),
                                    ),
                                    Text(
                                      child['class'] ?? '',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey.shade600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Text(
                                '$borrowed borrowed',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: overdue > 0
                                      ? AppTheme.danger
                                      : Colors.grey,
                                  fontWeight: overdue > 0
                                      ? FontWeight.w600
                                      : FontWeight.normal,
                                ),
                              ),
                              if (overdue > 0) ...[
                                const Text(
                                  ' · ',
                                  style: TextStyle(color: Colors.grey),
                                ),
                                Text(
                                  '$overdue overdue',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: AppTheme.danger,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),

                        // Books list
                        if (books.isEmpty)
                          const Padding(
                            padding: EdgeInsets.all(16),
                            child: Text(
                              'No books currently borrowed.',
                              style: TextStyle(
                                color: Colors.grey,
                                fontSize: 13,
                              ),
                            ),
                          )
                        else
                          ...books.map((book) {
                            final isOverdue = book['overdue'] == true;
                            return ListTile(
                              dense: true,
                              leading: Icon(
                                Icons.menu_book,
                                color: isOverdue
                                    ? AppTheme.danger
                                    : Colors.blue.shade200,
                                size: 20,
                              ),
                              title: Text(
                                book['title'] ?? 'Unknown',
                                style: const TextStyle(fontSize: 13),
                              ),
                              subtitle: book['author'] != null
                                  ? Text(
                                      book['author'],
                                      style: const TextStyle(fontSize: 11),
                                    )
                                  : null,
                              trailing: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    isOverdue ? 'OVERDUE' : 'Due',
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w700,
                                      color: isOverdue
                                          ? AppTheme.danger
                                          : Colors.grey,
                                    ),
                                  ),
                                  if (book['due_at'] != null)
                                    Text(
                                      book['due_at'],
                                      style: TextStyle(
                                        fontSize: 11,
                                        color: isOverdue
                                            ? AppTheme.danger
                                            : Colors.grey,
                                      ),
                                    ),
                                ],
                              ),
                            );
                          }),
                      ],
                    ),
                  );
                }),
              ],
            ),
          );
        },
      ),
    );
  }
}
