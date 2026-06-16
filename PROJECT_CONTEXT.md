# PROJECT_CONTEXT.md

# SNSU Facilities Management Office (FMO) Management System

## Project Overview

The **SNSU Facilities Management Office (FMO) Management System** is a centralized enterprise web and mobile application designed to digitalize the operations of the Facilities Management Office of Surigao del Norte State University – Del Carmen Campus.

The system aims to replace manual work order processing, inventory monitoring, asset management, maintenance tracking, and personnel assignment with an integrated platform that supports both web and offline mobile operations.

The project shall be developed using an **API-First Architecture** so that both the web application and Flutter mobile application consume the same backend services.

---

# Vision

To build a scalable, maintainable, enterprise-grade Facilities Management System capable of supporting present and future operational requirements of the university.

The system should eventually become the central platform for managing campus facilities, maintenance operations, inventories, preventive maintenance schedules, personnel workloads, and infrastructure assets.

---

# Technology Stack

## Backend

* Laravel 13+
* PHP 8.4+
* MySQL
* Laravel Sanctum
* Spatie Laravel Permission
* Redis
* Laravel Queues
* Laravel Scheduler

## Web Frontend

* Laravel Blade
* Tailwind CSS
* Alpine.js

## Mobile

* Flutter
* SQLite (Drift)
* Offline-first synchronization

## Development Environment

* Windows 11
* WSL2
* Docker Desktop
* Laravel Sail
* GitHub

---

# Architectural Principles

The project must follow these principles:

* API-First Design
* Clean Architecture
* SOLID Principles
* Repository Pattern
* Service Layer Pattern
* Thin Controllers
* Event-Driven Architecture
* Database Normalization
* Separation of Concerns
* Modular Development

Business logic must never reside inside controllers.

Controllers should only validate requests and delegate processing to services.

---

# Primary Objectives

The system should provide:

* Digital Work Order Processing
* Work Assignment Management
* Staff Skill Management
* Daily Progress Monitoring
* Inventory Management
* Asset Management
* Maintenance Management
* Evaluation and Feedback
* Reporting and Analytics
* Offline Mobile Operations

---

# User Roles

The system shall support the following roles:

## Super Admin

* Full system access
* Manage all modules
* Manage users and permissions
* Configure system settings
* View all reports

---

## FMO Head

* Review work requests
* Approve or reject requests
* Assign personnel
* Monitor operations
* Manage maintenance activities
* View reports

---

## Campus Director

* Directly create work orders
* Directly assign work orders
* Approve requests
* Monitor campus-wide operations
* View reports

---

## Director for Instruction

* Directly create work orders
* Directly assign work orders
* Approve requests
* View reports

---

## FMO Staff

* View assigned work
* Update daily progress
* Upload photos
* Record materials used
* Complete work orders
* View assigned tools

---

## Faculty

* Submit work requests
* Track request status
* Follow up requests
* Evaluate completed work

---

## Admin/Staff

* Submit work requests
* Track requests
* Submit evaluations

---

## Student

* Submit work requests
* Track requests
* Evaluate completed work

---

# Work Order Lifecycle

## Normal Process

Requestor

↓

Submit Work Order

↓

Optional Preferred Staff Selection

↓

FMO Head Review

↓

Campus Director or Director for Instruction may also approve

↓

Final Staff Assignment

↓

Work Starts

↓

Daily Progress Updates

↓

Completion

↓

Evaluation

↓

Closed

---

## Direct Assignment Process

Campus Director

or

Director for Instruction

↓

Create Work Order

↓

Assign Directly

↓

FMO Staff

↓

Completion

---

# Preferred Staff Selection

The requestor may optionally select a preferred FMO staff member.

This selection is considered only as a recommendation.

The preferred staff is **not automatically assigned**.

Final assignment authority belongs to:

* FMO Head
* Campus Director
* Director for Instruction

The system should allow approvers to:

* Accept preferred staff
* Assign another qualified staff
* Assign multiple staff
* Assign a team

---

# Staff Skills

Every FMO staff member should maintain a list of skills.

Examples:

* Carpentry
* Plumbing
* Electrical
* Masonry
* Painting
* Air Conditioning
* Welding
* Civil Works
* Safety Equipment
* General Maintenance

The system should recommend staff based on required skills but always allow manual override.

---

# Work Order Priorities

* Low
* Normal
* High
* Urgent

Urgent requests should be highlighted throughout the system.

---

# Daily Progress Updates

Every work order shall support unlimited progress logs.

Each update includes:

* Date
* Staff
* Status
* Notes
* Photos
* Materials Used
* Estimated Remaining Time

The progress history must remain immutable for audit purposes.

