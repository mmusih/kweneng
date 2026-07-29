import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../providers/flutter_providers.dart';

// ═══════════════════════════════════════════════════════════════════════════════
// Messages Screen  (inbox list)
// ═══════════════════════════════════════════════════════════════════════════════

class MessagesScreen extends ConsumerWidget {
  const MessagesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(messagesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Messages'),
        backgroundColor: const Color(0xFF2C3E6B),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: () => ref.invalidate(messagesProvider),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => const ComposeMessageScreen()),
        ),
        backgroundColor: const Color(0xFF2C3E6B),
        icon: const Icon(Icons.edit_outlined, color: Colors.white),
        label: const Text('New Message', style: TextStyle(color: Colors.white)),
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: 'Failed to load messages\n$e',
          onRetry: () => ref.invalidate(messagesProvider),
        ),
        data: (data) {
          if (data.threads.isEmpty) {
            return _EmptyState(
              icon: Icons.mail_outline_rounded,
              title: 'No messages yet',
              subtitle: 'Tap the button below to send a message to the school.',
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: data.threads.length,
            separatorBuilder: (_, _) => const Divider(height: 1, indent: 72),
            itemBuilder: (context, i) => _ThreadTile(thread: data.threads[i]),
          );
        },
      ),
    );
  }
}

// ── Thread list tile ──────────────────────────────────────────────────────────

class _ThreadTile extends ConsumerWidget {
  final MessageThread thread;
  const _ThreadTile({required this.thread});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final hasUnread = thread.hasUnread;
    final dateStr = thread.lastReplyAt != null
        ? _formatDate(thread.lastReplyAt!)
        : '';

    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      leading: CircleAvatar(
        radius: 22,
        backgroundColor: hasUnread
            ? const Color(0xFF2C3E6B)
            : Colors.grey.shade200,
        child: Icon(
          Icons.school_outlined,
          size: 20,
          color: hasUnread ? Colors.white : Colors.grey.shade500,
        ),
      ),
      title: Row(
        children: [
          Expanded(
            child: Text(
              thread.subject,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontWeight: hasUnread ? FontWeight.bold : FontWeight.normal,
                fontSize: 14,
              ),
            ),
          ),
          if (dateStr.isNotEmpty)
            Text(
              dateStr,
              style: TextStyle(
                fontSize: 11,
                color: hasUnread
                    ? const Color(0xFF2C3E6B)
                    : Colors.grey.shade500,
                fontWeight: hasUnread ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
        ],
      ),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 2),
          Text(
            thread.replies.isNotEmpty ? thread.replies.last.body : thread.body,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 13,
              color: hasUnread ? Colors.black87 : Colors.grey.shade600,
            ),
          ),
          if (thread.replies.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 3),
              child: Text(
                '${thread.replies.length} ${thread.replies.length == 1 ? "reply" : "replies"}',
                style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
              ),
            ),
        ],
      ),
      trailing: hasUnread
          ? Container(
              width: 10,
              height: 10,
              decoration: const BoxDecoration(
                color: Color(0xFF2C3E6B),
                shape: BoxShape.circle,
              ),
            )
          : null,
      onTap: () async {
        await Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => MessageThreadScreen(messageId: thread.id),
          ),
        );

        ref.invalidate(messagesProvider);
        ref.invalidate(dashboardProvider);
      },
    );
  }

  String _formatDate(DateTime dt) {
    final now = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inDays == 0) return DateFormat('HH:mm').format(dt);
    if (diff.inDays < 7) return DateFormat('EEE').format(dt);
    return DateFormat('d MMM').format(dt);
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Thread detail screen
// ═══════════════════════════════════════════════════════════════════════════════

class MessageThreadScreen extends ConsumerWidget {
  final int messageId;
  const MessageThreadScreen({super.key, required this.messageId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(messageDetailProvider(messageId));

    return Scaffold(
      appBar: AppBar(
        title: async.maybeWhen(
          data: (t) =>
              Text(t.subject, maxLines: 1, overflow: TextOverflow.ellipsis),
          orElse: () => const Text('Message'),
        ),
        backgroundColor: const Color(0xFF2C3E6B),
        foregroundColor: Colors.white,
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: 'Failed to load thread\n$e',
          onRetry: () => ref.invalidate(messageDetailProvider(messageId)),
        ),
        data: (thread) => _ThreadBody(thread: thread),
      ),
    );
  }
}

class _ThreadBody extends ConsumerStatefulWidget {
  final MessageThread thread;
  const _ThreadBody({required this.thread});

  @override
  ConsumerState<_ThreadBody> createState() => _ThreadBodyState();
}

class _ThreadBodyState extends ConsumerState<_ThreadBody> {
  final _replyCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();

  @override
  void dispose() {
    _replyCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollCtrl.hasClients) {
        _scrollCtrl.animateTo(
          _scrollCtrl.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final replyState = ref.watch(
      replyProvider.select((m) => m[widget.thread.id] ?? const ReplyState()),
    );

    // Scroll to bottom when reply succeeds.
    ref.listen(
      replyProvider.select((m) => m[widget.thread.id] ?? const ReplyState()),
      (_, next) {
        if (next.success) {
          _replyCtrl.clear();
          ref.read(replyProvider.notifier).reset(widget.thread.id);
          _scrollToBottom();
        }
      },
    );

    final allMessages = <_Bubble>[
      _Bubble(
        body: widget.thread.body,
        isFromParent: true,
        sentAt: null, // original message; date not always in response
        isOriginal: true,
      ),
      for (final r in widget.thread.replies)
        _Bubble(
          body: r.body,
          isFromParent: r.senderRole == 'parent',
          sentAt: r.sentAt,
          isOriginal: false,
        ),
    ];

    return Column(
      children: [
        Expanded(
          child: ListView.builder(
            controller: _scrollCtrl,
            padding: const EdgeInsets.all(16),
            itemCount: allMessages.length,
            itemBuilder: (_, i) => allMessages[i],
          ),
        ),
        _ReplyBar(
          controller: _replyCtrl,
          isLoading: replyState.isLoading,
          error: replyState.error,
          onSend: () async {
            final body = _replyCtrl.text.trim();
            if (body.isEmpty) return;
            await ref
                .read(replyProvider.notifier)
                .reply(widget.thread.id, body);
          },
        ),
      ],
    );
  }
}

// ── Chat bubble ───────────────────────────────────────────────────────────────

class _Bubble extends StatelessWidget {
  final String body;
  final bool isFromParent;
  final DateTime? sentAt;
  final bool isOriginal;

