# Workflow Specification

## Engine
- Tabel: `workflow_definitions`, `workflow_transitions`, `workflow_instances`, `workflow_histories`.
- Service: `App\Core\Workflow\WorkflowService` (`start`, `transition`, `canTransition`).
- Permission: `core.workflow.{view,manage,transition}`.

## Penggunaan
- Modul bisnis (incident, investigation, capa, document, audit, permit, environment, quality) menjalankan transisi status terotorisasi.
- Setiap transisi mencatat `workflow_histories` + `activity_logs`.
- UI menampilkan history di panel shared.

## Definisi State
Tiap modul mendefinisikan definition + transitions (submit → review → close, reject, restart, dll).
