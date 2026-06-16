# DATABASE_PLAN.md

# SNSU Facilities Management Office (FMO) Management System

## Master Database Architecture Plan

---

# Purpose

This document defines the logical database design for the SNSU Facilities Management Office (FMO) Management System.

It serves as the single source of truth for database architecture and should be consulted before creating or modifying migrations.

The database must remain:

* Normalized
* Scalable
* API-friendly
* Offline-sync ready
* Extensible

---

# Database Design Principles

* Follow Third Normal Form (3NF)
* Use foreign keys for relationships
* Use soft deletes where appropriate
* Prefer lookup tables over hardcoded values
* Preserve historical records
* Support auditability
* Support future multi-campus expansion

---

# Core Modules

1. User Management
2. Staff Management
3. Work Order Management
4. Approval Workflow
5. Assignment Management
6. Daily Progress Updates
7. Inventory Management
8. Asset Management
9. Maintenance Management
10. Evaluation System
11. Notification System
12. Audit Logging
13. Mobile Synchronization

---

# USER MANAGEMENT

## users

Stores all authenticated users.

Fields:

* id
* uuid
* employee_no
* student_no
* first_name
* middle_name
* last_name
* suffix
* email
* username
* password
* mobile_number
* is_active
* last_login_at
* created_at
* updated_at
* deleted_at

Relationships:

* hasOne staff_profile
* hasMany work_orders
* hasMany approvals

---

## roles

Managed by Spatie Permission.

---

## permissions

Managed by Spatie Permission.

---

## model_has_roles

Spatie table.

---

## model_has_permissions

Spatie table.

---

# STAFF MANAGEMENT

## staff_profiles

Stores FMO personnel information.

Fields:

* id
* user_id
* employee_code
* position
* designation
* employment_status
* availability_status
* remarks

Relationships:

* belongsTo user
* hasMany staff_skills
* hasMany assignments

---

## skills

Master list of skills.

Examples:

* Carpentry
* Plumbing
* Electrical
* Masonry
* Painting
* Air Conditioning
* Welding
* Civil Works
* General Maintenance

---

## staff_skill

Pivot table.

Fields:

* staff_profile_id
* skill_id

Many-to-many relationship.

---

# LOCATION MANAGEMENT

## buildings

Stores campus buildings.

Fields:

* id
* code
* name
* description

---

## floors

Fields:

* id
* building_id
* floor_name

---

## rooms

Fields:

* id
* floor_id
* room_name
* room_code

---

## asset_locations

Represents the exact location of assets.

Fields:

* id
* building_id
* floor_id
* room_id
* description

Example:

North Wall

South Corridor

Office Corner

---

# WORK ORDER MANAGEMENT

## work_orders

Main work order table.

Fields:

* id
* uuid
* work_order_number
* requestor_id
* building_id
* room_id
* category_id
* priority_id
* preferred_staff_id
* assigned_staff_id
* status_id
* approval_status_id
* title
* description
* requested_at
* target_completion_date
* completed_at

Relationships:

* belongsTo requestor
* belongsTo preferred_staff
* belongsTo assigned_staff
* hasMany updates
* hasMany attachments
* hasMany materials
* hasMany followups
* hasMany evaluations

---

## work_order_categories

Examples:

* Carpentry
* Electrical
* Plumbing
* Painting
* Air Conditioning
* Civil Works
* Others

---

## priorities

Values:

* Low
* Normal
* High
* Urgent

---

## work_order_statuses

Values:

* Draft
* Submitted
* For Approval
* Approved
* Assigned
* In Progress
* On Hold
* Pending Materials
* Completed
* Evaluated
* Closed
* Cancelled

---

## approval_statuses

Values:

* Pending
* Approved
* Rejected

---

# APPROVAL MANAGEMENT

## work_order_approvals

Fields:

* id
* work_order_id
* approver_id
* action
* remarks
* approved_at

Every approval action should be recorded.

No approval history should be deleted.

---

# ASSIGNMENT MANAGEMENT

## work_order_assignments

Stores assignment history.

Fields:

* id
* work_order_id
* assigned_staff_id
* assigned_by
* assignment_type
* remarks
* assigned_at
* unassigned_at

Assignment types:

* Individual
* Team
* General Pool

Maintains historical assignment changes.

---

# DAILY PROGRESS

## work_order_updates

Fields:

