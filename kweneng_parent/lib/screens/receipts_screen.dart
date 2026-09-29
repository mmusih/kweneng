import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import '../services/api_service.dart';

class ReceiptsScreen extends StatefulWidget {
  const ReceiptsScreen({super.key, this.loadPage});

  final Future<Map<String, dynamic>> Function(int page)? loadPage;
  @override
  State<ReceiptsScreen> createState() => _ReceiptsScreenState();
}

class _ReceiptsScreenState extends State<ReceiptsScreen> {
  final _api = ApiService();
  final Set<int> _downloading = {};
  late Future<Map<String, dynamic>> _receipts;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _receipts = _fetch(1);
  }

  Future<Map<String, dynamic>> _fetch(int page) =>
      widget.loadPage?.call(page) ?? _api.getReceipts(page: page);

  void _load(int page) {
    setState(() {
      _page = page;
      _receipts = _fetch(page);
    });
  }

  Future<void> _download(int id) async {
    setState(() => _downloading.add(id));
    try {
      final file = await _api.downloadReceipt(id);
      final result = await OpenFilex.open(file.path);
      if (result.type != ResultType.done && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Receipt downloaded. Install a PDF viewer to open it.',
            ),
          ),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Could not download this receipt. Please try again.'),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _downloading.remove(id));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text('Payments & receipts'),
      actions: [
        IconButton(
          onPressed: () => _load(_page),
          icon: const Icon(Icons.refresh),
          tooltip: 'Refresh',
        ),
      ],
    ),
    body: FutureBuilder<Map<String, dynamic>>(
      future: _receipts,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('Could not load receipts.'),
                TextButton(
                  onPressed: () => _load(_page),
                  child: const Text('Try again'),
                ),
              ],
            ),
          );
        }
        final data = snapshot.data!;
        final receipts = List<Map<String, dynamic>>.from(data['data'] as List);
        return RefreshIndicator(
          onRefresh: () async {
            _load(_page);
            await _receipts;
          },
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            children: [
              const Text(
                'Receipts include fees and other payments shared with your account.',
              ),
              const SizedBox(height: 16),
              if (receipts.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 48),
                  child: Text(
                    'No receipts available yet. Contact Accounts if a receipt is missing.',
                    textAlign: TextAlign.center,
                  ),
                ),
              ...receipts.map((receipt) {
                final id = receipt['id'] as int;
                final minor =
                    num.tryParse(receipt['amount_minor'].toString()) ?? 0;
                final items = List<Map<String, dynamic>>.from(
                  receipt['items'] as List,
                );
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          receipt['receipt_number'].toString(),
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        Text(
                          'P${(minor / 100).toStringAsFixed(2)}',
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                        Text('${receipt['paid_on']} · ${receipt['status']}'),
                        if (receipt['status'] == 'reversed')
                          const Text(
                            'This payment has been reversed.',
                            style: TextStyle(color: Colors.red),
                          ),
                        const SizedBox(height: 8),
                        ...items.map(
                          (item) => Text(
                            '${item['category']} — ${item['description']}${item['student_name'] == null ? '' : ' · ${item['student_name']}'}',
                          ),
                        ),
                        const SizedBox(height: 8),
                        OutlinedButton.icon(
                          onPressed: _downloading.contains(id)
                              ? null
                              : () => _download(id),
                          icon: const Icon(Icons.download_outlined),
                          label: Text(
                            _downloading.contains(id)
                                ? 'Downloading…'
                                : 'Download receipt PDF',
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }),
              Wrap(
                alignment: WrapAlignment.spaceBetween,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  TextButton(
                    onPressed: _page > 1 ? () => _load(_page - 1) : null,
                    child: const Text('Previous'),
                  ),
                  Text('Page $_page'),
                  TextButton(
                    onPressed: data['next_page_url'] != null
                        ? () => _load(_page + 1)
                        : null,
                    child: const Text('Next'),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    ),
  );
}
