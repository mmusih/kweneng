import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class PoweredByFooter extends StatelessWidget {
  const PoweredByFooter({super.key});

  static final Uri _providerUrl = Uri.parse('https://dbsystems.tech');

  Future<void> _openProviderWebsite() async {
    await launchUrl(_providerUrl, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      child: Center(
        child: Semantics(
          link: true,
          label: 'Powered by dbsystems.tech',
          child: TextButton(
            onPressed: _openProviderWebsite,
            style: TextButton.styleFrom(
              foregroundColor: Colors.blueGrey.shade500,
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              textStyle: textTheme.bodySmall,
            ),
            child: const Text('Powered by dbsystems.tech'),
          ),
        ),
      ),
    );
  }
}
