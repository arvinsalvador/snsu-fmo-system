import 'package:flutter_test/flutter_test.dart';
import 'package:snsu_fmo_mobile/data/models/app_notification.dart';
import 'package:snsu_fmo_mobile/data/models/dashboard.dart';
import 'package:snsu_fmo_mobile/data/models/user.dart';

void main() {
  test('permissions, not roles, drive capability helpers', () {
    final user = AppUser.fromJson({'uuid':'u','name':'Test','email':'test@example.com','roles':['Student'],'permissions':['view_assets']});
    expect(user.canViewAssets, isTrue);
    expect(user.canApproveWorkOrders, isFalse);
  });
  test('dashboard preserves server-authorized cards only', () {
    final value = DashboardSummary.fromJson({'assigned_work_orders': 2});
    expect(value['assigned_work_orders'], 2);
    expect(value.values.containsKey('pending_approvals'), isFalse);
  });
  test('notification reads mobile route without trusting action url', () {
    final value = AppNotification.fromJson({'uuid':'n','type':'assigned','title':'Assigned','mobile_route':'/work-orders/uuid','action_url':'https://bad.example','is_read':false,'created_at':'2026-07-13T00:00:00Z'});
    expect(value.route, '/work-orders/uuid');
  });
}