* id
* uuid
* work_order_id
* staff_id
* status
* notes
* estimated_remaining_days
* created_at

Unlimited progress logs.

Never overwrite previous updates.

---

## work_order_update_photos

Fields:

* id
* work_order_update_id
* image_path
* caption

Multiple photos per update.

---

# FOLLOW-UP

## work_order_followups

Fields:

* id
* work_order_id
* user_id
* message
* created_at

Requestors may submit follow-up messages.

---

# EVALUATION

## work_order_evaluations

Fields:

* id
* work_order_id
* evaluator_id
* quality_rating
* timeliness_rating
* professionalism_rating
* satisfaction_rating
* comments
* created_at

Only one evaluation per completed work order by the requestor.

---

# INVENTORY MANAGEMENT

## inventory_categories

Examples:

* Consumables
* Tools
* Equipment

---

## inventory_items

Fields:

* id
* item_code
* category_id
* name
* brand
* unit
* minimum_stock
* current_stock
* remarks

---

## stock_movements

Tracks all stock changes.

Fields:

* id
* inventory_item_id
* movement_type
* quantity
* reference_type
* reference_id
* performed_by
* created_at

Movement types:

* Stock In
* Stock Out
* Adjustment
* Work Order Usage

Never edit historical movements.

---

# TOOL ASSIGNMENT

## tool_assignments

Fields:

* id
* inventory_item_id
* assigned_staff_id
* assigned_by
* assigned_at
* returned_at
* condition_before
* condition_after

Maintains borrowing history.

---

# CAMPUS ASSET MANAGEMENT

## asset_categories

Examples:

* Air Conditioner
* Fire Extinguisher
* Smoke Detector
* Fire Alarm
* Emergency Light
* Wall Fan
* Orbit Fan
* Water Dispenser
* Television

---

## assets

Fields:

* id
* uuid
* asset_code
* category_id
* location_id
* brand
* model
* serial_number
* purchase_date
* warranty_until
* status
* remarks

Status values:

* Active
* Under Maintenance
* Defective
* Retired
* Lost

---

## asset_photos

Fields:

* id
* asset_id
* image_path
* caption

Supports multiple images per asset.

---

# MAINTENANCE MANAGEMENT

## maintenance_types

Examples:

* Cleaning
* Inspection
* Repair
* Calibration
* Testing
* Replacement

---

## asset_maintenance_logs

Fields:

* id
* asset_id
* maintenance_type_id
* work_order_id
* performed_by
* maintenance_date
* findings
* action_taken
* remarks

Complete maintenance history.

---

## preventive_maintenance_schedules

Fields:

* id
* asset_id
* frequency
* next_schedule
* last_completed

Used by the scheduler to generate future maintenance work orders.

---

# NOTIFICATIONS

## notifications

Fields:

* id
* user_id
* title
* message
* type
* is_read
* created_at

Supports web and mobile notifications.

---

# AUDIT LOGGING

## activity_logs

Fields:

* id
* user_id
* module
* action
* old_values
* new_values
* ip_address
* created_at

Every critical operation should create an audit record.

---

# MOBILE SYNCHRONIZATION

## mobile_devices

Stores registered mobile devices.

Fields:

* id
* user_id
* device_uuid
* device_name
* platform
* last_sync_at

---

## sync_logs

Stores synchronization history.

Fields:

* id
* device_id
* sync_started_at
* sync_completed_at
* records_uploaded
* records_downloaded
* status

---

## offline_queue

Stores pending offline changes awaiting synchronization.

Fields:

* id
* uuid
* device_id
* entity_type
* entity_uuid
* operation
* payload
* status
* created_at

Status:

* Pending
* Syncing
* Synced
* Failed
* Conflict

---

# FUTURE MODULES

Reserve the architecture for future additions:

* Vehicle Management
* Fleet Maintenance
* Procurement Requests
* Project Monitoring
* Utility Monitoring
* Building Inspections
* QR Code Asset Tracking
* Barcode Inventory
* GIS Mapping
* AI-Assisted Classification
* Predictive Maintenance

These modules should integrate without requiring major redesign of the existing schema.

---

# Final Database Principle

Every table, relationship, migration, and future enhancement must preserve:

* Data Integrity
* Historical Accuracy
* Scalability
* Offline Synchronization Compatibility
* API Consistency
* Enterprise Maintainability

The database should be designed for at least the next 10 years of expansion without requiring a complete restructuring.
