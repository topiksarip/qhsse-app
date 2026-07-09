# Handoff — Phase 0 Task 0.10 Workflow Core

## 1. Status

- Phase: 0 — Core Foundation
- Task: 0.10 — Workflow Core
- Status: Completed
- Date: 2026-07-09
- Executor: AI Agent

## 2. Scope Dikerjakan

- Implemented reusable workflow definitions.
- Implemented reusable workflow transitions with action keys, labels, reason requirement, and permission requirement.
- Implemented workflow instances using `module_name + reference_id`.
- Implemented workflow history recording actor, action, from/to status, reason, and metadata.
- Implemented `WorkflowService` for start, transition validation, transition execution, permission checks, and history creation.
- Added baseline workflows for Incident, CAPA, and Document.
- Added workflow permissions, routes, controller, request, minimal UI, tests, changelog, decision log, and handoff.

## 3. Scope Tidak Dikerjakan

- No advanced visual workflow builder.
- No per-module business forms.
- No audit trail integration; audit trail is Task 0.11.
- No comments/activity integration; comments/activity is Task 0.12.
- No notification triggers; notification core is Task 0.13.
- No module-specific workflow policies beyond permission checks.

## 4. File/Folder Dibuat

- `database/migrations/2026_07_09_102000_create_workflow_definitions_table.php`
- `database/migrations/2026_07_09_102001_create_workflow_transitions_table.php`
- `database/migrations/2026_07_09_102002_create_workflow_instances_table.php`
- `database/migrations/2026_07_09_102003_create_workflow_histories_table.php`
- `app/Models/Core/Workflow/WorkflowDefinition.php`
- `app/Models/Core/Workflow/WorkflowTransition.php`
- `app/Models/Core/Workflow/WorkflowInstance.php`
- `app/Models/Core/Workflow/WorkflowHistory.php`
- `database/factories/Core/Workflow/WorkflowDefinitionFactory.php`
- `database/factories/Core/Workflow/WorkflowTransitionFactory.php`
- `app/Core/Workflow/WorkflowService.php`
- `database/seeders/WorkflowSeeder.php`
- `app/Http/Controllers/Core/WorkflowController.php`
- `app/Http/Requests/Core/WorkflowTransitionRunRequest.php`
- `resources/js/Pages/Core/Workflow/Index.tsx`
- `resources/js/Pages/Core/Workflow/Show.tsx`
- `resources/js/Pages/Core/Workflow/History.tsx`
- `tests/Feature/Core/WorkflowCoreTest.php`
- `handoff/PHASE-00-task-0.10-workflow-core-HANDOFF.md`

## 5. File/Folder Diubah

- `database/seeders/DatabaseSeeder.php`
- `app/Core/Permissions/CorePermissions.php`
- `routes/core.php`
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `docs-qhsse/19_DECISION_LOG.md`
- `docs-qhsse/20_CHANGELOG.md`

## 6. Database/Migration/Model

- Table: `workflow_definitions`
  - `module_name`, `code`, `name`, `initial_status`, `is_active`
- Table: `workflow_transitions`
  - `workflow_definition_id`, `from_status`, `to_status`, `action_key`, `action_label`, `requires_reason`, `required_permission`, `is_active`
  - unique: `workflow_definition_id, from_status, action_key`
- Table: `workflow_instances`
  - `workflow_definition_id`, `module_name`, `reference_id`, `current_status`, `started_by`, `completed_at`
  - unique: `module_name, reference_id`
- Table: `workflow_histories`
  - `workflow_instance_id`, `module_name`, `reference_id`, `from_status`, `to_status`, `action_key`, `action_label`, `reason`, `actor_id`, `metadata`
- Models:
  - `App\Models\Core\Workflow\WorkflowDefinition`
  - `App\Models\Core\Workflow\WorkflowTransition`
  - `App\Models\Core\Workflow\WorkflowInstance`
  - `App\Models\Core\Workflow\WorkflowHistory`

## 7. API/Backend

