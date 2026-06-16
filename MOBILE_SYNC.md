# MOBILE_SYNC.md

# SNSU Facilities Management Office (FMO) Management System

## Flutter Offline Mobile Synchronization Plan

---

# Purpose

This document defines the offline-first mobile synchronization strategy for the SNSU Facilities Management Office (FMO) Management System.

The mobile app is intended primarily for **FMO Staff** who may work in areas with weak or unavailable internet connection.

The Flutter mobile app must allow staff to:

* View assigned work orders
* Work offline
* Add progress updates
* Capture photos
* Record materials used
* Save updates locally
* Sync once internet is available

---

# Recommended Mobile Stack

## Mobile Framework

Flutter

## Local Database

SQLite using Drift

## API

Laravel REST API

## Authentication

Laravel Sanctum token authentication

## Storage

Local file storage for offline photos

---

# Offline-First Principle

The mobile app must treat the local database as the primary working source while offline.

The server remains the official central source of truth.

The mobile app should:

1. Download assigned work orders.
2. Store them locally.
3. Allow updates offline.
4. Queue unsynced changes.
5. Sync changes once online.
6. Resolve conflicts safely.
7. Never lose local records.

---

# Mobile Users

The first mobile version is intended for:

* FMO Staff

Future mobile access may support:

* FMO Head
* Campus Director
* Director for Instruction
* Requestors

---

# Mobile App Scope

## Version 1 Features

* Login
* Logout
* View assigned work orders
* View work order details
* Start work
* Add daily progress updates
* Capture and attach photos
* Record materials used
* Mark work as in progress
* Mark work as pending materials
* Mark work as completed
* Sync offline updates

---

# Online Login Requirement

The first login must require internet.

After successful login, the app stores:

* Access token
* User profile
* Role
* Permissions
* Last sync timestamp
* Device UUID

Offline access may be allowed only after successful first login.

---

# Local Database Tables

The Flutter SQLite database should include local equivalents of important server data.

## local_users

Stores the currently logged-in user.

Fields:

* id
* uuid
* name
* email
* role
* permissions
* last_synced_at

---

## local_work_orders

Stores assigned work orders.

Fields:

* id
* uuid
* work_order_number
* title
* description
* category
* priority
* building
* room
* status
* approval_status
* assigned_staff_uuid
* requested_at
* target_completion_date
* completed_at
* last_modified_at
* sync_status

---

## local_work_order_updates

Stores progress updates created online or offline.

Fields:

* id
* uuid
* work_order_uuid
* staff_uuid
* status
* notes
* estimated_remaining_days
* created_at
* updated_at
* sync_status

---

## local_work_order_photos

Stores local photo references.

Fields:

* id
* uuid
* update_uuid
* local_path
* remote_path
* caption
* created_at
* sync_status

---

## local_material_usage

Stores materials used per work order.

Fields:

* id
* uuid
* work_order_uuid
* inventory_item_uuid
* item_name
* quantity
* unit
* created_at
* sync_status

---

## local_inventory_items

Stores synced inventory reference data.

Fields:

* id
* uuid
* item_code
* name
* unit
* current_stock
* last_modified_at

---

## local_sync_queue

Stores unsynced local operations.

Fields:

* id
* uuid
* entity_type
* entity_uuid
* operation
* payload
* status
* attempts
* error_message
* created_at
* updated_at

---

# Sync Status Values

Use consistent sync status values:

```text
pending
syncing
synced
failed
conflict
```

---

# Sync Direction

## Pull Sync

Server → Mobile

Used to download:

* Assigned work orders
* Work order updates
* Work order status changes
* Inventory reference data
* Categories
* Priorities
* Skills
* Notifications

---

## Push Sync

Mobile → Server

Used to upload:

* Progress updates
* Photos
* Material usage
* Status changes
* Completion notes

---

# Pull Sync Flow

```text
Mobile app checks last_synced_at
        ↓
Calls Laravel sync pull endpoint
        ↓
Server returns changed records
        ↓
Mobile updates local SQLite database
        ↓
Mobile updates last_synced_at
```

Recommended endpoint:

```http
GET /api/v1/mobile/sync/pull?last_synced_at=2026-06-17T08:00:00
```

---

# Push Sync Flow

```text
FMO Staff creates offline update
        ↓
App saves update locally
        ↓
App adds record to local_sync_queue
        ↓
Internet becomes available
        ↓
App uploads queued changes
        ↓
Server validates data
        ↓
Server saves official record
        ↓
Server returns success response
        ↓
App marks local record as synced
```

Recommended endpoint:

```http
POST /api/v1/mobile/sync/push
```

---

# Push Payload Format

```json
{
  "device_uuid": "device-uuid",
  "last_synced_at": "2026-06-17T08:00:00",
  "changes": [
    {
      "uuid": "local-change-uuid",
      "entity": "work_order_update",
      "operation": "create",
      "entity_uuid": "local-update-uuid",
      "payload": {
        "work_order_uuid": "work-order-uuid",
        "status": "in_progress",
        "notes": "Initial inspection completed.",
        "estimated_remaining_days": 2,
        "created_at": "2026-06-17T09:15:00"
      }
    }
  ]
}
```

---

# Photo Sync Strategy

Photos must be handled carefully because they may be large.

Recommended process:

1. Save photo locally.
2. Compress if needed.
3. Queue upload.
4. Upload photo when online.
5. Server returns remote path.
6. App updates local photo record.

Recommended endpoint:

```http
POST /api/v1/mobile/work-order-updates/{uuid}/photos
```

Use multipart form-data.

---

# Conflict Resolution Rules

