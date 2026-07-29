import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../models/flutter_models.dart';
import '../providers/flutter_providers.dart';

class ParentCodeScreen extends ConsumerStatefulWidget {
  const ParentCodeScreen({super.key});

  @override
  ConsumerState<ParentCodeScreen> createState() => _ParentCodeScreenState();
}

class _ParentCodeScreenState extends ConsumerState<ParentCodeScreen> {
  final _formKey = GlobalKey<FormState>();
  final _codeCtrl = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _codeCtrl.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiServiceProvider);
      final data = await api.verifyParentCode(_codeCtrl.text);
      final verification = ParentCodeVerification.fromJson(data);

      if (!mounted) return;
      context.push(
        '/parent-register/details?code=${Uri.encodeComponent(verification.inviteCode)}',
        extra: verification,
      );
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
        if (message != null && message.isNotEmpty) return message;
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
          return first.toString();
        }
      }
    }
    return 'Could not verify this code. Please try again.';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.primary,
      appBar: AppBar(
        title: const Text('Parent Code'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
                child: Column(
                  children: [
                    Image.asset(
                      'assets/images/logo.png',
                      width: 88,
                      height: 88,
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Activate Parent Account',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Enter the parent code from your child\'s login slip.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                width: double.infinity,
                constraints: BoxConstraints(
                  minHeight: MediaQuery.of(context).size.height * 0.68,
                ),
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
                        'Parent Registration',
                        style: TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primary,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'We will check the code and show the linked student before you continue.',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey.shade600,
                          height: 1.35,
                        ),
                      ),
                      const SizedBox(height: 24),
                      if (_error != null) ...[
                        _ErrorBox(message: _error!),
                        const SizedBox(height: 16),
                      ],
                      TextFormField(
                        controller: _codeCtrl,
                        textCapitalization: TextCapitalization.characters,
                        textInputAction: TextInputAction.done,
                        autocorrect: false,
                        enableSuggestions: false,
                        inputFormatters: const [ParentCodeInputFormatter()],
                        onFieldSubmitted: (_) => _verify(),
                        decoration: const InputDecoration(
                          labelText: 'Parent code',
                          hintText: '10-character code',
                          prefixIcon: Icon(Icons.confirmation_number_outlined),
                        ),
                        validator: (v) {
                          final value = ParentCodeInputFormatter.normalize(
                            v ?? '',
                          );
                          if (value.isEmpty) return 'Enter your parent code';
                          if (value.length != 10) {
                            return 'The parent code must be 10 characters';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 24),
                      ElevatedButton(
                        onPressed: _loading ? null : _verify,
                        child: _loading
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Text('Verify Code'),
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

class ParentCodeInputFormatter extends TextInputFormatter {
  const ParentCodeInputFormatter();

  static String normalize(String value) {
    final buffer = StringBuffer();
    for (final codeUnit in value.codeUnits) {
      final isDigit = codeUnit >= 48 && codeUnit <= 57;
      final isUpper = codeUnit >= 65 && codeUnit <= 90;
      final isLower = codeUnit >= 97 && codeUnit <= 122;

      if (isDigit || isUpper || isLower) {
        buffer.writeCharCode(isLower ? codeUnit - 32 : codeUnit);
      }

      if (buffer.length >= 10) break;
    }
    return buffer.toString();
  }

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    final normalized = normalize(newValue.text);
    return TextEditingValue(
      text: normalized,
      selection: TextSelection.collapsed(offset: normalized.length),
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
