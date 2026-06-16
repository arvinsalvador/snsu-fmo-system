# API_SPECIFICATION.md

# SNSU Facilities Management Office (FMO) Management System

## REST API Master Specification

---

# Purpose

This document defines the API architecture, endpoint standards, authentication rules, response formats, and future mobile synchronization considerations for the SNSU Facilities Management Office (FMO) Management System.

The API must support:

* Laravel Web Application
* Flutter Mobile Application
* Offline synchronization
* Future third-party integrations

This document should be reviewed before creating API routes, controllers, resources, or services.

---

# API Design Principle

The system must follow an **API-first architecture**.

All major business operations should be available through REST APIs.

The Blade web interface may use normal web routes, but the core logic must still be reusable by API controllers through shared services.

---

# API Base URL

Development:

```text
http://localhost/api/v1
```

Production:

```text
https://your-domain.com/api/v1
```

---

# API Versioning

All API routes must be versioned.

Initial version:

```text
/api/v1
```

Future breaking changes should use:

```text
/api/v2
```

---

# Authentication

Use Laravel Sanctum for API authentication.

## Login

```http
POST /api/v1/auth/login
```

Request:

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "plain-text-token",
    "user": {
      "id": 1,
      "uuid": "user-uuid",
      "name": "Juan Dela Cruz",
      "email": "user@example.com",
      "roles": ["FMO Staff"],
      "permissions": []
    }
  }
}
```

---

## Logout

```http
POST /api/v1/auth/logout
```

Headers:

```http
Authorization: Bearer token
```

---

## Authenticated User

```http
GET /api/v1/auth/me
```

---

# Standard Response Format

All API responses must follow a consistent structure.

## Success Response

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {}
}
```

## Error Response

```json
{
  "success": false,
  "message": "An error occurred.",
  "errors": {}
}
```

## Paginated Response

