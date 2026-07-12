class ApiError implements Exception {
  const ApiError({required this.code, required this.message, this.statusCode, this.fields = const {}});
  final String code;
  final String message;
  final int? statusCode;
  final Map<String, List<String>> fields;
  static String messageFor(Object? error) =>
      error is ApiError ? error.userMessage : 'The request could not be completed. Please try again.';
  String get userMessage => switch (code) {
    'UNAUTHENTICATED' => 'Your session has expired. Please sign in again.',
    'FORBIDDEN' => 'You do not have permission to perform this action.',
    'CONFLICT' || 'INVALID_STATE_TRANSITION' => 'This record has changed or the action is no longer valid. Refresh and try again.',
    'RATE_LIMITED' => 'Too many requests. Please wait before trying again.',
    'SERVER_ERROR' => 'The server could not complete the request. Please try again later.',
    'NETWORK_ERROR' => 'The server is unavailable. Check your connection and try again.',
    'TIMEOUT' => 'The request timed out. Check your connection and try again.',
    'SSL_ERROR' => 'A secure connection could not be established.',
    _ => message.isEmpty ? 'The request could not be completed.' : message,
  };
}
