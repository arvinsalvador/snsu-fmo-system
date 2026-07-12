# SNSU FMO API v1

Base path: `/api/v1`. Send `Accept: application/json` and `Authorization: Bearer <token>`. API timestamps are ISO 8601 UTC; date-only fields use `YYYY-MM-DD`. Native mobile clients are not governed by browser CORS, while browser origins must be listed in `CORS_ALLOWED_ORIGINS`.

## Response Contract

Success responses contain `success`, `message`, `data`, and `meta`; paginated responses also contain `links.first`, `last`, `prev`, and `next`. Errors contain `success: false`, `message`, `errors`, and `error_code`. Stable codes are `VALIDATION_ERROR`, `UNAUTHENTICATED`, `FORBIDDEN`, `NOT_FOUND`, `CONFLICT`, `RATE_LIMITED`, and `REQUEST_FAILED`.

## Authentication

`POST /auth/login` accepts email, password, and device name. Current-device logout is `POST /auth/logout`; all-device revocation is `POST /auth/logout-all`. `/auth/user`, `/auth/permissions`, and legacy `/auth/me` return the authenticated identity, roles, permissions, and current single-campus scope. Inactive users are rejected. Login/password routes are limited to 10 requests/minute/IP.

## Pagination and Filters

Lists accept `page` and `per_page` (1-100, default 20). Work orders support search, status, approval, priority, category, department, building, requestor, date range, `updated_after`, `sort_by`, and `sort_direction`. Assets support search, category/location/status, `updated_after`, and allowlisted sorting. `updated_after` must be an ISO 8601 timestamp.

## Retry Safety

Authenticated mutations may send `Idempotency-Key` (maximum 100 characters). Reusing the same key, user, path, method, and request body replays the successful JSON response with `Idempotency-Replayed: true`. Reusing it for different input returns `409 CONFLICT`. This is retry preparation, not offline synchronization.

## QR Lookup

`GET /assets/lookup/{identifier}` accepts an asset UUID, non-sequential QR UUID, asset code, or permitted serial number. QR payload recommendation: `snsu-fmo:asset:<qr-token>`. The token identifies an asset but never grants access; Sanctum authentication and the asset policy still apply.

## Uploads and Downloads

Uploads use `multipart/form-data`, server-generated private paths, and a 5 MB limit. Asset photos accept JPEG, PNG, or WebP. Work-order attachments accept those images or PDF. Resources return metadata and authenticated download URLs, never storage paths. Missing files return 404 and unrelated records return 403.

## Mobile Bootstrap

`GET /mobile/dashboard` is permission-sensitive. `GET /reference-data` returns active form references. `GET /system/info` returns v1, UTC server time, minimum mobile version, feature flags, and upload limits without framework or infrastructure details. Notifications support pagination, unread/type/date filters, unread count, read/read-all, and deletion.

## Rate Limits

General authenticated API: 120/minute/user; login and password: 10/minute/IP; uploads: 20/minute/user; QR: 60/minute/user; exports/report generation should use their named stricter middleware. A 429 uses `RATE_LIMITED`.

## State and Scope

The API uses the same services and policies as Blade workflows. Requestors see their own work orders; assigned staff receive assigned-work access; operational permissions control approval, assignment, maintenance, inventory, reports, and KPI actions. The database currently represents one campus and has no user-to-building scope mapping, so building scope cannot yet be inferred from the user.