  const _Bubble({
    required this.body,
    required this.isFromParent,
    required this.sentAt,
    required this.isOriginal,
  });

  @override
  Widget build(BuildContext context) {
    const parentColor = Color(0xFF2C3E6B);
    const adminColor = Color(0xFFF0F2F8);

    return Align(
      alignment: isFromParent ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: isFromParent ? parentColor : adminColor,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isFromParent ? 16 : 4),
            bottomRight: Radius.circular(isFromParent ? 4 : 16),
          ),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (isOriginal)
              Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Text(
                  isFromParent ? 'You' : 'School Admin',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: isFromParent ? Colors.white70 : Colors.grey.shade600,
                  ),
                ),
              ),
            Text(
              body,
              style: TextStyle(
                fontSize: 14,
                color: isFromParent ? Colors.white : Colors.black87,
                height: 1.4,
              ),
            ),
            if (sentAt != null)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  DateFormat('d MMM, HH:mm').format(sentAt!),
                  style: TextStyle(
                    fontSize: 10,
                    color: isFromParent ? Colors.white60 : Colors.grey.shade500,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ── Reply bar ─────────────────────────────────────────────────────────────────

class _ReplyBar extends StatelessWidget {
  final TextEditingController controller;
  final bool isLoading;
  final String? error;
  final VoidCallback onSend;

  const _ReplyBar({
    required this.controller,
    required this.isLoading,
    this.error,
    required this.onSend,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (error != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Text(
                error!,
                style: const TextStyle(color: Colors.red, fontSize: 12),
              ),
            ),
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: Colors.grey.shade200)),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: controller,
                    minLines: 1,
                    maxLines: 4,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: InputDecoration(
                      hintText: 'Type a reply…',
                      hintStyle: TextStyle(color: Colors.grey.shade400),
                      filled: true,
                      fillColor: Colors.grey.shade100,
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 10,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(24),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                isLoading
                    ? const SizedBox(
                        width: 40,
                        height: 40,
                        child: Padding(
                          padding: EdgeInsets.all(8),
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : IconButton(
                        onPressed: onSend,
                        icon: const Icon(Icons.send_rounded),
                        color: const Color(0xFF2C3E6B),
                        iconSize: 26,
                      ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Compose new message screen
// ═══════════════════════════════════════════════════════════════════════════════

class ComposeMessageScreen extends ConsumerStatefulWidget {
  const ComposeMessageScreen({super.key});

  @override
  ConsumerState<ComposeMessageScreen> createState() =>
      _ComposeMessageScreenState();
}

class _ComposeMessageScreenState extends ConsumerState<ComposeMessageScreen> {
  final _subjectCtrl = TextEditingController();
  final _bodyCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _subjectCtrl.dispose();
    _bodyCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final sendState = ref.watch(sendMessageProvider);

    // Navigate back on success.
    ref.listen(sendMessageProvider, (_, next) {
      if (next.success) {
        ref.read(sendMessageProvider.notifier).reset();
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Message sent to school admin.')),
        );
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('New Message'),
        backgroundColor: const Color(0xFF2C3E6B),
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            const Text(
              'Send a message to the school administration.',
              style: TextStyle(color: Colors.grey, fontSize: 13),
            ),
            const SizedBox(height: 20),

            // Subject
            TextFormField(
              controller: _subjectCtrl,
              textCapitalization: TextCapitalization.sentences,
              decoration: _inputDecoration('Subject'),
              validator: (v) => (v == null || v.trim().isEmpty)
                  ? 'Please enter a subject'
                  : null,
            ),
            const SizedBox(height: 16),

            // Body
            TextFormField(
              controller: _bodyCtrl,
              textCapitalization: TextCapitalization.sentences,
              minLines: 5,
              maxLines: 10,
              decoration: _inputDecoration('Message'),
              validator: (v) => (v == null || v.trim().isEmpty)
                  ? 'Please enter a message'
                  : null,
            ),
            const SizedBox(height: 8),

            if (sendState.error != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text(
                  sendState.error!,
                  style: const TextStyle(color: Colors.red, fontSize: 13),
                ),
              ),

            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: sendState.isLoading
                    ? null
                    : () async {
                        if (!_formKey.currentState!.validate()) return;
                        await ref
                            .read(sendMessageProvider.notifier)
                            .send(
                              subject: _subjectCtrl.text.trim(),
                              body: _bodyCtrl.text.trim(),
                            );
                      },
                icon: sendState.isLoading
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.send_rounded),
                label: Text(sendState.isLoading ? 'Sending…' : 'Send Message'),
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF2C3E6B),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String label) => InputDecoration(
    labelText: label,
    filled: true,
    fillColor: Colors.grey.shade50,
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: BorderSide(color: Colors.grey.shade300),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: BorderSide(color: Colors.grey.shade300),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: const BorderSide(color: Color(0xFF2C3E6B), width: 1.5),
    ),
  );
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
