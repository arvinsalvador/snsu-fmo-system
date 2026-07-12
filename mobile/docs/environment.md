# Environment

Compile-time configuration uses `--dart-define`:

| Name | Default | Purpose |
| --- | --- | --- |
| `API_BASE_URL` | `http://10.0.2.2:8080/api/v1` | Versioned API root |
| `APP_ENV` | `development` | Enforces HTTPS when `production` |
| `REQUEST_TIMEOUT_SECONDS` | `20` | Connect, send, and receive timeout |
| `ENABLE_NETWORK_LOGS` | `false` | Debug method/path logging only |

Android emulator uses `10.0.2.2`; a physical device uses the development machine's LAN address. Local HTTP is allowed by the Android debug manifest only. Release configuration rejects a non-HTTPS production URL. iOS local-network and build settings remain unverified because this environment has neither Flutter nor Xcode.

Feature availability and upload limits come from `/system/info`; controlled form values come from `/reference-data`. No production host, token, or secret is committed.
