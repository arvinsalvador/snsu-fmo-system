# PROJECT_STATUS.md

# SNSU Facilities Management Office (FMO) Management System

## Current Project Status

**Project Name:**

SNSU Facilities Management Office (FMO) Management System

**Architecture:**

Laravel API + Laravel Web + Flutter Offline Mobile App

**Development Methodology:**

Phase-Based Modular Development

**Current Status:**

Phase 6.5 Web Dashboard MVP Completed

---

# Current Phase

## Phase 6.5 - Web Dashboard MVP

**Status:**

Completed

---

# Project Repository

GitHub Repository

https://github.com/arvinsalvador/snsu-fmo-system

---

# Development Environment

## Backend

* Laravel 13+
* PHP 8.4+
* MySQL

## API

* Laravel Sanctum

## Authorization

* Spatie Laravel Permission

## Queue

* Redis

## Web

* Blade
* Tailwind CSS
* Alpine.js

## Mobile

* Flutter
* SQLite (Drift)

## Development

* Windows 11
* WSL2
* Docker Desktop
* Laravel Sail

---

# Completed Tasks

## Project Planning

* Project concept finalized
* System scope finalized
* User roles identified
* Technology stack selected
* API-first architecture selected
* Offline mobile architecture selected
* Modular development strategy established

---

## System Design

* Work Order concept finalized
* Approval workflow finalized
* Preferred staff selection finalized
* Staff skill assignment finalized
* Daily progress concept finalized
* Inventory concept finalized
* Asset management concept finalized
* Maintenance history concept finalized
* Preventive maintenance concept finalized
* Evaluation module concept finalized

---

## Documentation

Completed

* PROJECT_CONTEXT.md
* PROJECT_STATUS.md
* DEVELOPMENT_RULES.md
* DATABASE_PLAN.md
* API_SPECIFICATION.md
* MOBILE_SYNC.md
* MODULES.md

Pending

* Phase-specific documentation updates after each future module

---

# Phase 1 - Project Foundation

Completed

* Laravel 13 application verified
* Laravel Sail environment verified through test execution
* GitHub origin configured
* Environment example normalized for Sail, MySQL, Redis, and Mailpit
* Laravel Breeze installed with Blade, Tailwind CSS, and Alpine.js
* Web authentication routes, dashboard, navigation, and profile scaffolding installed
* Laravel Sanctum installed and configured
* Spatie Laravel Permission installed and configured
* API route file added with `/api/v1` structure
* API authentication endpoints added:
  * `POST /api/v1/auth/login`
  * `POST /api/v1/auth/logout`
  * `GET /api/v1/auth/me`
* User foundation columns added:
  * `uuid`
  * `is_active`
  * `last_login_at`
* User model configured for Sanctum tokens and Spatie roles
* Default roles seeded:
  * Super Admin
  * FMO Head
  * Campus Director
  * Director for Instruction
  * FMO Staff
  * Faculty
  * Admin/Staff
  * Student
* Foundational permissions seeded
* Super Admin receives all foundational permissions
* Basic API auth and roles/permissions tests added
* Full test suite verified

---

# Phase 2 - User Management, Staff Profiles, and Skills Foundation

Completed

* User profile database fields added according to `DATABASE_PLAN.md`
* User soft deletes enabled for historical preservation
* Staff profile table added
* Skills table added
* Staff-skill pivot table added
* User, StaffProfile, and Skill relationships added
* API user management endpoints added under `/api/v1/users`
* API staff profile endpoints added under `/api/v1/staff`
* API skill endpoints added under `/api/v1/skills`
* Staff skill sync endpoint added:
  * `PUT /api/v1/staff/{staffProfile}/skills`
* Form Requests added for user, staff profile, skill, and staff-skill validation
* API Resources added for user, staff profile, and skill responses
* Policies added and registered for users, staff profiles, and skills
* Service classes added for user management, staff profile management, and skill management
* FMO skill seeder added
* Role permissions expanded for FMO Head, Campus Director, Director for Instruction, FMO Staff, and requestor roles
* Feature tests added for user creation, authorization denial, staff profile creation, skill assignment, and skill seeding
* Full test suite verified

