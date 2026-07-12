import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'providers.dart';

class AppScaffold extends ConsumerWidget {
  const AppScaffold({required this.child, super.key});
  final Widget child;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;
    final info = auth.systemInfo;
    final items = <({String path, IconData icon, String label})>[
      (path: '/dashboard', icon: Icons.dashboard_outlined, label: 'Dashboard'),
      if (user?.canViewWorkOrders ?? false)
        (path: '/work-orders', icon: Icons.build_outlined, label: 'Work orders'),
      if ((user?.canViewAssets ?? false) && (info?.enabled('mobile_asset_qr_lookup') ?? false))
        (path: '/assets', icon: Icons.precision_manufacturing_outlined, label: 'Assets'),
      if (user?.canViewMaintenance ?? false)
        (path: '/maintenance', icon: Icons.event_available_outlined, label: 'Maintenance'),
      (path: '/notifications', icon: Icons.notifications_outlined, label: 'Notifications'),
      (path: '/profile', icon: Icons.person_outline, label: 'Profile'),
    ];
    final location = GoRouterState.of(context).uri.path;
    var index = items.indexWhere((e) => location.startsWith(e.path));
    if (index < 0) index = 0;
    final unread = ref.watch(unreadCountProvider).valueOrNull ?? 0;
    return Scaffold(
      appBar: AppBar(title: const Text('SNSU FMO'), actions: [
        Badge(isLabelVisible: unread > 0, label: Text(unread > 99 ? '99+' : '$unread'),
          child: IconButton(tooltip: 'Notifications', onPressed: () => context.go('/notifications'), icon: const Icon(Icons.notifications_outlined))),
        IconButton(tooltip: 'Profile', onPressed: () => context.go('/profile'), icon: const Icon(Icons.account_circle_outlined)),
      ]),
      body: SafeArea(child: child),
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (i) => context.go(items[i].path),
        destinations: [for (final item in items) NavigationDestination(icon: Icon(item.icon), label: item.label)],
      ),
    );
  }
}
