class QrPayloadParser {
  static const prefix = 'snsu-fmo:asset:';
  static String? assetIdentifier(String value) { final trimmed = value.trim(); if (!trimmed.startsWith(prefix)) return null; final id = trimmed.substring(prefix.length); return RegExp(r'^[0-9a-fA-F-]{36}$').hasMatch(id) ? id : null; }
}
