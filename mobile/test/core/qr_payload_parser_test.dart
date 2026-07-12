import 'package:flutter_test/flutter_test.dart';
import 'package:snsu_fmo_mobile/core/utils/qr_payload_parser.dart';

void main() {
  test('accepts only the documented asset payload', () {
    expect(QrPayloadParser.assetIdentifier('snsu-fmo:asset:123e4567-e89b-12d3-a456-426614174000'), '123e4567-e89b-12d3-a456-426614174000');
    expect(QrPayloadParser.assetIdentifier('https://example.com/token'), isNull);
    expect(QrPayloadParser.assetIdentifier('snsu-fmo:asset:short'), isNull);
  });
}
