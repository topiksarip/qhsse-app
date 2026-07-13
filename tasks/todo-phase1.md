# Phase 1 — Incident Reporting Task Checklist

## Backend (Tasks 1-7)

- [x] Task 1: Migration + Model + Factory — `incidents` table
- [x] Task 2: Migration — `incident_involved_persons` pivot
- [x] Task 3: CorePermissions + seeder update (7 incident permissions)
- [x] Task 4: StoreIncidentReportRequest + UpdateIncidentReportRequest
- [x] Task 5: Incident controllers/services (CRUD, workflow, evidence, print, export)
- [x] Task 6: Routes in routes/modules.php
- [x] Task 7: IncidentReportingSeeder (numbering format, workflow def+transitions, notification templates)

## Frontend (Tasks 8-11)

- [x] Task 8: Index.tsx — list with filter/search/pagination/export
- [x] Task 9: Form.tsx — sectioned create/edit form and involved-person repeater
- [x] Task 10: Show.tsx — evidence, activity, workflow actions, and print report
- [x] Task 11: Navigation update in AuthenticatedLayout.tsx

## Tests & Verification (Task 12)

- [x] Task 12: Feature and acceptance tests
- [x] `make test` — 403 tests / 1,737 assertions green
- [x] `npm run build` — passes
- [x] Temporary SQLite `migrate:fresh --seed` — clean
- [x] Route/config/view cache — passes with local array cache fallback

## Docs & Handoff (Task 13)

- [x] Task 13: Changelog + Decision Log + closure handoff