- `GET /core/workflow` — definition list + recent instances, permission `core.workflow.view`
- `GET /core/workflow/history` — history list/filter, permission `core.workflow.view`
- `GET /core/workflow/{definition}` — transition detail, permission `core.workflow.view`
- `POST /core/workflow/run` — start instance or run transition, permission `core.workflow.transition`

## 8. UI/Frontend

- `resources/js/Pages/Core/Workflow/Index.tsx`
  - Workflow definition list.
  - Workflow transition tester.
  - Recent workflow instances.
- `resources/js/Pages/Core/Workflow/Show.tsx`
  - Transition table for one definition.
- `resources/js/Pages/Core/Workflow/History.tsx`
  - Workflow history list and filters.
- `resources/js/Layouts/AuthenticatedLayout.tsx`
  - Added Workflow navigation link.

## 9. Permission Ditambahkan

- `core.workflow.view`
- `core.workflow.manage`
- `core.workflow.transition`

## 10. Master Data/Seed Ditambahkan

- `WorkflowSeeder` adds baseline workflow definitions and transitions for:
  - Incident
  - CAPA
  - Document

## 11. Workflow/Status Ditambahkan

Incident:
- `draft -> submitted` via `submit`
- `submitted -> under_review` via `review`
- `under_review -> investigation` via `investigate`
- `under_review -> action_open` via `open_action`
- `investigation -> action_open` via `open_action`
- `action_open -> closed` via `close`, reason required
- `submitted -> rejected` via `reject`, reason required
- `under_review -> rejected` via `reject`, reason required

CAPA:
- `open -> in_progress` via `start`
- `in_progress -> waiting_verification` via `submit_verification`
- `waiting_verification -> closed` via `verify_close`, reason required
- `waiting_verification -> rejected` via `reject`, reason required
- `rejected -> in_progress` via `restart`

Document:
- `draft -> review` via `submit_review`
- `review -> approved` via `approve`
- `approved -> effective` via `make_effective`
- `effective -> obsolete` via `obsolete`, reason required
- `review -> rejected` via `reject`, reason required
- `rejected -> draft` via `revise`

## 12. Notification Ditambahkan

- No notifications added.

## 13. Report/Export Ditambahkan

- No report/export added.

## 14. Test Dijalankan

- `php artisan test tests/Feature/Core/WorkflowCoreTest.php`
- `php artisan test`
- `npm run build`

## 15. Hasil Test

- Passed: `php artisan test tests/Feature/Core/WorkflowCoreTest.php` — 8 passed, 17 assertions.
- Passed: `php artisan test` — 59 passed, 175 assertions.
- Passed: `npm run build`.
- Failed: none.
- Not tested: browser manual workflow tester.

## 16. Known Issues

- `WorkflowService::transition()` starts missing instances automatically, then applies the transition. Business modules may choose to explicitly call `start()` first for stricter lifecycle control.
- Terminal status detection is currently simple: `closed`, `rejected`, `obsolete`, `cancelled`.
- UI is a foundation/tester UI, not a final workflow builder.

## 17. Deferred Items

- Advanced workflow builder UI.
- Per-module workflow policies beyond permission keys.
- Workflow audit trail integration.
- Workflow notification events.
- Workflow activity timeline integration.
- Module-specific status badge/color integration.

## 18. Decision Log Update

- Added decision: workflow uses reusable definitions/instances/history with `module_name + reference_id` rather than module-specific workflow tables.

## 19. Breaking Changes

- None.

## 20. Next Phase Readiness

- Ready for Phase 0 Task 0.11 — Audit Trail.
- Reason: Workflow Core is implemented, verified, and available for future modules.

## 21. Rekomendasi Prompt Berikutnya

```text
Lanjutkan Phase 0 Task 0.11 — Audit Trail.
Project path: /home/ubuntu/qhsse-app-v3.
Baca AGENTS.md, docs-qhsse/27_PHASE_0_BUILD_PLAN.md, dan handoff/PHASE-00-task-0.10-workflow-core-HANDOFF.md.
Kerjakan hanya audit trail: reusable audit log schema, audit service, selected core model audit hooks or explicit controller/service logging, audit viewer, tests, changelog, decision log if needed, and handoff.
Jangan kerjakan comments, notification, export, dashboard, atau modul bisnis.
```
