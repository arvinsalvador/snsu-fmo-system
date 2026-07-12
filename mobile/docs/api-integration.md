# API Integration

`ApiClient` owns the API root, timeout, bearer injection, idempotency keys, 401 session clearing, authenticated byte downloads, and safe error conversion. Debug logging includes method and path only; request bodies, passwords, tokens, and attachments are never logged.

Repositories parse the Phase 11A `success`, `message`, `data`, `meta`, and `links` envelope. Stable errors map to user-readable messages; validation fields remain structured. A 204 response is accepted for delete operations.

QR payloads must match `snsu-fmo:asset:<uuid>`. The identifier is sent to authenticated `/assets/lookup/{identifier}` and is never treated as authorization. Notification navigation accepts only relative, allowlisted internal routes. Attachments are downloaded as authenticated bytes rather than opened through token-bearing public URLs.

The mobile creation form uses numeric reference IDs because Laravel foreign-key validation requires them. Phase 11B corrected `/reference-data` to return both numeric IDs for form submission and UUIDs for identity/deep-link use.
