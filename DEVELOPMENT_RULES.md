# DEVELOPMENT_RULES.md

# SNSU Facilities Management Office (FMO) Management System

## Development Standards and Coding Guidelines

---

# Purpose

This document defines the software engineering standards, architectural principles, coding conventions, and development workflow for the SNSU Facilities Management Office (FMO) Management System.

All contributors, AI coding assistants, and future developers must follow these rules to ensure the project remains maintainable, scalable, secure, and production-ready.

---

# General Principles

The project must be developed as an **Enterprise-Level Application**.

Priority order:

1. Maintainability
2. Scalability
3. Security
4. Readability
5. Performance
6. Development Speed

Quick fixes and shortcuts that compromise architecture are prohibited.

---

# Development Methodology

Development shall follow a **phase-based modular approach**.

Each module must be:

* Independent
* Reusable
* Extensible
* Loosely Coupled
* Easily Testable

No module should tightly depend on another module unless absolutely necessary.

---

# API-First Architecture

All business logic shall be accessible through REST APIs.

The following clients must use the same API:

* Laravel Web Application
* Flutter Mobile Application
* Future Third-party Integrations

Never implement logic exclusively for Blade pages.

Controllers should call Services, which encapsulate business rules used by both web and API layers.

---

# Layered Architecture

The preferred architecture is:

```text
Routes

↓

Controllers

↓

Form Requests

↓

Services

↓

Repositories

↓

Models

↓

Database
```

Controllers should never communicate directly with the database except in trivial read-only cases.

---

# Controller Rules

Controllers should only:

* Receive requests
* Validate requests
* Call services
* Return responses

Controllers must not:

* Contain business logic
* Perform complex calculations
* Build reports
* Manipulate inventory
* Handle assignment algorithms

Controllers should remain thin.

---

# Service Layer Rules

Business logic belongs in Services.

Examples:

* WorkOrderService
* AssignmentService
* InventoryService
* MaintenanceService
* NotificationService
* EvaluationService

Services may call multiple repositories and coordinate transactions.

---

# Repository Pattern

Repositories handle database operations.

Examples:

* UserRepository
* WorkOrderRepository
* InventoryRepository
* AssetRepository

Repositories should not contain business rules.

Repositories should only encapsulate data access.

---

# Validation

All validation must use Laravel Form Requests.

Validation should never be embedded directly inside controllers.

Example:

CreateWorkOrderRequest

UpdateInventoryRequest

AssignStaffRequest

---

# Authorization

Use Policies and Gates.

Never rely solely on frontend restrictions.

Every critical action must verify authorization on the server.

Examples:

* approve work order
* assign staff
* deduct inventory
* edit maintenance logs
* delete assets

---

# Roles and Permissions

Use Spatie Laravel Permission.

Never hardcode role names inside controllers.

Always use permissions instead of role comparisons whenever possible.

Example:

Bad

if ($user->role == "Super Admin")

Good

$user->can('approve_work_orders')

---

# Database Standards

Use foreign keys.

Use indexes.

Normalize tables.

Avoid duplicated information.

Store references instead of repeated text values where appropriate.

Never denormalize prematurely.

---

# Soft Deletes

Use soft deletes for:

* Work Orders
* Assets
* Inventory Items
* Categories
* Staff Profiles

Avoid permanent deletion unless explicitly required.

---

# Transactions

Critical operations must use database transactions.

Examples:

* Approving work orders
* Deducting inventory
* Assigning materials
* Completing work orders
* Creating maintenance records

Use:

DB::transaction()

to ensure data integrity.

---

# UUID Strategy

Prepare the project for mobile synchronization.

Use UUIDs where appropriate for entities exchanged with the mobile application.

Examples:

* Work Orders
* Progress Logs
* Assets
* Inventory Transactions

This simplifies offline synchronization.

---

# Audit Logging

Critical actions must be logged.

Examples:

* User Login
* Approval
* Rejection
* Assignment
* Inventory Deduction
* Asset Update
* Maintenance Completion

Logs should include:

* User
* Timestamp
* Action
* Previous Values
* New Values
* IP Address (if available)

---

# Event-Driven Development

Prefer Laravel Events and Listeners for secondary actions.

Examples:

WorkOrderApproved

↓

Send Notification

↓

Update Dashboard

↓

Write Audit Log

↓

Create Mobile Sync Record

Avoid embedding multiple side effects inside controllers.

---

# Queue Usage

