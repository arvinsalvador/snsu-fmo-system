# MODULES.md

# SNSU Facilities Management Office (FMO) Management System

## Master Module Roadmap

---

# Purpose

This document defines all functional modules of the SNSU Facilities Management Office (FMO) Management System.

Each module should be developed independently while remaining fully integrated with the overall architecture.

No module should violate the project's API-first, clean architecture, and modular design principles.

---

# Module Dependency Diagram

```text
System Foundation
        │
        ▼
Authentication
        │
        ▼
Roles & Permissions
        │
        ▼
User Management
        │
        ▼
Staff Management
        │
        ▼
Location Management
        │
        ▼
Work Order Management
        │
        ▼
Approval Workflow
        │
        ▼
Assignment Engine
        │
        ▼
Daily Progress
        │
        ▼
Inventory
        │
        ▼
Asset Management
        │
        ▼
Maintenance
        │
        ▼
Evaluation
        │
        ▼
Reports
        │
        ▼
Notifications
        │
        ▼
REST API
        │
        ▼
Flutter Mobile
        │
        ▼
Offline Synchronization
```

---

# MODULE 01

## Project Foundation

### Objective

Prepare the development environment and project architecture.

### Includes

* Laravel installation
* Docker / Laravel Sail
* GitHub integration
* Environment configuration
* Redis
* Queue
* Scheduler
* Mail configuration

### Deliverables

* Running Laravel application
* Working Docker environment
* Connected repository
* Verified development environment

---

# MODULE 02

## Authentication

### Objective

Provide secure user authentication.

### Includes

* Login
* Logout
* Password reset
* Profile
* Change password
* Email verification (optional)

### Deliverables

Complete authentication system.

---

# MODULE 03

## Roles and Permissions

### Objective

Implement authorization.

### Roles

* Super Admin
* FMO Head
* Campus Director
* Director for Instruction
* FMO Staff
* Faculty
* Admin/Staff
* Student

### Deliverables

Role-based access control using Spatie Permission.

---

# MODULE 04

## User Management

### Objective

Manage system users.

### Features

* Create user
* Edit user
* Activate/deactivate
* Search
* Filter
* Import
* Export

### Deliverables

Complete user management module.

---

# MODULE 05

## Staff Management

### Objective

Manage FMO personnel.

### Features

* Staff profile
* Position
* Availability
* Contact information
* Assigned tools
* Assigned work

### Deliverables

Complete staff management module.

---

# MODULE 06

## Skills Management

### Objective

Manage staff competencies.

### Features

* Skill master list
* Assign skills
* Remove skills
* Skill search
* Skill recommendation

Examples

* Carpentry
* Plumbing
* Electrical
* Masonry
* Painting
* Air Conditioning
* Welding

### Deliverables

Skill-based recommendation engine foundation.

---

# MODULE 07

## Location Management

### Objective

Manage campus locations.

### Features

* Buildings
* Floors
* Rooms
* Exact asset locations

### Deliverables

Reusable location hierarchy for all modules.

---

# MODULE 08

## Work Order Management

### Objective

Digitalize work order requests.

### Features

* Create request
* Edit request
* Attach files
* Priority
* Category
* Building
* Room
* Search
* Filter

### Requestors

* Faculty
* Admin/Staff
* Student

### Deliverables

Complete work order module.

---

# MODULE 09

## Preferred Staff Selection

### Objective

Allow requestors to recommend an FMO staff member.

### Rules

Selection is optional.

Selection is not binding.

Final approval belongs to:

* FMO Head
* Campus Director
* Director for Instruction

### Deliverables

Preferred staff recommendation workflow.

---

# MODULE 10

## Approval Workflow

### Objective

Manage request approvals.

### Features

* Approve
* Reject
* Return for revision
* Remarks
* Approval history

### Deliverables

Complete approval process with audit trail.

---

# MODULE 11

## Assignment Engine

### Objective

Assign work orders to personnel.

### Assignment Types

* Individual
* Team
* General Pool

### Assignment Factors

* Skill match
* Availability
* Current workload
* Preferred staff
* Manual override

### Deliverables

Smart assignment module.

---

# MODULE 12

## Daily Progress

### Objective

Track day-to-day work progress.

### Features

* Daily notes
* Photos
* Status updates
* Remaining work estimate
* Timeline

### Deliverables

Immutable progress history.

---

# MODULE 13

## Follow-up System

### Objective

Allow requestors to follow up active work orders.

### Features

* Comments
* Notifications
* Timeline history

### Deliverables

Integrated follow-up communication.

---

# MODULE 14

## Evaluation System

### Objective

Measure service quality.

### Evaluation Criteria

* Quality
* Timeliness
* Professionalism
* Satisfaction

### Features

* Rating
* Comments
* Reports

### Deliverables

Performance evaluation module.

---

# MODULE 15

## Consumable Inventory

### Objective

Manage consumable materials.

### Examples

* Nails
* Paint
* Cement
* PVC Pipe
* Wire
* Bulbs

