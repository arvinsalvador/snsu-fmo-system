import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:snsu_fmo_mobile/shared/widgets.dart';

void main() {
  testWidgets('status badge includes text and semantics', (tester) async {
    await tester.pumpWidget(const MaterialApp(home: Scaffold(body: StatusBadge('in_progress'))));
    expect(find.text('in progress'), findsOneWidget);
    expect(find.bySemanticsLabel('Status in progress'), findsOneWidget);
  });
  testWidgets('error panel exposes retry', (tester) async {
    var retried = false;
    await tester.pumpWidget(MaterialApp(home: ErrorPanel('Failed', onRetry: () => retried = true)));
    await tester.tap(find.text('Retry'));
    expect(retried, isTrue);
  });
}
