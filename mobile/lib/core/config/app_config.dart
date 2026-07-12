import 'package:flutter/foundation.dart';

@immutable
class AppConfig {
  const AppConfig({required this.baseUrl, required this.timeout, required this.environment, required this.networkLogs});
  factory AppConfig.fromEnvironment() {
    const baseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8080/api/v1');
    const seconds = int.fromEnvironment('REQUEST_TIMEOUT_SECONDS', defaultValue: 20);
    const environment = String.fromEnvironment('APP_ENV', defaultValue: 'development');
    const networkLogs = bool.fromEnvironment('ENABLE_NETWORK_LOGS');
    if (environment == 'production' && !baseUrl.startsWith('https://')) throw StateError('Production API_BASE_URL must use HTTPS.');
    return const AppConfig(baseUrl: baseUrl, timeout: Duration(seconds: seconds), environment: environment, networkLogs: networkLogs);
  }
  final String baseUrl;
  final Duration timeout;
  final String environment;
  final bool networkLogs;
}
