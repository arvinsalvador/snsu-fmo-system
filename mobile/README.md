# SNSU FMO Mobile

Online-first Flutter foundation for the SNSU Facilities Management Office system. The app lives in `mobile/`, separate from Laravel. It requires server validation at startup and deliberately contains no offline database, background synchronization, or queued mutations.

## Setup

Install a stable Flutter SDK compatible with Dart 3.5+, then run:

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8080/api/v1
```

Use `http://<LAN-IP>:8080/api/v1` for a physical device. Production must set `APP_ENV=production` and an HTTPS `API_BASE_URL`. Never place credentials or bearer tokens in source.

The checked-in Android files define `edu.snsu.fmo.mobile`, minimum SDK 23, internet/camera access, and debug-only cleartext networking. The iOS plist contains the app name and camera usage description. Because no Flutter SDK was available during creation, run `flutter create --platforms=android,ios .` only if generated platform artifacts are missing, then carefully retain the manifest, package ID, and plist settings documented here.

## Verification

```bash
dart format --output=none --set-exit-if-changed lib test integration_test
flutter analyze
flutter test
flutter test integration_test
flutter build apk --debug
```

See `docs/` for architecture, API integration, environments, limitations, and testing.
