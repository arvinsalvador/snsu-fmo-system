import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';
import '../config/app_config.dart';
import '../storage/token_storage.dart';
import 'api_error.dart';

class ApiClient {
  ApiClient(AppConfig config, this._tokens, {Dio? dio, this.onUnauthorized}) : dio = dio ?? Dio(BaseOptions(baseUrl: config.baseUrl, connectTimeout: config.timeout, receiveTimeout: config.timeout, sendTimeout: config.timeout, headers: const {'Accept': 'application/json'})) {
    this.dio.interceptors.add(InterceptorsWrapper(onRequest: (options, handler) async { final token = await _tokens.read(); if (token != null) options.headers['Authorization'] = 'Bearer $token'; if (config.networkLogs && kDebugMode) debugPrint('API ${options.method} ${options.path}'); handler.next(options); }, onError: (error, handler) async { if (error.response?.statusCode == 401) { await _tokens.clear(); onUnauthorized?.call(); } handler.next(error); }));
  }
  final Dio dio;
  final TokenStorage _tokens;
  final VoidCallback? onUnauthorized;
  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) => _request('GET', path, query: query);
  Future<Map<String, dynamic>> post(String path, {Object? data, bool idempotent = false}) => _request('POST', path, data: data, idempotent: idempotent);
  Future<Map<String, dynamic>> patch(String path, {Object? data, bool idempotent = false}) => _request('PATCH', path, data: data, idempotent: idempotent);
  Future<Map<String, dynamic>> delete(String path) => _request('DELETE', path);
  Future<List<int>> download(String path, {void Function(int, int)? onProgress}) async {
    try {
      final response = await dio.get<List<int>>(
        path,
        options: Options(responseType: ResponseType.bytes),
        onReceiveProgress: onProgress,
      );
      return response.data ?? const <int>[];
    } on DioException catch (error) {
      throw _errorFrom(error);
    }
  }
  Future<Map<String, dynamic>> _request(String method, String path, {Object? data, Map<String, dynamic>? query, bool idempotent = false}) async {
    try { final response = await dio.request<Object?>(path, data: data, queryParameters: query, options: Options(method: method, headers: idempotent ? {'Idempotency-Key': const Uuid().v4()} : null)); if (response.statusCode == 204) return <String, dynamic>{}; if (response.data is! Map) throw const ApiError(code: 'INVALID_RESPONSE', message: 'The server returned an unexpected response.'); return Map<String, dynamic>.from(response.data! as Map); }
    on DioException catch (error) { throw _errorFrom(error); }
  }

  ApiError _errorFrom(DioException error) {
    final body = error.response?.data;
    if (body is Map) {
      final json = Map<String, dynamic>.from(body);
      final rawFields = json['errors'] as Map? ?? const {};
      return ApiError(code: json['error_code'] as String? ?? 'REQUEST_FAILED', message: json['message'] as String? ?? '', statusCode: error.response?.statusCode, fields: rawFields.map((key, value) => MapEntry(key.toString(), value is List ? value.map(Object.toString).toList() : [value.toString()])));
    }
    final code = switch (error.type) {
      DioExceptionType.connectionTimeout || DioExceptionType.receiveTimeout || DioExceptionType.sendTimeout => 'TIMEOUT',
      DioExceptionType.badCertificate => 'SSL_ERROR',
      _ => 'NETWORK_ERROR',
    };
    return ApiError(code: code, message: '', statusCode: error.response?.statusCode);
  }
}
