class AppUser {
  const AppUser({required this.uuid, required this.name, required this.email, required this.roles, required this.permissions, this.mobileNumber, this.scope = const {}});
  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(uuid: json['uuid'] as String, name: json['name'] as String? ?? '', email: json['email'] as String? ?? '', mobileNumber: json['mobile_number'] as String?, roles: _strings(json['roles']), permissions: _strings(json['permissions']), scope: Map<String,dynamic>.from(json['authorized_scope'] as Map? ?? json['scope'] as Map? ?? const {}));
  final String uuid, name, email;
  final String? mobileNumber;
  final Set<String> roles, permissions;
  final Map<String,dynamic> scope;
  bool can(String permission) => permissions.contains(permission);
  bool get canViewWorkOrders => can('view_work_orders');
  bool get canCreateWorkOrder => can('create_work_orders');
  bool get canApproveWorkOrders => can('approve_work_orders');
  bool get canAssignWorkOrders => can('assign_work_orders');
  bool get canViewAssets => can('view_assets');
  bool get canViewMaintenance => can('view_maintenance_schedules') || can('view_maintenance_records');
  bool get canCompleteMaintenance => can('complete_maintenance_schedules');
  bool get canViewInventory => can('view_inventory');
  bool get canViewReports => can('view_reports');
  bool get canViewKpis => can('view_kpi_scorecards');
  static Set<String> _strings(Object? value) => value is List ? value.map(Object.toString).toSet() : <String>{};
}