---

# Phase 2.5 - Master Data Foundation

Completed

* Master data tables added:
  * Buildings
  * Floors
  * Rooms
  * Departments/Offices
  * Work Order Categories
  * Priorities
  * Work Order Statuses
  * Asset Categories
  * Maintenance Types
  * Inventory Categories
* UUID support added to master data records for future mobile reference use
* Soft deletes added to master data records
* Models and relationships added for building, floor, and room hierarchy
* Idempotent master data seeder added
* `manage_master_data` and `manage_work_order_settings` permissions added
* Protected REST API endpoints added under `/api/v1`
* Form Requests added for master data validation
* API Resources added for master data responses
* Master data policy and service support added
* Feature tests added for seeding, CRUD, hierarchy creation, search, authorization, and uniqueness
* `migrate --seed`, repeated master data seeding, full tests, and Pint verified

---

# Phase 3 - Work Order Module

Completed

* Work order core table added
* Work order attachment metadata table added
* Work order model and attachment model added
* Work order relationships added for:
  * Requestor
  * Department/Office
  * Building
  * Floor
  * Room
  * Category
  * Priority
  * Status
  * Preferred Staff
  * Attachments
* Work order repository added for scoped querying and visibility rules
* Work order service added for transactions, numbering, default status, updates, and deletion
* Work order Form Requests added
* Work order API Resources added
* Work order policy added
* Work order permissions added:
  * `manage_work_orders`
  * `view_work_orders`
  * `create_work_orders`
  * `update_work_orders`
  * `delete_work_orders`
* Protected work order REST API endpoints added under `/api/v1/work-orders`
* Requestors can only see their own requests unless operationally authorized
* FMO Head, Campus Director, Director for Instruction, and Super Admin can see operational requests through permissions
* Preferred staff is stored only as a recommendation
* Feature tests added for creation, attachment metadata, scoped visibility, operational visibility, status update protection, and deletion
* `migrate --seed`, full tests, Pint, and route checks verified

---

# Phase 4 - Approval Workflow

Completed

* Work order approval history table added
* Work order approval status tracking added for API filtering and mobile sync compatibility
* Work order model relationships added for approval history
* User model relationship added for approval actions
* Approval API Resources added
* Approval and rejection Form Requests added
* Work order service expanded with transactional approve and reject operations
* Approval workflow records approver, action, remarks, and timestamp
* Approval workflow moves work orders to `Approved` status
* Rejection workflow records `rejected` history and moves work orders to `Cancelled` using the existing status structure
* Approval history endpoint added:
  * `GET /api/v1/work-orders/{workOrder}/approvals`
* Approval action endpoints added:
  * `POST /api/v1/work-orders/{workOrder}/approve`
  * `POST /api/v1/work-orders/{workOrder}/reject`
* Approval permissions added:
  * `approve_work_orders`
  * `reject_work_orders`
  * `view_work_order_approvals`
* Authorized approval roles configured:
  * Super Admin
  * FMO Head
  * Campus Director
  * Director for Instruction
* Requestors without explicit approval permission cannot approve their own requests
* Completed approval workflows cannot be approved or rejected again
* Approval history has no delete endpoint and is preserved as an audit trail
* Feature tests added for approval, rejection, authorization denial, repeat-action protection, and approval history retrieval
* `migrate --seed`, full tests, Pint, and route checks verified

---

# Phase 5A - Assignment Foundation

Completed

* Immutable work order assignment history table added
* UUID support added to assignment records
* Individual assignment supported
* Team assignment supported with one history record per staff member
* Reassignment closes active records with `unassigned_at` before creating replacements
* Assignment service added with transactional row locking and status transitions
* Assignment Form Requests and API Resource added
* Work order policy expanded for assignment actions
* Assignment permissions added:
  * `assign_work_orders`
  * `reassign_work_orders`
  * `view_assignments`
* Authorized assignment roles configured:
  * Super Admin
  * FMO Head
  * Campus Director
  * Director for Instruction
