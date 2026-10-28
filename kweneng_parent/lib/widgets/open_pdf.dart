import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';

/// Keep a successful download available even if the external viewer fails.
Future<void> openPdf(
  BuildContext context,
  String path, {
  Future<OpenResult> Function(String)? opener,
}) async {
  ResultType result;
  try {
    result =
        (await (opener?.call(path) ??
                OpenFilex.open(path, type: 'application/pdf')))
            .type;
  } catch (_) {
    result = ResultType.error;
  }
  if (!context.mounted || result == ResultType.done) return;
  final message = switch (result) {
    ResultType.noAppToOpen =>
      'The report downloaded successfully, but this device has no app that can '
          'open PDFs. Install a PDF viewer on this device, then return and tap '
          'Open again.',
    ResultType.fileNotFound =>
      'The downloaded report is no longer on this device. Tap Re-download, '
          'then try opening it again.',
    ResultType.permissionDenied =>
      'The PDF viewer could not access this report. Try another PDF viewer '
          'or tap Re-download and try again.',
    _ =>
      'The report could not be opened. Try another PDF viewer or tap '
          'Re-download and try again.',
  };
  await showDialog<void>(
    context: context,
    builder: (context) => AlertDialog(
      title: Text(
        result == ResultType.noAppToOpen
            ? 'PDF viewer needed'
            : 'Could not open report',
      ),
      content: Text(message),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('OK'),
        ),
      ],
    ),
  );
}
