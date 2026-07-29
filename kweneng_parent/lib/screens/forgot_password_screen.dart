import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../providers/flutter_providers.dart';

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() =>
      _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();

  bool _isLoading = false;
  String? _message;
  String? _error;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _message = null;
      _error = null;
    });

    try {
      final message = await ref
          .read(apiServiceProvider)
          .requestPasswordReset(_emailController.text);

      if (!mounted) return;
      setState(() => _message = message);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error =
            'We could not request a reset link. Check your connection and try again.';
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reset password')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 18),
                const CircleAvatar(
                  radius: 34,
                  backgroundColor: Color(0xFFEFF6FF),
                  child: Icon(
                    Icons.mark_email_read_outlined,
                    size: 34,
                    color: AppTheme.primary,
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  'Forgot your password?',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.primary,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Enter the email used for your parent account. We will send a secure reset link if the account exists.',
                  textAlign: TextAlign.center,
                  style: TextStyle(height: 1.5, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 28),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.done,
                  autofillHints: const [AutofillHints.email],
                  onFieldSubmitted: (_) => _isLoading ? null : _submit(),
                  decoration: const InputDecoration(
                    labelText: 'Email address',
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                  validator: (value) {
                    final email = value?.trim() ?? '';
                    if (email.isEmpty || !email.contains('@')) {
                      return 'Enter a valid email address.';
                    }
                    return null;
                  },
                ),
                if (_message != null) ...[
                  const SizedBox(height: 18),
                  _FeedbackCard(
                    icon: Icons.check_circle_outline,
                    message: _message!,
                    color: AppTheme.success,
                    background: const Color(0xFFECFDF5),
                  ),
                ],
                if (_error != null) ...[
                  const SizedBox(height: 18),
                  _FeedbackCard(
                    icon: Icons.error_outline,
                    message: _error!,
                    color: AppTheme.danger,
                    background: const Color(0xFFFEF2F2),
                  ),
                ],
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: _isLoading ? null : _submit,
                  icon: _isLoading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_outlined),
                  label: Text(_isLoading ? 'Sending...' : 'Email reset link'),
                ),
                const SizedBox(height: 12),
                TextButton(
                  onPressed: _isLoading ? null : () => Navigator.pop(context),
                  child: const Text('Back to sign in'),
                ),
                const SizedBox(height: 12),
                Text(
                  'The link expires after 60 minutes and opens the secure Kweneng website.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _FeedbackCard extends StatelessWidget {
  const _FeedbackCard({
    required this.icon,
    required this.message,
    required this.color,
    required this.background,
  });

  final IconData icon;
  final String message;
  final Color color;
  final Color background;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(message, style: TextStyle(color: color, height: 1.4)),
          ),
        ],
      ),
    );
  }
}
