import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/theme.dart';
import '../models/flutter_models.dart';

class SelectedChild extends Notifier<int?> {
  @override
  int? build() => null;
  void select(int id) => state = id;
}

final selectedChildProvider = NotifierProvider<SelectedChild, int?>(
  SelectedChild.new,
);

ChildModel? selectedChild(List<ChildModel> children, int? id) {
  if (children.isEmpty) return null;
  return children.where((child) => child.id == id).firstOrNull ??
      children.first;
}

class ChildAvatar extends StatelessWidget {
  final String name;
  final String? photo;
  final double radius;
  const ChildAvatar({
    super.key,
    required this.name,
    this.photo,
    this.radius = 28,
  });
  @override
  Widget build(BuildContext context) => CircleAvatar(
    radius: radius,
    backgroundColor: AppTheme.accent,
    foregroundColor: AppTheme.primary,
    foregroundImage: photo?.isNotEmpty == true ? NetworkImage(photo!) : null,
    onForegroundImageError: photo?.isNotEmpty == true ? (_, _) {} : null,
    child: Text(
      name.isEmpty ? 'S' : name.characters.first.toUpperCase(),
      style: TextStyle(fontWeight: FontWeight.w800, fontSize: radius * .7),
    ),
  );
}

class ChildTabInfo {
  final int id;
  final String name;
  final String? className;
  final String? photo;
  const ChildTabInfo({
    required this.id,
    required this.name,
    this.className,
    this.photo,
  });
}

/// Visible child tabs, with photos and class labels, shared across parent screens.
class ChildTabs extends StatelessWidget {
  final List<ChildTabInfo> children;
  final int? selectedId;
  final ValueChanged<int> onSelected;
  const ChildTabs({
    super.key,
    required this.children,
    required this.selectedId,
    required this.onSelected,
  });
  @override
  Widget build(BuildContext context) {
    if (children.length < 2) return const SizedBox.shrink();
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = (constraints.maxWidth - 12) / 2;
        return SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              for (var index = 0; index < children.length; index++) ...[
                if (index > 0) const SizedBox(width: 12),
                SizedBox(
                  width: width,
                  child: _ChildTab(
                    child: children[index],
                    selected: children[index].id == selectedId,
                    onTap: () => onSelected(children[index].id),
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}

class _ChildTab extends StatelessWidget {
  final ChildTabInfo child;
  final bool selected;
  final VoidCallback onTap;
  const _ChildTab({
    required this.child,
    required this.selected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) => Semantics(
    button: true,
    selected: selected,
    label: '${child.name}, ${child.className ?? "Class not assigned"}',
    child: Tooltip(
      message: child.name,
      child: Material(
        color: selected ? const Color(0xFFE2EDFC) : Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: selected ? AppTheme.primary : const Color(0xFFE2E8F0),
            width: selected ? 2 : 1,
          ),
        ),
        child: InkWell(
          key: ValueKey('child-tab-${child.id}'),
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
            child: Row(
              children: [
                ChildAvatar(name: child.name, photo: child.photo, radius: 19),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        child.name.trim().split(RegExp(r'\s+')).first,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: selected
                              ? FontWeight.w800
                              : FontWeight.w600,
                          color: AppTheme.primary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        child.className ?? 'Unassigned',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppTheme.primary,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}

class ChildSelector extends ConsumerWidget {
  final List<ChildModel> children;
  final double bottomPadding;
  const ChildSelector({
    super.key,
    required this.children,
    this.bottomPadding = 18,
  });
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (children.length < 2) return const SizedBox.shrink();
    final child = selectedChild(children, ref.watch(selectedChildProvider));
    return Padding(
      padding: EdgeInsets.only(bottom: bottomPadding),
      child: ChildTabs(
        children: children
            .map(
              (item) => ChildTabInfo(
                id: item.id,
                name: item.name,
                className: item.className,
                photo: item.photo,
              ),
            )
            .toList(),
        selectedId: child?.id,
        onSelected: ref.read(selectedChildProvider.notifier).select,
      ),
    );
  }
}

class PortalCard extends StatelessWidget {
  final Widget child;
  final EdgeInsets padding;
  const PortalCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(20),
  });
  @override
  Widget build(BuildContext context) => Card(
    margin: EdgeInsets.zero,
    child: Padding(padding: padding, child: child),
  );
}

class PortalMetric extends StatelessWidget {
  final String label;
  final String value;
  final bool subdued;
  const PortalMetric({
    super.key,
    required this.label,
    required this.value,
    this.subdued = false,
  });
  @override
  Widget build(BuildContext context) => SizedBox(
    width: 132,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: subdued ? 14 : 23,
            fontWeight: subdued ? FontWeight.w500 : FontWeight.w800,
            color: subdued ? const Color(0xFF64748B) : AppTheme.primary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
        ),
      ],
    ),
  );
}

class PortalLoading extends StatelessWidget {
  const PortalLoading({super.key});
  @override
  Widget build(BuildContext context) => Semantics(
    label: 'Loading school information',
    child: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        for (final height in [64.0, 190.0, 96.0, 120.0])
          Container(
            height: height,
            margin: const EdgeInsets.only(bottom: 18),
            decoration: BoxDecoration(
              color: const Color(0xFFE7ECF3),
              borderRadius: BorderRadius.circular(22),
            ),
          ),
      ],
    ),
  );
}

class PortalError extends StatelessWidget {
  final String title;
  final VoidCallback onRetry;
  const PortalError({super.key, required this.title, required this.onRetry});
  @override
  Widget build(BuildContext context) => Center(
    child: SingleChildScrollView(
      padding: const EdgeInsets.all(28),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(
            Icons.cloud_off_outlined,
            size: 48,
            color: AppTheme.primary,
          ),
          const SizedBox(height: 16),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 8),
          const Text(
            'Check your connection and try again. Your information has not been changed.',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          FilledButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ],
      ),
    ),
  );
}

class PortalLink extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback onTap;
  final bool alert;
  const PortalLink({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    required this.onTap,
    this.alert = false,
  });
  @override
  Widget build(BuildContext context) => ListTile(
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
    leading: Icon(icon, color: alert ? AppTheme.danger : AppTheme.primary),
    title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
    subtitle: subtitle == null ? null : Text(subtitle!),
    trailing: const Icon(Icons.chevron_right, size: 20),
    onTap: onTap,
  );
}
