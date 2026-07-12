class ApiEnvelope<T> {
  const ApiEnvelope({required this.data, required this.message, this.meta, this.links});
  factory ApiEnvelope.fromJson(Map<String, dynamic> json, T Function(Object?) decode) {
    if (json['success'] != true) throw const FormatException('Expected a successful API envelope.');
    return ApiEnvelope(data: decode(json['data']), message: json['message'] as String? ?? '', meta: json['meta'] as Map<String, dynamic>?, links: json['links'] as Map<String, dynamic>?);
  }
  final T data;
  final String message;
  final Map<String, dynamic>? meta;
  final Map<String, dynamic>? links;
}
