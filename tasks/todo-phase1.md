# Phase 1 — Incident Reporting Task Checklist

## Backend (Tasks 1-7)

- [ ] Task 1: Migration + Model + Factory — `incidents` table
- [ ] Task 2: Migration — `incident_involved_persons` pivot
- [ ] Task 3: CorePermissions + seeder update (7 incident permissions)
- [ ] Task 4: StoreIncidentReportRequest + UpdateIncidentReportRequest
- [ ] Task 5: IncidentReportController (index/create/store/show/edit/update/submit/review/close/export)
- [ ] Task 6: Routes in routes/modules.php
- [ ] Task 7: IncidentReportingSeeder (numbering format, workflow def+transitions, notification templates)

## Frontend (Tasks 8-11)

- [ ] Task 8: Index.tsx — list with filter/search/pagination/export
- [ ] Task 9: Form.tsx — sectioned create/edit form
- [ ] Task 10: Show.tsx — detail with evidence/comments/activity/workflow actions
- [ ] Task 11: Navigation update in AuthenticatedLayout.tsx

## Tests & Verification (Task 12)

- [ ] Task 12: Feature tests (16 test cases)
- [ ] `php artisan test` — all green
- [ ] `npm run build` — passes
- [ ] `php artisan migrate:status` — all ran
- [ ] `php artisan db:seed` — clean

## Docs & Handoff (Task 13)

- [ ] Task 13: Changelog + Decision Log + Handoff
