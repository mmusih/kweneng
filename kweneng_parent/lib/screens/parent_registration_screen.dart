import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class ParentRegistrationScreen extends ConsumerStatefulWidget {
  final ParentCodeVerification? verification;
  final String? inviteCode;

  const ParentRegistrationScreen({
    super.key,
    required this.verification,
    this.inviteCode,
  });

  @override
  ConsumerState<ParentRegistrationScreen> createState() =>
      _ParentRegistrationScreenState();
}

class _ParentRegistrationScreenState
    extends ConsumerState<ParentRegistrationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  final _existingPasswordCtrl = TextEditingController();

  String _relationship = 'father';
  bool _useExistingAccount = false;
  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _obscureExisting = true;
  bool _loading = false;
  bool _verifyingCode = false;
  String? _error;
  ParentCodeVerification? _verification;

  @override
  void initState() {
    super.initState();
    _verification = widget.verification;

    final code = widget.inviteCode?.trim();
    if (_verification == null && code != null && code.isNotEmpty) {
      _recoverVerification(code);
    }
  }

  Future<void> _recoverVerification(String inviteCode) async {
    setState(() {
      _verifyingCode = true;
      _error = null;
    });

    try {
      final api = ref.read(apiServiceProvider);
      final data = await api.verifyParentCode(inviteCode);
      if (!mounted) return;
      setState(() => _verification = ParentCodeVerification.fromJson(data));
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _friendlyError(e));
    } finally {
      if (mounted) setState(() => _verifyingCode = false);
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmCtrl.dispose();
    _existingPasswordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    final verification = _verification;
    if (verification == null) {
      context.go('/parent-register');
      return;
    }

    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.completeParentRegistration(
        inviteCode: verification.inviteCode,
        email: _emailCtrl.text.trim(),
        phone: _phoneCtrl.text.trim(),
        relationship: _relationship,
        useExistingAccount: _useExistingAccount,
        name: _nameCtrl.text.trim(),
        password: _passwordCtrl.text.trim(),
        passwordConfirmation: _confirmCtrl.text.trim(),
        existingPassword: _existingPasswordCtrl.text.trim(),
      );

      await ref.read(authProvider.notifier).applyAuthResponse(res);

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Parent account activated successfully.')),
      );

      final auth = ref.read(authProvider);
      context.go(auth.mustChangePassword ? '/change-password' : '/dashboard');
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = _friendlyError(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _friendlyError(Object e) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map) {
        final message = data['message']?.toString();
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
          return first.toString();
        }
        if (message != null && message.isNotEmpty) return message;
      }
    }
    return 'Registration could not be completed. Please try again.';
  }

  @override
  Widget build(BuildContext context) {
    final verification = _verification;

    if (verification == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Parent Registration')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_verifyingCode) ...[
                  const CircularProgressIndicator(),
                  const SizedBox(height: 16),
                  const Text(
                    'Checking your parent code...',
                    textAlign: TextAlign.center,
                  ),
                ] else ...[
                  const Icon(Icons.info_outline, size: 44, color: Colors.grey),
                  const SizedBox(height: 12),
                  Text(
                    _error ??
                        'Please verify your parent code before continuing.',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => context.go('/parent-register'),
                    child: const Text('Enter parent code'),
                  ),
                ],
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: AppTheme.primary,
      appBar: AppBar(
        title: const Text('Complete Registration'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 20, 24, 18),
                child: _StudentCard(verification: verification),
              ),
              Container(
                width: double.infinity,
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
                ),
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text(
                        'Parent Details',
                        style: TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primary,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Complete your parent account and link it to this student.',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(height: 20),
                      if (_error != null) ...[
                        _ErrorBox(message: _error!),
                        const SizedBox(height: 16),
                      ],
                      SwitchListTile.adaptive(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('I already have a parent account'),
                        subtitle: const Text(
                          'Use your existing account password to link this child.',
                        ),
                        value: _useExistingAccount,
                        onChanged: _loading
                            ? null
                            : (v) => setState(() {
                                _useExistingAccount = v;
                                _error = null;
                              }),
                      ),
                      const SizedBox(height: 8),
                      if (!_useExistingAccount) ...[
                        TextFormField(
                          controller: _nameCtrl,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'Full name',
                            prefixIcon: Icon(Icons.person_outline),
                          ),
                          validator: (v) {
                            if (_useExistingAccount) return null;
                            if (v == null || v.trim().isEmpty) {
                              return 'Enter your full name';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 14),
                      ],
                      TextFormField(
                        controller: _emailCtrl,
                        keyboardType: TextInputType.emailAddress,
                        textInputAction: TextInputAction.next,
                        decoration: const InputDecoration(
                          labelText: 'Email address',
                          prefixIcon: Icon(Icons.email_outlined),
                        ),
                        validator: (v) {
                          if (v == null || v.trim().isEmpty) {
                            return 'Enter your email address';
                          }
                          if (!v.contains('@')) return 'Enter a valid email';
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _phoneCtrl,
                        keyboardType: TextInputType.phone,
                        textInputAction: TextInputAction.next,
                        decoration: const InputDecoration(
                          labelText: 'Phone number',
                          prefixIcon: Icon(Icons.phone_outlined),
                        ),
                        validator: (v) {
                          if (v == null || v.trim().isEmpty) {
                            return 'Enter your phone number';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      DropdownButtonFormField<String>(
                        initialValue: _relationship,
                        decoration: const InputDecoration(
                          labelText: 'Relationship to student',
                          prefixIcon: Icon(Icons.family_restroom_outlined),
                        ),
                        items: const [
                          DropdownMenuItem(
                            value: 'father',
                            child: Text('Father'),
                          ),
                          DropdownMenuItem(
                            value: 'mother',
                            child: Text('Mother'),
                          ),
                          DropdownMenuItem(
                            value: 'guardian',
                            child: Text('Guardian'),
                          ),
                          DropdownMenuItem(
                            value: 'other',
                            child: Text('Other'),
                          ),
                        ],
                        onChanged: _loading
                            ? null
                            : (v) =>
                                  setState(() => _relationship = v ?? 'father'),
                      ),
                      const SizedBox(height: 14),
                      if (_useExistingAccount) ...[
                        TextFormField(
                          controller: _existingPasswordCtrl,
                          obscureText: _obscureExisting,
                          textInputAction: TextInputAction.done,
                          onFieldSubmitted: (_) => _submit(),
                          decoration: InputDecoration(
                            labelText: 'Existing account password',
                            prefixIcon: const Icon(Icons.lock_outline),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscureExisting
                                    ? Icons.visibility_outlined
                                    : Icons.visibility_off_outlined,
                              ),
                              onPressed: () => setState(
                                () => _obscureExisting = !_obscureExisting,
                              ),
                            ),
                          ),
                          validator: (v) {
                            if (!_useExistingAccount) return null;
                            if (v == null || v.trim().isEmpty) {
                              return 'Enter your existing account password';
                            }
                            return null;
                          },
                        ),
                      ] else ...[
                        TextFormField(
                          controller: _passwordCtrl,
                          obscureText: _obscurePassword,
                          textInputAction: TextInputAction.next,
                          decoration: InputDecoration(
                            labelText: 'Create password',
                            prefixIcon: const Icon(Icons.lock_outline),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_outlined
                                    : Icons.visibility_off_outlined,
                              ),
                              onPressed: () => setState(
                                () => _obscurePassword = !_obscurePassword,
                              ),
                            ),
                          ),
                          validator: (v) {
                            if (_useExistingAccount) return null;
                            final value = v?.trim() ?? '';
                            if (value.isEmpty) return 'Create a password';
                            if (value.length < 8) {
                              return 'Password must be at least 8 characters';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 14),
                        TextFormField(
                          controller: _confirmCtrl,
                          obscureText: _obscureConfirm,
                          textInputAction: TextInputAction.done,
                          onFieldSubmitted: (_) => _submit(),
                          decoration: InputDecoration(
                            labelText: 'Confirm password',
                            prefixIcon: const Icon(
                              Icons.verified_user_outlined,
                            ),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscureConfirm
                                    ? Icons.visibility_outlined
                                    : Icons.visibility_off_outlined,
                              ),
                              onPressed: () => setState(
                                () => _obscureConfirm = !_obscureConfirm,
                              ),
                            ),
                          ),
                          validator: (v) {
                            if (_useExistingAccount) return null;
                            if (v == null || v.trim().isEmpty) {
                              return 'Confirm your password';
                            }
                            if (v.trim() != _passwordCtrl.text.trim()) {
                              return 'Passwords do not match';
                            }
                            return null;
                          },
                        ),
                      ],
                      const SizedBox(height: 24),
                      ElevatedButton(
                        onPressed: _loading ? null : _submit,
                        child: _loading
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Text('Complete Registration'),
                      ),
                      const SizedBox(height: 12),
                      TextButton(
                        onPressed: _loading ? null : () => context.go('/login'),
                        child: const Text('Back to sign in'),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StudentCard extends StatelessWidget {
  final ParentCodeVerification verification;
  const _StudentCard({required this.verification});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
      ),
      child: Row(
        children: [
          const CircleAvatar(
            backgroundColor: Colors.white,
            foregroundColor: AppTheme.primary,
            child: Icon(Icons.school_outlined),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  verification.student.name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  verification.student.className ?? 'Class not assigned',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.8),
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorBox extends StatelessWidget {
  final String message;
  const _ErrorBox({required this.message});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF2F2),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFFECACA)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline, color: AppTheme.danger, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(color: AppTheme.danger, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}
