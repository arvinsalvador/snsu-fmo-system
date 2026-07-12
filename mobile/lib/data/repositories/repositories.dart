import '../../core/network/api_client.dart';
import '../models/app_notification.dart';
import '../models/asset.dart';
import '../models/dashboard.dart';
import '../models/maintenance.dart';
import '../models/system_info.dart';
import '../models/user.dart';
import '../models/work_order.dart';

Map<String, dynamic> _map(Object? value) =>
    Map<String, dynamic>.from(value! as Map);
List<Map<String, dynamic>> _maps(Object? value) =>
    (value as List? ?? const []).map(_map).toList();
Object? _data(Map<String, dynamic> json) => json['data'];

class PageResult<T> {
  const PageResult(this.items, {this.page = 1, this.lastPage = 1});
  final List<T> items;
  final int page;
  final int lastPage;
}

class AuthRepository {
  AuthRepository(this.api);
  final ApiClient api;
  Future<(String, AppUser)> login(String email, String password) async {
    final json = await api.post('/auth/login', data: {
      'email': email,
      'password': password,
      'device_name': 'SNSU FMO Mobile',
    });
    final data = _map(_data(json));
    final user = _map(data['user'])
      ..['roles'] = data['roles']
      ..['permissions'] = data['permissions']
      ..['authorized_scope'] = data['authorized_scope'];
    return (data['token'] as String, AppUser.fromJson(user));
  }

  Future<AppUser> currentUser() async {
    final data = _map(_data(await api.get('/auth/user')));
    final authorization = _map(_data(await api.get('/auth/permissions')));
    final user = _map(data['user'] ?? data)
      ..['roles'] = authorization['roles']
      ..['permissions'] = authorization['permissions']
      ..['scope'] = authorization['scope'];
    return AppUser.fromJson(user);
  }

  Future<void> logout() => api.post('/auth/logout').then((_) {});
  Future<void> logoutAll() => api.post('/auth/logout-all').then((_) {});
}

class SystemRepository {
  SystemRepository(this.api);
  final ApiClient api;
  Future<SystemInfo> info() async =>
      SystemInfo.fromJson(_map(_data(await api.get('/system/info'))));
}

class DashboardRepository {
  DashboardRepository(this.api);
  final ApiClient api;
  Future<DashboardSummary> load() async => DashboardSummary.fromJson(
      _map(_data(await api.get('/mobile/dashboard'))));
}

class WorkOrderRepository {
  WorkOrderRepository(this.api);
  final ApiClient api;
  Future<PageResult<WorkOrder>> list({
    int page = 1,
    String? search,
    String? statusId,
    String? priorityId,
    String? categoryId,
    bool assignedToMe = false,
  }) async {
    final json = await api.get('/work-orders', query: {
      'page': page,
      'per_page': 20,
      if (search?.isNotEmpty ?? false) 'search': search,
      if (statusId != null) 'status_id': statusId,
      if (priorityId != null) 'priority_id': priorityId,
      if (categoryId != null) 'category_id': categoryId,
      if (assignedToMe) 'assigned_to_me': 1,
    });
    final meta = json['meta'] is Map ? _map(json['meta']) : <String, dynamic>{};
    return PageResult(
      _maps(_data(json)).map(WorkOrder.fromJson).toList(),
      page: (meta['current_page'] as num?)?.toInt() ?? page,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
    );
  }

  Future<WorkOrder> detail(String uuid) async {
    final data = _map(_data(await api.get('/work-orders/$uuid')));
    return WorkOrder.fromJson(_map(data['work_order'] ?? data));
  }

  Future<WorkOrder> create(Map<String, dynamic> input) async {
    final data = _map(_data(await api.post('/work-orders', data: input, idempotent: true)));
    return WorkOrder.fromJson(_map(data['work_order'] ?? data));
  }

  Future<void> approve(String uuid, String? remarks) => api
      .post('/work-orders/$uuid/approve', data: {'remarks': remarks}, idempotent: true)
      .then((_) {});
  Future<void> reject(String uuid, String reason) => api
      .post('/work-orders/$uuid/reject', data: {'remarks': reason, 'reason': reason}, idempotent: true)
      .then((_) {});
  Future<List<Map<String, dynamic>>> updates(String uuid) async =>
      _maps(_data(await api.get('/work-orders/$uuid/updates')));
  Future<void> addProgress(String uuid, Map<String, dynamic> input) => api
      .post('/work-orders/$uuid/updates', data: input, idempotent: true)
      .then((_) {});
}

