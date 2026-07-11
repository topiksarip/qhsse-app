# Handoff — Phase 2 Investigation & RCA

## 1. Status
- Phase: 2 — Investigation & RCA
- Status: Completed
- Date: 2026-07-11
- Executor: AI Agent
- Project path: `/home/qhsse/qhsse-app-v3`

## 2. Scope Dikerjakan
- Migration: `investigations` table + `investigation_team` pivot
- Model: `Investigation` with relationships (incident, investigator, teamMembers)
- Factory: `InvestigationFactory`
- Permissions: 7 `investigation.reports.*` keys + role matrix
- Form Requests: Store + Update with JSON field validation (five_whys, fishbone, contributing_factors, timeline_events)
- Controller: Full CRUD + start/complete/cancel/export with workflow integration
- Routes: 10 routes in routes/modules.php
- Seeder: InvestigationSeeder (workflow definition + 4 transitions + 3 notification templates)
- React: Index, Form (with 5-Why display + Fishbone display), Show (with RCA sections + workflow timeline + comments + activity + action buttons + complete/cancel modal)
- Navigation: "Investigasi & RCA" added to Modul QHSSE group
- Tests: 19 Pest tests (functional + permission + integration + negative)

## 3. Scope Tidak Dikerjakan
- 5-Why interactive editor (display only in this phase)
- Fishbone interactive editor (display only)
- Team members UI repeater (backend supports)
- CAPA cross-module link from recommendations (Phase 3)
- Data scope filtering

## 4. Test Results
- Tests: **117 passed** (381 assertions) — 79 Phase 0 + 19 Phase 1 + 19 Phase 2
- Build: **pass** (4.46s)
- Migration: all ran

## 5. Next Prompt
```text
Lanjutkan Phase 3 — CAPA / Action Tracking.
Project path: /home/qhsse/qhsse-app-v3.
Baca SOUL.md, IDEA.md, AGENTS.md, docs-qhsse, handoff terakhir.
Kerjakan hanya scope phase ini.
Gunakan core foundation yang sudah ada.
Tambahkan migration/model/request/controller/route/UI/tests.
Jalankan php artisan test dan npm run build.
Update changelog/decision log bila perlu.
Buat handoff setelah selesai.
```