* Protected assignment endpoints added:
  * `POST /api/v1/work-orders/{workOrder}/assign`
  * `POST /api/v1/work-orders/{workOrder}/assign-team`
  * `POST /api/v1/work-orders/{workOrder}/reassign`
  * `GET /api/v1/work-orders/{workOrder}/assignments`
* Active staff and active user accounts are required for assignment
* Rejected and terminal work orders cannot be assigned
* Active assignments cannot be overwritten through initial assignment endpoints
* Preferred staff remains a recommendation and is never assigned automatically
* Feature tests added for individual assignment, team assignment, reassignment, authorization, transition safety, and history
* `migrate --seed`, full tests, Pint, and route checks verified

---

# Phase 5B - Assignment Intelligence

Completed

* Read-only assignment intelligence service added
* Work-order assignment recommendations endpoint added
* Staff workload summary endpoint added
* Available staff lookup endpoint added
* Work order categories matched to active staff skills by normalized name
* `Others` category mapped to `General Maintenance`
* Recommendation responses include staff profile, user name, availability, matched skills, workload counts, preferred-staff flag, and score
* Recommendation scoring includes skill match, preferred staff, availability, and active workload
* Active assignment count uses open assignment records and excludes completed, evaluated, closed, and cancelled work orders
* Pending assignment count defined by `Assigned` work-order status
* In-progress assignment count defined by `In Progress` work-order status
* Available staff lookup filters active staff and users marked `available`
* Availability remains advisory and is not enforced during assignment
* Preferred staff remains advisory and is never assigned automatically
* Staff search, skill, availability, and pagination filters added
* Assignment intelligence permissions added:
  * `view_assignment_recommendations`
  * `view_staff_workload`
* Feature tests added for recommendations, scoring, workload counts, availability filtering, lookup filters, authorization, and no automatic assignment
* Permission seeding, full tests, Pint, and route checks verified

---

# Phase 6 - Daily Progress Updates

Completed

* Immutable work order progress history and photo metadata tables added
* UUID support added to progress updates and photo records
* Updates record status, notes, estimated remaining days, staff, creator, and timestamp
* Safe JPEG, PNG, and WebP upload foundation added with size and count limits
* Transactional service and row locking enforce status transition safety
* First updates may move `Assigned` work orders to `In Progress`
* Progress supports `In Progress`, `On Hold`, `Pending Materials`, and `Completed`
* Completion records `completed_at` and prevents later progress updates
* Assigned FMO staff and authorized operational roles can create updates
* Requestors can view updates for their own work orders
* Update and photo history have no delete endpoints and use restrictive foreign keys
* Permissions added: `view_work_order_updates`, `create_work_order_updates`, `manage_work_order_updates`
* Protected progress and photo endpoints added under `/api/v1/work-orders`
* Feature tests cover transitions, completion, authorization, visibility, and photo metadata

---

# Phase 6.5 - Web Dashboard MVP

Completed

* Breeze/Tailwind application shell replaced with a responsive operational sidebar layout
* Role-aware dashboard navigation and summary metrics added for all eight project roles
* Web work-order dashboard and filtered work-order listing added
* Requestor pages added for request history, creation, and work-order detail
* Approval and assignment queue pages added for authorized operational roles
* Assignment recommendation page displays preferred staff, skill matches, availability, workload, and score
* Assigned-task page added for FMO Staff
* Work-order detail page includes request data, approval actions, assignment history, and progress timeline
* Progress update form delegates status transition rules to the existing progress service
* Web write actions reuse work-order, approval, assignment, recommendation, and progress services
* Policies and record-level scope allow assigned staff to access only their active tasks
* Sail/Vite host configuration normalized for Windows and WSL browser access
* Feature tests cover all roles, navigation visibility, request creation, queues, assigned-task access, and progress submission

---

# Pending Tasks for Current Phase

No remaining Phase 6.5 implementation tasks.

Next recommended phase: Phase 7 - Follow-up and Evaluation.

---

# Planned Development Roadmap

---

## Phase 1

Project Foundation

Status:

🟢 Completed

Includes:

* Laravel Installation
* Docker
* GitHub
* Breeze
* Sanctum
* Spatie Permission

---

## Phase 2

User Management

Status:

🟢 Completed

Includes:

