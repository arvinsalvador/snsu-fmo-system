# API Inventory

All routes below use `/api/v1`; protected groups use `auth:sanctum`, `throttle:api`, policies/Gates, and idempotency middleware for mutations.

| Area | Routes | Validation / Resource | Pagination and scope |
|---|---|---|---|
| Auth/Profile | `/auth/login`, `/logout`, `/logout-all`, `/user`, `/permissions`, `/profile`, `/profile/change-password` | Login/Profile Form Requests, UserResource | Current user only |
| Work orders | REST `/work-orders`, approval, assignment, updates, materials, followups, evaluation, attachments | Work-order action requests/resources; domain services | Scoped repository; filters and `updated_after` |
| Assets | REST `/assets`, `/assets/lookup/{identifier}`, photos/download | Asset requests and resources | Policy protected; paginated and incremental |
| Maintenance | schedules, records, reviews, corrections, history | Existing requests/resources/services | Permission/policy protected and paginated |
| Inventory | items, movements, stock-in, adjustment, intelligence, work-order material use | Existing requests/resources/services | Transactional stock rules; paginated reports |
| Notifications | list, unread-count, read, read-all, delete | NotificationIndexRequest, DatabaseNotificationResource | Own notifications only; paginated/filtered |
| Reports/KPI | report summaries, generation, schedules, templates, KPI definitions/targets/actions | Existing Phase 10 resources and requests | Management permissions and private downloads |
| Mobile bootstrap | `/mobile/dashboard`, `/reference-data`, `/system/info` | Structured JSON/MobileAssetResource | Conditional permission sections |

Existing route names and legacy aliases are retained. `/auth/me`, PATCH `/notifications/read-all`, and search-based `/assets/lookup` are deprecated compatibility surfaces; new clients should use `/auth/user`, POST read-all, and identifier lookup.