Long-running tasks should be queued.

Examples:

* Email Sending
* Notification Broadcasting
* Report Generation
* Bulk Imports
* Large Exports
* Image Processing

Never block user requests unnecessarily.

---

# Scheduler Usage

Use Laravel Scheduler for automated tasks.

Examples:

* Preventive Maintenance Generation
* Daily Reports
* Inventory Alerts
* Reminder Notifications
* Cleanup Tasks

---

# File Upload Standards

Store uploaded files using Laravel Storage.

Do not store uploaded files directly in public folders.

Supported uploads:

* Images
* PDFs
* Work Attachments
* Maintenance Documents

Use organized directory structures.

Example:

work-orders/

assets/

maintenance/

evaluations/

---

# Image Standards

Store original images.

Generate thumbnails if necessary.

Do not overwrite original files.

Record metadata where useful.

---

# Inventory Rules

Inventory must never become negative.

Every stock movement must create a transaction record.

Stock adjustments require authorization.

All deductions should be traceable to a work order or adjustment record.

---

# Asset Rules

Assets are never consumed.

Assets may be:

* Active
* Under Maintenance
* Defective
* Retired
* Lost

Maintain complete lifecycle history.

---

# Maintenance Rules

Maintenance records are immutable historical data.

Corrections should create new records instead of editing history whenever possible.

---

# Work Order Rules

Every work order should maintain complete history.

Status transitions should be logged.

Recommended statuses:

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

Never delete completed work orders.

---

# Preferred Staff Rule

Preferred staff selected by the requestor is only a recommendation.

Final assignment authority belongs to:

* FMO Head
* Campus Director
* Director for Instruction

The system must allow reassignment at approval.

---

# Mobile Compatibility

Every backend feature must consider offline synchronization.

Never assume continuous internet connectivity.

All API responses should be deterministic and consistent.

Avoid returning unnecessarily large payloads.

Support incremental synchronization.

---

# API Standards

Use RESTful conventions.

Examples:

GET

POST

PUT

PATCH

DELETE

Return JSON consistently.

Use proper HTTP status codes.

Paginate list endpoints.

Filter and sort through query parameters.

Version APIs when breaking changes occur.

Example:

/api/v1/

---

# Security Standards

Always:

* Validate input
* Sanitize output
* Protect against mass assignment
* Use CSRF protection where applicable
* Escape rendered output
* Authorize sensitive actions

Never trust client input.

---

# Performance Standards

Use eager loading.

Avoid N+1 queries.

Paginate large datasets.

Cache frequently accessed data.

Optimize database indexes.

Do not prematurely optimize at the expense of clarity.

---

# Coding Standards

Follow PSR standards.

Use descriptive class names.

Use descriptive variable names.

Avoid abbreviations unless widely understood.

Example:

Good

$assignedStaff

Bad

$as

---

# Naming Conventions

Classes

PascalCase

Methods

camelCase

Variables

camelCase

Database Tables

snake_case plural

Columns

snake_case

Routes

kebab-case

---

# Comments

Write self-explanatory code.

Comment only when explaining complex business rules.

Avoid redundant comments.

Bad:

// increment counter

$count++;

Good:

// Skip automatic assignment when preferred staff is unavailable

---

# Git Workflow

Commit frequently.

Use meaningful commit messages.

Examples:

feat: add work order approval workflow

fix: resolve inventory deduction bug

refactor: extract assignment service

docs: update project status

---

# Phase Workflow

Before every new phase:

1. Read PROJECT_CONTEXT.md
2. Read PROJECT_STATUS.md
3. Review architecture
4. Review migrations
5. Review dependencies
6. Review database
7. Review services
8. Review repositories
9. Scan for duplicated logic
10. Scan for security issues

Only then recommend implementation.

---

# Codex Working Rules

Before generating code:

* Analyze existing codebase
* Explain the proposed implementation
* Identify risks
* Recommend architecture
* Wait for confirmation

Do not modify multiple unrelated modules in one task unless requested.

After completing work:

* Summarize changes
* List affected files
* Recommend the next phase
* Update PROJECT_STATUS.md recommendation
* Wait for user confirmation

---

# Final Principle

This project is intended to become the official digital Facilities Management Platform of SNSU.

Every design decision should favor long-term maintainability, extensibility, and reliability.

When multiple implementation approaches exist, choose the one that best supports future growth, offline mobile synchronization, modular expansion, and clean architecture.
