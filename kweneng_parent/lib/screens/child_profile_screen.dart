import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class ChildProfileScreen extends ConsumerStatefulWidget {
  final int studentId;
  final ChildModel? initialChild;

  const ChildProfileScreen({
    super.key,
    required this.studentId,
    this.initialChild,
  });

  @override
  ConsumerState<ChildProfileScreen> createState() => _ChildProfileScreenState();
}

class _ChildProfileScreenState extends ConsumerState<ChildProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nationalityController = TextEditingController();
  final _documentNumberController = TextEditingController();
  final _contactNameController = TextEditingController();
  final _relationshipController = TextEditingController();
  final _phoneController = TextEditingController();
  final _altPhoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _medicalNotesController = TextEditingController();

  String _documentType = 'birth_certificate';
  bool _hydrated = false;

  static const _documentTypes = {
    'birth_certificate': 'Birth Certificate Number',
    'national_id': 'ID Number',
    'passport': 'Passport Number',
  };

  @override
  void dispose() {
    _nationalityController.dispose();
    _documentNumberController.dispose();
    _contactNameController.dispose();
    _relationshipController.dispose();
    _phoneController.dispose();
    _altPhoneController.dispose();
    _addressController.dispose();
    _medicalNotesController.dispose();
    super.dispose();
  }

  void _hydrate(ChildModel child) {
    if (_hydrated) return;
    _nationalityController.text = child.identity.nationality ?? '';
    _documentType = _documentTypes.containsKey(child.identity.documentType)
        ? child.identity.documentType!
        : 'birth_certificate';
    _documentNumberController.text = child.identity.documentNumber ?? '';
    _contactNameController.text = child.emergencyContact.name ?? '';
    _relationshipController.text = child.emergencyContact.relationship ?? '';
    _phoneController.text = child.emergencyContact.phone ?? '';
    _altPhoneController.text = child.emergencyContact.altPhone ?? '';
    _addressController.text = child.emergencyContact.address ?? '';
    _medicalNotesController.text = child.emergencyContact.medicalNotes ?? '';
    _hydrated = true;
  }

  @override
  Widget build(BuildContext context) {
    final dashboard = ref.watch(dashboardProvider);
    final updateState = ref.watch(childProfileUpdateProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Student Profile')),
      body: dashboard.when(
        loading: () {
          final child = widget.initialChild;
          if (child != null) return _buildForm(context, child, updateState);
          return const Center(child: CircularProgressIndicator());
        },
        error: (_, _) {
          final child = widget.initialChild;
          if (child != null) return _buildForm(context, child, updateState);
          return _ErrorState(onRetry: () => ref.invalidate(dashboardProvider));
        },
        data: (data) {
          ChildModel? child;
          for (final candidate in data.children) {
            if (candidate.id == widget.studentId) {
              child = candidate;
              break;
            }
          }
          child ??= widget.initialChild;
          if (child == null) {
            return const Center(
              child: Text('Student not found on this parent account.'),
            );
          }
          return _buildForm(context, child, updateState);
        },
      ),
    );
  }

  Widget _buildForm(
    BuildContext context,
    ChildModel child,
    ChildProfileUpdateState updateState,
  ) {
    _hydrate(child);

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
        children: [
          _ChildHeader(child: child),
          if (!child.profile.complete) ...[
            const SizedBox(height: 12),
            _MissingFieldsCard(fields: child.profile.missingFields),
          ],
          const SizedBox(height: 16),
          _SectionCard(
            title: 'Identity details',
            icon: Icons.badge_outlined,
            children: [
              TextFormField(
                controller: _nationalityController,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Nationality',
                  hintText: 'e.g. Botswana',
                  border: OutlineInputBorder(),
                ),
                validator: _required,
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: _documentType,
                decoration: const InputDecoration(
                  labelText: 'Document type',
                  border: OutlineInputBorder(),
                ),
                items: _documentTypes.entries
                    .map(
                      (entry) => DropdownMenuItem(
                        value: entry.key,
                        child: Text(entry.value),
                      ),
                    )
                    .toList(),
                onChanged: (value) => setState(
                  () => _documentType = value ?? 'birth_certificate',
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _documentNumberController,
                textInputAction: TextInputAction.next,
                textCapitalization: TextCapitalization.characters,
                decoration: InputDecoration(
                  labelText: _documentTypes[_documentType],
                  border: const OutlineInputBorder(),
                ),
                validator: _required,
              ),
            ],
          ),
          const SizedBox(height: 16),
          _SectionCard(
            title: 'Emergency contact',
            icon: Icons.health_and_safety_outlined,
            children: [
              TextFormField(
                controller: _contactNameController,
                textInputAction: TextInputAction.next,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Contact name',
                  border: OutlineInputBorder(),
                ),
                validator: _required,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _relationshipController,
                textInputAction: TextInputAction.next,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Relationship',
                  hintText: 'e.g. Mother, Uncle, Guardian',
                  border: OutlineInputBorder(),
                ),
                validator: _required,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Primary phone',
                  border: OutlineInputBorder(),
                ),
                validator: _required,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _altPhoneController,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Alternative phone (optional)',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _addressController,
                minLines: 2,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Emergency address (optional)',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _medicalNotesController,
                minLines: 2,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'Medical notes / allergies (optional)',
                  hintText:
                      'Add allergies, medication, or important health notes.',
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ),
          if ((updateState.error ?? '').isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(updateState.error!, style: const TextStyle(color: Colors.red)),
          ],
          const SizedBox(height: 20),
          FilledButton.icon(
            onPressed: updateState.isLoading ? null : () => _submit(child),
            icon: updateState.isLoading
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.save_outlined),
            label: Text(updateState.isLoading ? 'Saving...' : 'Save profile'),
          ),
        ],
      ),
    );
  }

  String? _required(String? value) {
    if (value == null || value.trim().isEmpty) return 'Required';
    return null;
  }

  Future<void> _submit(ChildModel child) async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    final ok = await ref
        .read(childProfileUpdateProvider.notifier)
        .update(
          studentId: child.id,
          nationality: _nationalityController.text,
          identityDocumentType: _documentType,
          identityDocumentNumber: _documentNumberController.text,
          emergencyContactName: _contactNameController.text,
          emergencyContactRelationship: _relationshipController.text,
          emergencyContactPhone: _phoneController.text,
          emergencyContactAltPhone: _altPhoneController.text,
          emergencyContactAddress: _addressController.text,
          medicalNotes: _medicalNotesController.text,
        );

    if (!mounted) return;

    if (ok) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Student profile updated.')));
      context.pop();
    }
  }
}