---

# Offline Mobile Application

FMO staff will use a Flutter mobile application.

The application must support:

* Login
* Download assigned work orders
* Offline viewing
* Offline updates
* Offline photo capture
* Offline material recording
* Automatic synchronization

Offline records must never be lost.

Synchronization should occur automatically when internet connectivity becomes available.

---

# Inventory Management

The inventory module shall be divided into multiple categories.

## Consumable Materials

Examples:

* Nails
* Paint
* Cement
* Pipes
* Electrical Wire
* Bulbs
* Switches

Consumables decrease when used.

Every deduction must be recorded.

---

## Tools and Equipment

Examples:

* Drill
* Hammer
* Grinder
* Ladder
* Welding Machine

Tools are assigned to personnel.

They are not consumed.

Assignment history must be maintained.

---

# Campus Asset Management

The system must inventory installed campus assets.

Examples include:

* Air Conditioners
* Fire Extinguishers
* Smoke Detectors
* Fire Alarm Systems
* Emergency Lights
* Wall Fans
* Orbit Fans
* Water Dispensers
* Televisions

Each asset should include:

* Asset Code
* Category
* Brand
* Model
* Serial Number
* Building
* Floor
* Room
* Exact Location
* Status
* Purchase Date
* Warranty
* Photos

---

# Maintenance Management

Assets requiring maintenance should maintain a complete maintenance history.

Examples:

* Air Conditioner Cleaning
* Fire Extinguisher Inspection
* Emergency Light Testing
* Fan Cleaning
* Fire Alarm Testing

Maintenance logs should be searchable and reportable.

---

# Preventive Maintenance

The system should support preventive maintenance scheduling.

Examples:

* Air Conditioner Cleaning every 6 months
* Fire Extinguisher Inspection every month
* Smoke Detector Testing every quarter

Preventive maintenance should automatically generate work orders based on configured schedules.

---

# Material Consumption

Work orders may consume inventory materials.

Examples:

* Paint
* Nails
* Cement
* Electrical Tape

The system should:

* Deduct inventory
* Record quantity used
* Record staff
* Record associated work order
* Maintain complete stock movement history

---

# Request Follow-up

Requestors may submit follow-up messages while a work order is active.

Follow-ups should notify assigned personnel and approvers.

All follow-ups should become part of the permanent work order history.

---

# Evaluation System

After completion, requestors should evaluate the service.

Evaluation criteria:

* Quality of Work
* Timeliness
* Professionalism
* Satisfaction

Additional comments should also be recorded.

Evaluation results will contribute to staff performance analytics.

---

# Notifications

Notifications should be generated for:

* New Requests
* Approval
* Rejection
* Assignment
* Status Changes
* Follow-up Messages
* Completion
* Evaluation Pending

Notifications should be available on both web and mobile platforms.

---

# Reporting

The system should provide dashboards and reports including:

## Work Orders

* Pending
* Approved
* Assigned
* In Progress
* Delayed
* Completed

## Personnel

* Workload
* Productivity
* Completed Tasks
* Average Completion Time

## Inventory

* Current Stock
* Low Stock
* Issued Materials
* Stock Movement

## Assets

* Asset Inventory
* Maintenance History
* Preventive Maintenance Due
* Asset Status

## Evaluation

* Satisfaction Ratings
* Staff Performance
* Service Quality Trends

---

# Future Expansion

The architecture must remain extensible for future modules such as:

* Vehicle Management
* Fleet Maintenance
* Project Monitoring
* Procurement Requests
* Building Inspection
* Utility Consumption Monitoring
* Energy Management
* Visitor Maintenance Requests
* Campus Infrastructure Mapping
* QR Code Asset Tracking
* Barcode Inventory
* GIS Integration
* AI-assisted Work Order Classification
* Predictive Maintenance
* Executive Analytics Dashboard

---

# Development Guidelines

Every feature must be developed as a standalone module.

Before implementing any feature:

1. Analyze the existing architecture.
2. Review database relationships.
3. Check for duplicate logic.
4. Verify security implications.
5. Ensure API compatibility.
6. Ensure future Flutter offline compatibility.

No feature should compromise modularity or scalability.

---

# Codex Working Instructions

Before writing any code:

1. Read this PROJECT_CONTEXT.md document completely.
2. Analyze the existing codebase.
3. Review the current project status.
4. Identify architectural concerns.
5. Recommend the next development phase.
6. Wait for confirmation before generating or modifying code.

Always prioritize maintainability, scalability, and long-term sustainability over quick implementations.

This project is intended to serve as an enterprise-grade Facilities Management Platform for the university and should be developed accordingly.
