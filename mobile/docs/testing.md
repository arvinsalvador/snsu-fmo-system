# Testing

The initial suite covers stable error mapping, validation fields, QR payload rejection, permission-derived capabilities, dashboard parsing, safe notification route parsing, and shared loading/error/status widgets. `integration_test/app_flow_test.dart` establishes the harness for injected fake-server flows without camera hardware or a production API.

Run:

```bash
flutter pub get
dart format --output=none --set-exit-if-changed lib test integration_test
flutter analyze
flutter test
flutter test integration_test
flutter build apk --debug
```

Flutter and Dart were unavailable in the implementation environment, so these commands are mandatory before accepting a distributable build. Future tests should expand repository transport fakes, auth restoration, router redirects, list pagination, mutation conflict handling, scanner controller permission states, and full profile/work-order/maintenance widget flows.