class _ChildHeader extends StatelessWidget {
  final ChildModel child;
  const _ChildHeader({required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 26,
            child: Text(
              child.name.isEmpty
                  ? 'S'
                  : child.name.substring(0, 1).toUpperCase(),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  child.name,
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if ((child.className ?? '').isNotEmpty)
                  Text(
                    child.className!,
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
              ],
            ),
          ),
          Icon(
            child.profile.complete
                ? Icons.verified_outlined
                : Icons.error_outline,
            color: child.profile.complete ? Colors.green : Colors.orange,
          ),
        ],
      ),
    );
  }
}

class _MissingFieldsCard extends StatelessWidget {
  final List<String> fields;
  const _MissingFieldsCard({required this.fields});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.info_outline, color: Color(0xFFD97706)),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              fields.isEmpty
                  ? 'Please complete the missing identity and emergency contact information.'
                  : 'Missing: ${fields.join(', ')}',
              style: const TextStyle(fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final List<Widget> children;

  const _SectionCard({
    required this.title,
    required this.icon,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            ...children,
          ],
        ),
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  final VoidCallback onRetry;
  const _ErrorState({required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('Could not load profile.'),
          const SizedBox(height: 10),
          FilledButton(onPressed: onRetry, child: const Text('Retry')),
        ],
      ),
    );
  }
}