Conflicts must be handled safely.

## Server Wins

Server data should win for:

* Official work order assignment
* Approval status
* Final assigned staff
* User permissions
* Inventory official stock level

---

## Mobile Wins

Mobile data may win for:

* Progress notes
* Offline photos
* Staff-created daily updates
* Field observations

---

## Requires Review

Manual review may be required for:

* Duplicate progress logs
* Conflicting completion status
* Inventory deduction with insufficient stock
* Work order already closed on server
* Staff no longer assigned to the work order

---

# Inventory Sync Rule

Inventory deductions must be validated by the server.

The mobile app may record materials used offline, but final deduction happens on the Laravel server.

If stock is insufficient during sync:

* Mark record as conflict
* Notify staff
* Notify FMO Head
* Do not silently deduct stock

---

# Work Order Status Sync Rule

Mobile staff may update:

* In Progress
* On Hold
* Pending Materials
* Completed

But server must validate:

* User is assigned to work order
* Work order is approved
* Work order is not closed
* Status transition is allowed

---

# Completion Rule

When staff marks a work order as completed:

1. App saves completion update.
2. App queues sync.
3. Server verifies required progress data.
4. Server marks work order as completed.
5. Requestor receives evaluation notification.
6. Work order moves to For Evaluation status.

---

# Device Registration

Upon first mobile login, register device:

```http
POST /api/v1/mobile/devices/register
```

Payload:

```json
{
  "device_uuid": "device-uuid",
  "device_name": "Samsung Galaxy A15",
  "platform": "android"
}
```

---

# Sync Logs

The Laravel server should maintain sync logs.

Each sync log should record:

* Device
* User
* Start time
* End time
* Uploaded records
* Downloaded records
* Status
* Errors

---

# Offline Queue Retry Rule

Failed sync attempts should be retried.

Recommended retry policy:

```text
Attempt 1: Immediate
Attempt 2: After 1 minute
Attempt 3: After 5 minutes
Attempt 4: After 15 minutes
Attempt 5: Manual retry
```

After repeated failure, mark as:

```text
failed
```

---

# Data Download Scope

Mobile app should not download all system records.

FMO Staff mobile app should only download:

* Work orders assigned to the user
* Updates related to those work orders
* Required reference data
* Relevant inventory items
* Relevant notifications

This keeps the app fast and secure.

---

# Security Rules

The mobile app must:

* Store token securely
* Never expose sensitive data
* Encrypt local storage when possible
* Require re-authentication when token expires
* Sync only through HTTPS in production
* Validate all server responses

The server must:

* Authenticate every sync request
* Validate device ownership
* Authorize every uploaded operation
* Reject unauthorized changes
* Log suspicious sync behavior

---

# Recommended Sync Endpoints

```http
POST /api/v1/mobile/devices/register

GET /api/v1/mobile/bootstrap

GET /api/v1/mobile/work-orders/assigned

GET /api/v1/mobile/work-orders/{uuid}

GET /api/v1/mobile/sync/pull

POST /api/v1/mobile/sync/push

POST /api/v1/mobile/work-order-updates/{uuid}/photos

GET /api/v1/mobile/sync/status
```

---

# Mobile Bootstrap Data

The mobile bootstrap endpoint should return:

* Authenticated user
* Roles
* Permissions
* Assigned staff profile
* Work order categories
* Priorities
* Work order statuses
* Inventory reference data
* App configuration
* Last sync timestamp

---

# Sync Safety Requirements

The mobile app must never delete local unsynced data automatically.

Before deleting local data:

* Confirm it has synced
* Confirm server acknowledged it
* Confirm it is no longer needed

---

# Offline UX Requirements

The app should clearly show:

* Offline mode
* Pending sync count
* Failed sync count
* Last successful sync time
* Sync button
* Sync progress
* Conflict warnings

---

# Recommended Mobile Screens

## Login Screen

* Email
* Password
* Login button

## Dashboard

* Assigned tasks
* Pending sync count
* Completed today
* Urgent tasks

## Assigned Work Orders

* Pending
* In Progress
* Pending Materials
* Completed

## Work Order Details

* Description
* Location
* Priority
* Photos
* Updates
* Materials used

## Add Progress Update

* Status
* Notes
* Photos
* Estimated remaining days

## Material Usage

* Select material
* Quantity used
* Unit

## Sync Center

* Last sync
* Pending uploads
* Failed uploads
* Manual sync

---

# Recommended Development Order

## Mobile Phase 1

Online-only Flutter prototype.

Includes:

* Login
* Assigned work orders
* Work order details
* Progress updates

---

## Mobile Phase 2

Local SQLite database.

Includes:

* Save assigned work locally
* View work offline
* Save progress offline

---

## Mobile Phase 3

Push synchronization.

Includes:

* Upload progress updates
* Upload photos
* Upload material usage

---

## Mobile Phase 4

Pull synchronization.

Includes:

* Download updated assignments
* Download status changes
* Download reference data

---

## Mobile Phase 5

Conflict handling.

Includes:

* Failed sync screen
* Conflict status
* Manual retry

---

# Server Development Requirements

Laravel must prepare for mobile sync by using:

* UUIDs
* updated_at timestamps
* deleted_at timestamps
* sync logs
* API resources
* authorization policies
* deterministic API responses

---

# Final Mobile Sync Principle

The mobile app must be reliable even when internet access is unstable.

FMO staff should be able to perform their work, record progress, capture photos, and update tasks without worrying about connectivity.

The system must protect data integrity by treating the Laravel backend as the official record while still preserving all offline field updates until they are safely synchronized.