### Features

* Stock in
* Stock out
* Adjustments
* Low stock alerts
* Usage history

### Deliverables

Complete consumable inventory system.

---

# MODULE 16

## Tools and Equipment

### Objective

Manage reusable tools.

### Features

* Assignment
* Borrowing
* Return
* Condition monitoring
* History

### Deliverables

Tool accountability module.

---

# MODULE 17

## Campus Asset Inventory

### Objective

Track installed campus assets.

### Examples

* Air Conditioners
* Fire Extinguishers
* Fire Alarms
* Smoke Detectors
* Emergency Lights
* Wall Fans
* Orbit Fans
* Water Dispensers

### Features

* Asset profile
* Photos
* Location
* Status

### Deliverables

Enterprise asset registry.

---

# MODULE 18

## Maintenance Management

### Objective

Maintain asset service history.

### Features

* Cleaning
* Repair
* Inspection
* Testing
* Replacement

### Deliverables

Complete maintenance log module.

---

# MODULE 19

## Preventive Maintenance

### Objective

Automatically schedule maintenance.

### Features

* Frequencies
* Next schedule
* Auto-generated work orders
* Reminders

### Deliverables

Preventive maintenance engine.

---

# MODULE 20

## Notifications

### Objective

Notify users of important events.

### Channels

* Web
* Mobile
* Email

### Events

* New request
* Approval
* Assignment
* Follow-up
* Completion
* Evaluation

### Deliverables

Central notification service.

---

# MODULE 21

## Reports and Analytics

### Reports

#### Work Orders

* Pending
* Assigned
* Delayed
* Completed

#### Personnel

* Workload
* Productivity
* Completion rate

#### Inventory

* Stock levels
* Consumption
* Movements

#### Assets

* Inventory
* Maintenance history
* Due maintenance

#### Evaluations

* Satisfaction ratings
* Staff performance

### Deliverables

Executive reporting dashboard.

---

# MODULE 22

## REST API

### Objective

Expose all business functionality through APIs.

### Standards

* RESTful
* JSON
* Pagination
* Filtering
* Versioning

### Deliverables

Complete API layer.

---

# MODULE 23

## Flutter Mobile Application

### Users

FMO Staff

### Features

* Login
* Assigned work
* Daily updates
* Photos
* Materials used
* Notifications

### Deliverables

Field operations mobile application.

---

# MODULE 24

## Offline Synchronization

### Objective

Support work without internet connectivity.

### Features

* Local SQLite storage
* Download assignments
* Offline updates
* Background synchronization
* Conflict resolution

### Deliverables

Offline-first mobile experience.

---

# MODULE 25

## Audit Trail

### Objective

Track all critical system activities.

### Logs

* Login
* Approval
* Assignment
* Inventory movement
* Asset update
* Maintenance
* Evaluation

### Deliverables

Complete audit logging system.

---

# MODULE 26

## System Administration

### Features

* General settings
* Categories
* Priorities
* Skills
* Maintenance frequencies
* Notification templates

### Deliverables

Central administration module.

---

# MODULE 27

## Future Expansion

The architecture must support future modules without redesign.

Planned future modules include:

* Vehicle Management
* Fleet Maintenance
* Procurement Requests
* Project Monitoring
* Utility Consumption Monitoring
* QR Code Asset Tracking
* Barcode Inventory
* GIS Mapping
* AI Work Order Classification
* Predictive Maintenance
* Executive KPI Dashboard

---

# Recommended Implementation Order

1. Project Foundation
2. Authentication
3. Roles & Permissions
4. User Management
5. Staff Management
6. Skills Management
7. Location Management
8. Work Order Management
9. Preferred Staff Selection
10. Approval Workflow
11. Assignment Engine
12. Daily Progress
13. Follow-up System
14. Evaluation System
15. Consumable Inventory
16. Tools & Equipment
17. Campus Asset Inventory
18. Maintenance Management
19. Preventive Maintenance
20. Notifications
21. Reports & Analytics
22. REST API Finalization
23. Flutter Mobile Application
24. Offline Synchronization
25. Audit Trail
26. System Administration
27. Future Module Integration

---

# Codex Working Instructions

Before implementing any module:

1. Read `PROJECT_CONTEXT.md`
2. Read `PROJECT_STATUS.md`
3. Read `DEVELOPMENT_RULES.md`
4. Read `DATABASE_PLAN.md`
5. Read `MODULES.md`
6. Analyze the current codebase
7. Check for architectural conflicts
8. Review existing migrations and services
9. Recommend the implementation approach
10. Wait for user confirmation before generating code

After completing a module:

* Summarize completed work
* List modified files
* Recommend the next module
* Suggest updates to `PROJECT_STATUS.md`
* Wait for confirmation before proceeding

The objective is to build a long-term, enterprise-grade Facilities Management Platform that remains modular, scalable, maintainable, and fully compatible with future Flutter offline synchronization and campus expansion.
