import 'package:flutter_test/flutter_test.dart';
import 'package:snsu_fmo_mobile/core/network/api_error.dart';

void main() {
  test('stable API codes map to safe user messages', () {
    expect(const ApiError(code: 'UNAUTHENTICATED', message: 'raw').userMessage, contains('session has expired'));
    expect(const ApiError(code: 'CONFLICT', message: 'raw').userMessage, contains('Refresh'));
    expect(const ApiError(code: 'SERVER_ERROR', message: 'secret stack').userMessage, isNot(contains('secret stack')));
  });
  test('validation field errors remain structured', () {
    const error = ApiError(code: 'VALIDATION_ERROR', message: 'Invalid.', fields: {'email': ['Required.']});
    expect(error.fields['email'], ['Required.']);
  });
}