* Users
* Roles
* Permissions
* Staff Profiles
* Staff Skills

---

## Phase 2.5

Master Data Foundation

Status:

🟢 Completed

Includes:

* Buildings
* Floors
* Rooms
* Departments/Offices
* Work Order Categories
* Priorities
* Work Order Statuses
* Asset Categories
* Maintenance Types
* Inventory Categories

---

## Phase 3

Work Order Module

Status:

🟢 Completed

Includes:

* Work Order Creation
* Categories
* Priority
* Attachments
* Preferred Staff
* Tracking

---

## Phase 4

Approval Workflow

Status:

🟢 Completed

Includes:

* FMO Head Approval
* Campus Director Approval
* Director for Instruction Approval
* Approval History
* Rejection Workflow

---

## Phase 5

Staff Assignment

Status:

🟢 Completed

Includes:

* Skill Matching
* Assignment History
* Individual, Team, and Reassignment APIs
* Assignment Engine
* Workload Monitoring
* Availability

---

## Phase 6

Daily Progress

Status:

Completed

Includes:

* Daily Updates
* Photos
* Notes
* Progress Timeline

---

## Phase 7

Follow-up and Evaluation

Status:

⚪ Pending

Includes:

* Follow-up Messages
* Notifications
* Evaluation Forms
* Satisfaction Ratings

---

## Phase 8

Consumable Inventory

Status:

⚪ Pending

Includes:

* Materials
* Stock
* Stock Movement
* Material Usage

---

## Phase 9

Tools and Equipment

Status:

⚪ Pending

Includes:

* Tool Assignment
* Borrowing
* Return History

---

## Phase 10

Campus Asset Inventory

Status:

⚪ Pending

Includes:

* Air Conditioners
* Fire Extinguishers
* Emergency Lights
* Smoke Detectors
* Fire Alarms
* Electric Fans
* Wall Fans
* Water Dispensers
* Other Assets

---

## Phase 11

Maintenance Management

Status:

⚪ Pending

Includes:

* Maintenance History
* Preventive Maintenance
* Maintenance Scheduling
* Auto-generated Work Orders

---

## Phase 12

Reports and Dashboard

Status:

⚪ Pending

Includes:

* Work Order Reports
* Staff Reports
* Inventory Reports
* Asset Reports
* Evaluation Reports

---

## Phase 13

Notification System

Status:

⚪ Pending

Includes:

* Web Notifications
* Mobile Notifications
* Email Notifications

---

## Phase 14

API Stabilization

Status:

⚪ Pending

Includes:

* REST API
* Authentication
* Versioning
* Documentation

---

## Phase 15

Flutter Mobile Application

Status:

⚪ Pending

Includes:

* Login
* Assigned Tasks
* Daily Progress
* Photo Upload
* Materials Used

---

## Phase 16

Offline Synchronization

Status:

⚪ Pending

Includes:

* Local Database
* Offline Queue
* Conflict Resolution
* Auto Sync

---

## Phase 17

Optimization and Security

Status:

⚪ Pending

Includes:

* Performance Optimization
* Security Audit
* API Optimization
* Database Optimization

---

## Phase 18

Testing and Deployment

Status:

⚪ Pending

Includes:

* Unit Testing
* Feature Testing
* Integration Testing
* User Acceptance Testing
* Production Deployment

---

# Current Priority

Proceed to Phase 7 - Follow-up and Evaluation only after user confirmation.

Recommended approach: split Phase 7 into follow-up/notification foundations and evaluation workflows so each authorization and history model remains independently testable.

---

# Codex Instructions

Before implementing any feature:

1. Read PROJECT_CONTEXT.md.
2. Read PROJECT_STATUS.md.
3. Analyze the current codebase.
4. Scan for architectural issues.
5. Scan for duplicate logic.
6. Scan for migration conflicts.
7. Scan for security issues.
8. Recommend the next implementation step.
9. Wait for user confirmation before proceeding.

Never skip phases.

Never implement features from future phases unless explicitly instructed.

Always preserve clean architecture, modularity, scalability, and API-first principles.

Update this PROJECT_STATUS.md file after every completed phase so it accurately reflects the current state of development and the next recommended actions.
