import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/app_providers.dart';

class TeacherChangePasswordScreen extends ConsumerStatefulWidget {
  final bool isRequired;

  const TeacherChangePasswordScreen({super.key, this.isRequired = false});

  @override
  ConsumerState<TeacherChangePasswordScreen> createState() =>
      _TeacherChangePasswordScreenState();
}

class _TeacherChangePasswordScreenState
    extends ConsumerState<TeacherChangePasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _currentPassword = TextEditingController();
  final _newPassword = TextEditingController();
  final _confirmation = TextEditingController();
  bool _showCurrent = false;
  bool _showNew = false;
  bool _showConfirmation = false;

  @override
  void dispose() {
    _currentPassword.dispose();
    _newPassword.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);

    return PopScope(
      canPop: !widget.isRequired,
      child: Scaffold(
        appBar: AppBar(
          automaticallyImplyLeading: !widget.isRequired,
          title: Text(
            widget.isRequired ? 'Secure your account' : 'Change password',
          ),
          actions: widget.isRequired
              ? [
                  IconButton(
                    tooltip: 'Use another account',
                    onPressed: auth.isLoading
                        ? null
                        : () => ref.read(authProvider.notifier).logout(),
                    icon: const Icon(Icons.logout),
                  ),
                ]
              : null,
        ),
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Icon(
                    Icons.shield_outlined,
                    size: 64,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    widget.isRequired
                        ? 'Your temporary password must be replaced before you continue.'
                        : 'Choose a private password that you do not use elsewhere.',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  if ((auth.error ?? '').isNotEmpty) ...[
                    const SizedBox(height: 18),
                    MaterialBanner(
                      content: Text(auth.error!),
                      actions: const [SizedBox.shrink()],
                    ),
                  ],
                  const SizedBox(height: 24),
                  _PasswordField(
                    controller: _currentPassword,
                    label: 'Current password',
                    visible: _showCurrent,
                    onToggle: () =>
                        setState(() => _showCurrent = !_showCurrent),
                    validator: (value) => value == null || value.isEmpty
                        ? 'Enter your current password.'
                        : null,
                  ),
                  const SizedBox(height: 14),
                  _PasswordField(
                    controller: _newPassword,
                    label: 'New password',
                    visible: _showNew,
                    onToggle: () => setState(() => _showNew = !_showNew),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Enter a new password.';
                      }
                      if (value.length < 8) {
                        return 'Use at least 8 characters.';
                      }
                      if (value == _currentPassword.text) {
                        return 'The new password must be different.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 14),
                  _PasswordField(
                    controller: _confirmation,
                    label: 'Confirm new password',
                    visible: _showConfirmation,
                    onToggle: () => setState(
                      () => _showConfirmation = !_showConfirmation,
                    ),
                    onSubmitted: (_) => _submit(),
                    validator: (value) => value != _newPassword.text
                        ? 'The passwords do not match.'
                        : null,
                  ),
                  const SizedBox(height: 24),
                  FilledButton.icon(
                    onPressed: auth.isLoading ? null : _submit,
                    icon: auth.isLoading
                        ? const SizedBox.square(
                            dimension: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.check_circle_outline),
                    label: Text(
                      auth.isLoading ? 'Saving...' : 'Save new password',
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

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    final success = await ref
        .read(authProvider.notifier)
        .changePassword(
          currentPassword: _currentPassword.text,
          password: _newPassword.text,
          passwordConfirmation: _confirmation.text,
        );
    if (!mounted || !success) return;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Password changed successfully.')),
    );
    if (!widget.isRequired) Navigator.of(context).pop();
  }
}

class _PasswordField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final bool visible;
  final VoidCallback onToggle;
  final FormFieldValidator<String>? validator;
  final ValueChanged<String>? onSubmitted;

  const _PasswordField({
    required this.controller,
    required this.label,
    required this.visible,
    required this.onToggle,
    this.validator,
    this.onSubmitted,
  });

  @override
  Widget build(BuildContext context) => TextFormField(
    controller: controller,
    obscureText: !visible,
    autocorrect: false,
    enableSuggestions: false,
    textInputAction: onSubmitted == null
        ? TextInputAction.next
        : TextInputAction.done,
    onFieldSubmitted: onSubmitted,
    validator: validator,
    decoration: InputDecoration(
      labelText: label,
      prefixIcon: const Icon(Icons.lock_outline),
      suffixIcon: IconButton(
        tooltip: visible ? 'Hide password' : 'Show password',
        onPressed: onToggle,
        icon: Icon(
          visible ? Icons.visibility_off_outlined : Icons.visibility_outlined,
        ),
      ),
    ),
  );
}
