# Implementation Plan: QHSSE Phase 0 Core Foundation

## Overview

Build the Core Foundation for the QHSSE Web Application using Laravel 12, Inertia React, PostgreSQL, Redis, Tailwind CSS, and modular monolith architecture. This phase creates the shared platform used by all future modules: auth, users, roles, permissions, master data, files, numbering, workflow, audit trail, comments, notifications, export base, and dashboard shell.

## Architecture Decisions

- Use modular monolith, not microservices.
- Use Laravel + Inertia React to move fast while keeping full-stack cohesion.
- Use PostgreSQL and Redis as enterprise-ready defaults.
- Use spatie/laravel-permission for RBAC.
- Build reusable Core services before business modules.
- Use generic reference patterns for cross-module concerns.
- Keep workflow status-based in Phase 0.

## Phase 0 Task List

### Foundation Setup

- [ ] Task 0.1: Bootstrap Laravel/Inertia project
- [ ] Task 0.2: Establish project rules and modular structure

### Identity and Access

- [ ] Task 0.3: Authentication
- [ ] Task 0.4: User, employee, company core
- [ ] Task 0.5: Role, permission, and scope

### Master Data

- [ ] Task 0.6: Organization master
- [ ] Task 0.7: General QHSSE master data

### Shared Core Services

- [ ] Task 0.8: File service
- [ ] Task 0.9: Numbering service
- [ ] Task 0.10: Workflow core
- [ ] Task 0.11: Audit trail
- [ ] Task 0.12: Comments and activity log
- [ ] Task 0.13: Notification core

### UI/Reporting Base

- [ ] Task 0.14: Search, filter, pagination, export base
- [ ] Task 0.15: Dashboard shell

### Verification and Handoff

- [ ] Task 0.16: Core UAT, documentation, and handoff

## Checkpoint 1: Bootstrap Complete

After Tasks 0.1-0.2:

- [ ] App runs locally.
- [ ] Inertia page renders.
- [ ] Project structure exists.
- [ ] `AGENTS.md` exists.

## Checkpoint 2: Access Control Complete

After Tasks 0.3-0.5:

- [ ] Login/logout works.
- [ ] Admin can create user.
- [ ] Role permission protects backend route/action.
- [ ] Scope model is established.

## Checkpoint 3: Master Data Complete

After Tasks 0.6-0.7:

- [ ] Site/area/department/company/employee masters work.
- [ ] Severity/priority/status/category/risk matrix exists.
- [ ] Incident phase has required master data.

## Checkpoint 4: Core Services Complete

After Tasks 0.8-0.13:

- [ ] File upload/download secure.
- [ ] Numbering unique.
- [ ] Workflow transition history works.
- [ ] Audit trail captures critical changes.
- [ ] Comment/activity works.
- [ ] Notification center works.

## Checkpoint 5: Phase 0 Complete

After Tasks 0.14-0.16:

- [ ] Core list/export pattern works.
- [ ] Dashboard shell works.
- [ ] Tests/build run.
- [ ] Handoff created.
- [ ] Ready for Phase 1 Incident Reporting.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Building too much at once | High | Follow task order and checkpoints |
| Permission bypass | High | Server-side checks and tests |
| File access leak | High | Private storage and authorized endpoint |
| Workflow over-engineering | Medium | Status-based workflow only |
| Missing handoff | Medium | Phase cannot close without handoff |

## Open Questions

- Confirm final hosting target.
- Confirm Docker requirement.
- Confirm company PDF templates.
- Confirm SSO roadmap.
- Confirm field-level permission for medical/security data.