class AssetRepository {
  AssetRepository(this.api);
  final ApiClient api;
  Future<PageResult<AssetSummary>> list({
    int page = 1,
    String? search,
    String? status,
    String? categoryId,
    String? buildingId,
  }) async {
    final json = await api.get('/assets', query: {
      'page': page,
      'per_page': 20,
      if (search?.isNotEmpty ?? false) 'search': search,
      if (status != null) 'status': status,
      if (categoryId != null) 'asset_category_id': categoryId,
      if (buildingId != null) 'building_id': buildingId,
    });
    final meta = json['meta'] is Map ? _map(json['meta']) : <String, dynamic>{};
    return PageResult(_maps(_data(json)).map(AssetSummary.fromJson).toList(),
        page: (meta['current_page'] as num?)?.toInt() ?? page,
        lastPage: (meta['last_page'] as num?)?.toInt() ?? 1);
  }

  Future<AssetSummary> lookup(String identifier) async => AssetSummary.fromJson(
      _map(_data(await api.get('/assets/lookup/$identifier'))));
  Future<AssetSummary> detail(String uuid) async {
    final data = _map(_data(await api.get('/assets/$uuid')));
    return AssetSummary.fromJson(_map(data['asset'] ?? data));
  }
}

class MaintenanceRepository {
  MaintenanceRepository(this.api);
  final ApiClient api;
  Future<List<MaintenanceSchedule>> schedules({bool overdue = false}) async =>
      _maps(_data(await api.get('/maintenance-schedules/${overdue ? 'overdue' : 'upcoming'}')))
          .map(MaintenanceSchedule.fromJson)
          .toList();
  Future<MaintenanceSchedule> detail(String id) async {
    final data = _map(_data(await api.get('/maintenance-schedules/$id')));
    return MaintenanceSchedule.fromJson(_map(data['maintenance_schedule'] ?? data));
  }

  Future<void> complete(String id, Map<String, dynamic> input) => api
      .post('/maintenance-schedules/$id/complete', data: input, idempotent: true)
      .then((_) {});
}

class NotificationRepository {
  NotificationRepository(this.api);
  final ApiClient api;
  Future<PageResult<AppNotification>> list({bool unread = false, int page = 1}) async {
    final json = await api.get('/notifications', query: {
      'page': page,
      'per_page': 20,
      if (unread) 'unread': 1,
    });
    final meta = json['meta'] is Map ? _map(json['meta']) : <String, dynamic>{};
    return PageResult(_maps(_data(json)).map(AppNotification.fromJson).toList(),
        page: (meta['current_page'] as num?)?.toInt() ?? page,
        lastPage: (meta['last_page'] as num?)?.toInt() ?? 1);
  }

  Future<int> unreadCount() async =>
      (_map(_data(await api.get('/notifications/unread-count')))['count'] as num).toInt();
  Future<void> read(String uuid) => api.patch('/notifications/$uuid/read').then((_) {});
  Future<void> readAll() => api.patch('/notifications/read-all').then((_) {});
  Future<void> delete(String uuid) => api.delete('/notifications/$uuid').then((_) {});
}

class ProfileRepository {
  ProfileRepository(this.api);
  final ApiClient api;
  Future<AppUser> load() async =>
      AppUser.fromJson(_map(_data(await api.get('/profile'))));
  Future<AppUser> update(Map<String, dynamic> input) async => AppUser.fromJson(
      _map(_data(await api.patch('/profile', data: input, idempotent: true))));
  Future<void> password(Map<String, dynamic> input) => api
      .post('/profile/change-password', data: input, idempotent: true)
      .then((_) {});
}

class ReferenceRepository {
  ReferenceRepository(this.api);
  final ApiClient api;
  Map<String, dynamic>? _memory;
  Future<Map<String, dynamic>> load({bool refresh = false}) async {
    if (!refresh && _memory != null) return _memory!;
    return _memory = _map(_data(await api.get('/reference-data')));
  }
}
