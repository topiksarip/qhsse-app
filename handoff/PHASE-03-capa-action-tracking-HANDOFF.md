# Handoff — Phase 3 CAPA / Action Tracking

## 1. Status
- Phase: 3 — CAPA / Action Tracking
- Status: Completed
- Date: 2026-07-11
- Executor: AI Agent
- Project path: `/home/qhsse/qhsse-app-v3`

## 2. Scope Dikerjakan
- Migration: `capa_actions` table (22 columns including source_module/source_reference_id for cross-module linking, overdue detection via due_date)
- Model: `CapaAction` with 7 relationships + `is_overdue` accessor
- Factory: `CapaActionFactory`
- Permissions: 8 `capa.actions.*` keys (view/create/update/submit/verify/close/reject/export) + role matrix
- Form Requests: Store + Update
- Controller: CRUD + start/submitVerification/verifyClose/reject/restart/export with workflow + audit + activity + notification
- Routes: 12 routes
- Seeder: CapaSeeder (3 notification templates: assigned/closed/rejected)
- React: Index (with overdue RED highlight), Form (source selection + assignment), Show (verification panel + workflow timeline + comments + activity + action buttons + reason modal)
- Navigation: "CAPA / Action" in Modul QHSSE group
- Tests: 20 Pest tests

## 3. Test Results
- Tests: **136 passed** (423 assertions) — 79 Phase 0 + 19 Phase 1 + 19 Phase 2 + 19 Phase 3
- Build: **pass** (4.86s)

## 4. Next Prompt
```text
Lanjutkan Phase 4 — Inspection Checklist.
Project path: /home/qhsse/qhsse-app-v3.
```
