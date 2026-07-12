class SystemInfo {
  const SystemInfo({required this.apiVersion, required this.serverTime, required this.maintenanceMode, required this.features, this.minimumMobileVersion, this.uploadLimits = const {}});
  factory SystemInfo.fromJson(Map<String,dynamic> json)=>SystemInfo(apiVersion:json['api_version'] as String,serverTime:DateTime.parse(json['server_time'] as String).toUtc(),maintenanceMode:json['maintenance_mode'] as bool? ?? false,minimumMobileVersion:json['minimum_mobile_version'] as String?,features:Map<String,bool>.from(json['features'] as Map? ?? const {}),uploadLimits:Map<String,dynamic>.from(json['upload_limits'] as Map? ?? const {}));
  final String apiVersion; final DateTime serverTime; final bool maintenanceMode; final String? minimumMobileVersion; final Map<String,bool> features;
  bool enabled(String key)=>features[key]??false;
  final Map<String,dynamic> uploadLimits;
}
