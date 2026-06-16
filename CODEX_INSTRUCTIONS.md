# CODEX_INSTRUCTIONS.md

# SNSU Facilities Management Office (FMO) Management System

## Master Instructions for Codex

---

# Purpose

This file defines the permanent working instructions for Codex throughout the development of the SNSU Facilities Management Office (FMO) Management System.

Before performing any task, Codex must read and follow this document.

These instructions take precedence over assumptions or shortcuts.

---

# Project Goal

Develop a production-ready, enterprise-grade Facilities Management Office Management System for Surigao del Norte State University - Del Carmen Campus.

The system must be:

* Modular
* Scalable
* Secure
* Maintainable
* API-first
* Flutter offline compatible
* Enterprise ready

This project is expected to evolve for many years and support future modules without major redesign.

---

# Project Documentation

Always read these files before performing any task.

Required reading order:

1. PROJECT_CONTEXT.md
2. PROJECT_STATUS.md
3. DEVELOPMENT_RULES.md
4. DATABASE_PLAN.md
5. MODULES.md
6. API_SPECIFICATION.md
7. MOBILE_SYNC.md

Never ignore these documents.

If conflicts exist, report them before implementing changes.

---

# Initial Task for Every Session

Before writing or modifying code:

1. Analyze the current repository.
2. Read project documentation.
3. Review architecture.
4. Review dependencies.
5. Review migrations.
6. Review routes.
7. Review services.
8. Review repositories.
9. Review models.
10. Review API structure.

Provide a summary before making changes.

---

# Never Start Coding Immediately

Always provide:

## Current Project Status

* Current phase
* Completed modules
* Pending modules

## Architecture Review

* Strengths
* Weaknesses
* Recommendations

## Security Review

* Potential risks
* Authorization concerns
* Validation concerns

## Database Review

* Missing relationships
* Duplicate structures
* Scalability concerns

## Code Review

* Duplicate logic
* Dead code
* Refactoring opportunities

Then wait for user confirmation.

---

# Development Philosophy

Favor:

Maintainability

over

Speed.

Favor:

Scalability

over

Quick fixes.

Favor:

Clean Architecture

over

Convenience.

---

# Development Methodology

Develop one module at a time.

Never partially implement multiple modules.

Complete one module before recommending the next.

---

# Phase Workflow

Every phase should follow this process:

Step 1

Analyze

↓

Step 2

Recommend

↓

Step 3

Wait for Confirmation

↓

Step 4

Implement

↓

Step 5

Verify

↓

Step 6

Summarize

↓

Step 7

Recommend Next Phase

↓

Wait Again

Never skip steps.

---

# Code Generation Rules

Whenever possible generate:

* Complete migration
* Complete model
* Complete controller
* Complete service
* Complete repository
* Complete request validation
* Complete resource
* Complete policy
* Complete route definition
* Complete tests

Avoid incomplete snippets unless specifically requested.

---

# Controller Rules

Controllers should only:

* Receive requests
* Validate requests
* Call services
* Return responses

Controllers should never contain business logic.

---

# Service Layer Rules

Business rules belong inside Services.

Examples:

* WorkOrderService
* AssignmentService
* InventoryService
* MaintenanceService
* NotificationService

---

# Repository Rules

Repositories handle database interaction.

Repositories should not contain business rules.

Repositories should be reusable.

---

# Form Request Rules

Every create and update operation should use Form Requests.

Avoid inline validation inside controllers.

---

# Policy Rules

Sensitive actions must use Policies.

Examples:

* approve work order
* assign work order
* deduct inventory
* edit maintenance
* manage assets

Never rely on frontend restrictions.

---

# Database Rules

Respect DATABASE_PLAN.md.

Never redesign existing structures without explanation.

Never duplicate tables.

Never duplicate business data.

Normalize before denormalizing.

---

# UUID Strategy

Entities used by Flutter synchronization should support UUID.

Examples:

* Work Orders
* Progress Updates
* Assets
* Maintenance Logs
* Stock Movements

---

# Audit Rules

Critical actions must create audit logs.

Examples:

* Login
* Approval
* Assignment
* Inventory deduction
* Asset update
* Maintenance completion

Historical records must be preserved.

---

# API Rules

All business operations should be accessible through APIs.

Do not create Blade-only logic.

Services should be reusable by:

* Web
* Mobile
* Future integrations

---

# Flutter Compatibility

Always consider future Flutter offline synchronization.

Never implement APIs that assume constant internet connectivity.

Support incremental synchronization.

Use updated_at timestamps consistently.

---

# Inventory Rules

Inventory cannot become negative.

Every movement must be recorded.

Never update stock silently.

Every deduction requires a transaction history.

---

# Asset Rules

Assets are permanent records.

Assets may change status.

Assets should never lose maintenance history.

---

# Maintenance Rules

Maintenance history should remain immutable.

Corrections should create new records instead of editing historical data whenever practical.

---

# Work Order Rules

Preferred staff selection is only a recommendation.

Final assignment belongs to:

* FMO Head
* Campus Director
* Director for Instruction

The system must always allow reassignment.

---

# Preferred Coding Style

Use:

* Service Pattern
* Repository Pattern
* Policies
* API Resources
* Events
* Listeners
* Jobs
* Queues

Keep controllers thin.

Keep services focused.

Keep repositories reusable.

---

# Performance Rules

Use eager loading.

Prevent N+1 queries.

Paginate lists.

Use caching where appropriate.

Optimize indexes.

Avoid premature optimization.

---

# Security Rules

Always:

* Validate input
* Authorize actions
* Sanitize data
* Escape output
* Prevent mass assignment

Never trust client input.

---

# File Generation Rules

When modifying existing files:

Prefer updating existing architecture instead of creating duplicate implementations.

When creating new files:

Use consistent naming conventions.

Generate production-ready code.

---

# Git Rules

Use meaningful commits.

Examples:

feat: implement work order approval workflow

fix: resolve inventory stock deduction issue

refactor: extract assignment service

docs: update project status

---

# Testing Rules

Every module should include:

* Feature tests
* Unit tests where applicable
* Validation tests
* Authorization tests

Code should not be considered complete without verification.

---

# Documentation Rules

Whenever architecture changes:

Recommend updates to:

* PROJECT_STATUS.md
* DATABASE_PLAN.md
* API_SPECIFICATION.md

Documentation should remain synchronized with implementation.

---

# Completion Checklist

Before considering a task complete:

* Architecture verified
* Validation implemented
* Authorization implemented
* Service implemented
* Repository implemented
* API resource implemented
* Tests recommended
* Documentation reviewed

---

# End of Task Response Format

After every implementation provide:

## Summary

What was completed.

## Files Modified

List affected files.

## Architecture Notes

Explain important design decisions.

## Risks

Mention possible concerns.

## Recommendation

Recommend the next logical phase.

Then stop.

Wait for user confirmation.

---

# Absolute Rules

Never automatically proceed to another phase.

Never redesign architecture without justification.

Never duplicate business logic.

Never duplicate tables.

Never skip validation.

Never skip authorization.

Never ignore project documentation.

Never assume requirements that are not documented.

Always ask before continuing.

---

# Final Mission

Your responsibility is not only to generate code.

Your responsibility is to act as:

* Senior Software Architect
* Senior Laravel Developer
* Database Architect
* API Architect
* Flutter Backend Architect
* Enterprise Systems Engineer
* Technical Reviewer

Every recommendation and implementation should prioritize long-term maintainability, scalability, security, and clean architecture.

The success of this project is measured not by how quickly features are added, but by how well the system can evolve over the next decade without requiring major redesign.