```json
{
  "success": true,
  "message": "Records retrieved successfully.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

# HTTP Status Codes

Use proper HTTP status codes.

```text
200 OK
201 Created
204 No Content
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
422 Validation Error
500 Server Error
```

---

# Authorization

Every protected endpoint must require Sanctum authentication.

Use policies and permissions for sensitive actions.

Examples:

```text
approve_work_orders
assign_work_orders
manage_inventory
manage_assets
manage_users
view_reports
```

---

# API Route Groups

Recommended structure:

```php
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(...);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('work-orders', WorkOrderController::class);
    });
});
```

---

# USER ENDPOINTS

## List Users

```http
GET /api/v1/users
```

Query parameters:

```text
search
role
status
page
per_page
```

---

## Create User

```http
POST /api/v1/users
```

---

## Show User

```http
GET /api/v1/users/{id}
```

---

## Update User

```http
PUT /api/v1/users/{id}
```

---

## Deactivate User

```http
PATCH /api/v1/users/{id}/deactivate
```

---

# ROLE AND PERMISSION ENDPOINTS

## List Roles

```http
GET /api/v1/roles
```

## List Permissions

```http
GET /api/v1/permissions
```

## Assign Role

```http
POST /api/v1/users/{id}/roles
```

Request:

```json
{
  "roles": ["FMO Staff"]
}
```

---

# STAFF ENDPOINTS

## List FMO Staff

```http
GET /api/v1/staff
```

Query parameters:

```text
search
skill
availability
status
```

---

## Show Staff Profile

```http
GET /api/v1/staff/{id}
```

---

## Update Staff Availability

```http
PATCH /api/v1/staff/{id}/availability
```

Request:

```json
{
  "availability_status": "available"
}
```

---

## Staff Skills

```http
GET /api/v1/staff/{id}/skills
POST /api/v1/staff/{id}/skills
DELETE /api/v1/staff/{id}/skills/{skillId}
```

---

# SKILLS ENDPOINTS

## List Skills

```http
GET /api/v1/skills
```

## Create Skill

```http
POST /api/v1/skills
```

## Update Skill

```http
PUT /api/v1/skills/{id}
```

## Delete Skill

```http
DELETE /api/v1/skills/{id}
```

---

# LOCATION ENDPOINTS

## Buildings

```http
GET /api/v1/buildings
POST /api/v1/buildings
GET /api/v1/buildings/{id}
PUT /api/v1/buildings/{id}
DELETE /api/v1/buildings/{id}
```

## Floors

```http
GET /api/v1/floors
POST /api/v1/floors
```

## Rooms

```http
GET /api/v1/rooms
POST /api/v1/rooms
```

---

# WORK ORDER ENDPOINTS

## List Work Orders

```http
GET /api/v1/work-orders
```

Query parameters:

```text
search
status
approval_status
priority
category
building
assigned_staff
requestor
date_from
date_to
page
per_page
```

---

## Create Work Order

```http
POST /api/v1/work-orders
```

Request:

```json
{
  "title": "Broken door lock",
  "description": "The door lock in Room 203 is damaged.",
  "building_id": 1,
  "room_id": 5,
  "category_id": 2,
  "priority_id": 3,
  "preferred_staff_id": 8
}
```

---

## Show Work Order

```http
GET /api/v1/work-orders/{id}
```

---

## Update Work Order

```http
PUT /api/v1/work-orders/{id}
```

---

## Cancel Work Order

```http
PATCH /api/v1/work-orders/{id}/cancel
```

---

# WORK ORDER ATTACHMENTS

## Upload Attachment

```http
POST /api/v1/work-orders/{id}/attachments
```

Multipart form-data:

```text
file
caption
```

---

## Delete Attachment

```http
DELETE /api/v1/work-orders/{id}/attachments/{attachmentId}
```

---

# WORK ORDER APPROVAL

## Approve Work Order

```http
POST /api/v1/work-orders/{id}/approve
```

Request:

```json
{
  "remarks": "Approved for repair."
}
```

---

## Reject Work Order

```http
POST /api/v1/work-orders/{id}/reject
```

Request:

```json
{
  "remarks": "Duplicate request."
}
```

---

# WORK ORDER ASSIGNMENT

## Assign Staff

```http
POST /api/v1/work-orders/{id}/assign
```

Request:

```json
{
  "assigned_staff_id": 5,
  "assignment_type": "individual",
  "remarks": "Assigned based on carpentry skill."
}
```

---

## Assign Team

```http
POST /api/v1/work-orders/{id}/assign-team
```

Request:

```json
{
  "staff_ids": [5, 6, 7],
  "remarks": "Assigned as repair team."
}
```

---

## Reassign Staff

```http
POST /api/v1/work-orders/{id}/reassign
```

---

# WORK ORDER PROGRESS

## Add Progress Update

```http
POST /api/v1/work-orders/{id}/updates
```

Request:

```json
{
  "status": "in_progress",
  "notes": "Initial inspection completed.",
  "estimated_remaining_days": 2
}
```

---

## List Progress Updates

```http
GET /api/v1/work-orders/{id}/updates
```

---

## Upload Progress Photos

```http
POST /api/v1/work-orders/{id}/updates/{updateId}/photos
```

Multipart form-data:

```text
photos[]
caption
```

---

# WORK ORDER MATERIAL USAGE

## Record Materials Used

```http
POST /api/v1/work-orders/{id}/materials
```

Request:

```json
{
  "materials": [
    {
      "inventory_item_id": 1,
      "quantity": 10
    },
    {
      "inventory_item_id": 2,
      "quantity": 1
    }
  ]
}
```

---

# WORK ORDER FOLLOW-UP

## Add Follow-up

```http
POST /api/v1/work-orders/{id}/followups
```

Request:

```json
{
  "message": "May I follow up on this request?"
}
```

---

## List Follow-ups

```http
GET /api/v1/work-orders/{id}/followups
```

---

# WORK ORDER EVALUATION

## Submit Evaluation

```http
POST /api/v1/work-orders/{id}/evaluation
```

Request:

```json
{
  "quality_rating": 5,
  "timeliness_rating": 4,
  "professionalism_rating": 5,
  "satisfaction_rating": 5,
  "comments": "Work was completed properly."
}
```

---

# INVENTORY ENDPOINTS

## List Inventory Items

```http
GET /api/v1/inventory/items
```

Query parameters:

```text
search
category
low_stock
status
```

---

## Create Inventory Item

```http
POST /api/v1/inventory/items
```

---

## Show Inventory Item

```http
GET /api/v1/inventory/items/{id}
```

---

## Update Inventory Item

```http
PUT /api/v1/inventory/items/{id}
```

---

## Stock In

```http
POST /api/v1/inventory/items/{id}/stock-in
```

Request:

```json
{
  "quantity": 50,
  "remarks": "New delivery."
}
```

---

## Stock Adjustment

```http
POST /api/v1/inventory/items/{id}/adjust
```

---

## Stock Movements

```http
GET /api/v1/inventory/items/{id}/movements
```

---

# TOOL ASSIGNMENT ENDPOINTS

## Assign Tool

```http
POST /api/v1/tools/{id}/assign
```

Request:

```json
{
  "assigned_staff_id": 5,
  "condition_before": "Good condition"
}
```

---

## Return Tool

```http
POST /api/v1/tools/{id}/return
```

Request:

```json
{
  "condition_after": "Good condition"
}
```

---

# ASSET ENDPOINTS

## List Assets

```http
GET /api/v1/assets
```

Query parameters:

```text
search
category
building
room
status
maintenance_due
```

---

## Create Asset

```http
POST /api/v1/assets
```

---

## Show Asset

```http
GET /api/v1/assets/{id}
```

---

## Update Asset

```http
PUT /api/v1/assets/{id}
```

---

## Delete Asset

```http
DELETE /api/v1/assets/{id}
```

---

# ASSET MAINTENANCE ENDPOINTS

## Add Maintenance Log

```http
POST /api/v1/assets/{id}/maintenance-logs
```

Request:

```json
{
  "maintenance_type_id": 1,
  "work_order_id": 10,
  "maintenance_date": "2026-06-17",
  "findings": "Filter was dirty.",
  "action_taken": "Cleaned filter and tested unit.",
  "remarks": "Working normally."
}
```

---

## List Maintenance Logs

```http
GET /api/v1/assets/{id}/maintenance-logs
```

---

# PREVENTIVE MAINTENANCE ENDPOINTS

## List Preventive Maintenance Schedules

```http
GET /api/v1/preventive-maintenance
```

---

## Create Schedule

```http
POST /api/v1/preventive-maintenance
```

---

## Update Schedule

```http
PUT /api/v1/preventive-maintenance/{id}
```

---

## Generate Due Work Orders

```http
POST /api/v1/preventive-maintenance/generate
```

---

# NOTIFICATION ENDPOINTS

## List Notifications

```http
GET /api/v1/notifications
```

---

## Mark as Read

```http
PATCH /api/v1/notifications/{id}/read
```

---

## Mark All as Read

```http
PATCH /api/v1/notifications/read-all
```

---

# REPORT ENDPOINTS

## Dashboard Summary

```http
GET /api/v1/reports/dashboard
```

---

## Work Order Report

```http
GET /api/v1/reports/work-orders
```

Query parameters:

```text
date_from
date_to
status
category
assigned_staff
```

---

## Inventory Report

```http
GET /api/v1/reports/inventory
```

---

## Asset Report

```http
GET /api/v1/reports/assets
```

---

## Maintenance Report

```http
GET /api/v1/reports/maintenance
```

---

## Evaluation Report

```http
GET /api/v1/reports/evaluations
```

---

# MOBILE API ENDPOINTS

These endpoints are specifically designed for Flutter mobile use.

## Mobile Bootstrap

```http
GET /api/v1/mobile/bootstrap
```

Returns:

* Authenticated user
* Assigned roles
* Permissions
* Skills
* Work order statuses
* Categories
* Priorities
* App configuration

---

## Assigned Work Orders

```http
GET /api/v1/mobile/work-orders/assigned
```

Returns only work orders assigned to the authenticated FMO staff.

---

## Mobile Work Order Details

```http
GET /api/v1/mobile/work-orders/{uuid}
```

Use UUID for mobile compatibility.

---

## Mobile Sync Pull

```http
GET /api/v1/mobile/sync/pull
```

Query parameters:

```text
last_synced_at
```

Returns changes since last sync.

---

## Mobile Sync Push

```http
POST /api/v1/mobile/sync/push
```

Uploads offline-created updates.

Request:

```json
{
  "device_uuid": "device-uuid",
  "changes": [
    {
      "entity": "work_order_update",
      "operation": "create",
      "uuid": "local-uuid",
      "payload": {
        "work_order_uuid": "work-order-uuid",
        "status": "in_progress",
        "notes": "Offline update.",
        "created_at": "2026-06-17T08:00:00"
      }
    }
  ]
}
```

---

## Mobile Sync Status

```http
GET /api/v1/mobile/sync/status
```

---

# CONFLICT RESOLUTION

Conflict rules must be defined per entity.

Basic rule:

* Server data wins for official assignments.
* Mobile data wins for staff progress logs if not duplicated.
* Inventory deductions require validation before acceptance.
* Conflicts must be logged.

---

# API Security Rules

All API requests must:

* Use HTTPS in production
* Require authentication unless public
* Validate all inputs
* Authorize all sensitive actions
* Prevent mass assignment
* Rate limit authentication endpoints
* Log suspicious activity

---

# API Resource Standards

Use Laravel API Resources for all structured responses.

Examples:

* UserResource
* WorkOrderResource
* WorkOrderUpdateResource
* InventoryItemResource
* AssetResource
* NotificationResource

Never expose raw models directly.

---

# Pagination Rules

All list endpoints must support pagination.

Default:

```text
per_page = 15
```

Maximum:

```text
per_page = 100
```

---

# Filtering Rules

Use query parameters for filters.

Example:

```http
GET /api/v1/work-orders?status=assigned&priority=urgent
```

---

# Sorting Rules

Use:

```text
sort
direction
```

Example:

```http
GET /api/v1/work-orders?sort=requested_at&direction=desc
```

---

# Final API Principle

The API must remain consistent, secure, documented, mobile-ready, and scalable.

Every endpoint must be designed with future Flutter offline synchronization in mind.

Do not design any endpoint that only works for the web application.

All critical business operations must be available through shared services and reusable API endpoints.
