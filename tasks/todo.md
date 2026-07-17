# Task List: Inspeksi Multi-Unit (Mode Sesi) — COMPLETED

Spec: docs-qhsse/specs/inspection-multi-unit.md
Plan: tasks/plan.md
Handoff: handoff/PHASE-INSPECTION-MULTI-UNIT-HANDOFF.md
Commit: 4d4c167 (develop) — deployed ubuntu-5

## Phase 1: Data Foundation
- [x] Task 1: Migration inspection_units + alter inspection_results (backfill legacy)
- [x] Task 2: Model InspectionUnit + relasi Inspection/InspectionResult

## Phase 2: Backend — Store & Unit lifecycle
- [x] Task 3: StoreInspectionRequest validasi units[] (min 1)
- [x] Task 4: InspectionController.store buat InspectionUnit (multi / default)
- [x] Task 5: Endpoint simpan hasil 1 unit (PUT units/{unit}) + request + status done
- [x] Task 6: Endpoint cancel unit (POST units/{unit}/cancel) + request + status cancelled
- [x] Task 7: complete guard (409 jika ada pending) + route baru

## Phase 3: Backend — Show & Export
- [x] Task 8: InspectionController.show muat units + per-unit results
- [x] Task 9: Export per-unit (CsvExporter) di controller export

## Phase 4: Frontend — Reusable + Form
- [x] Task 10: SearchableMultiSelect.tsx (reusable)
- [x] Task 11: Form.tsx section Daftar Unit (build list + multi-select, units wajib)

## Phase 5: Frontend — Show eksekusi
- [x] Task 12: Show.tsx ubah jadi unit-per-page (dropdown tanda, simpan, cancel, complete terkunci)

## Phase 6: Permission & Roles
- [x] Task 13: CorePermissions tambah role Foreman & Operator (view+create+execute)

## Phase 7: Tests & Build
- [x] Task 14: InspectionTest perluas (multi-unit, cancel, complete guard, permission, export)
- [x] Task 15: npm run build + php artisan test hijau (25 passed, 78 assertions)

## Status
SELESAI & DEPLOYED. Verifikasi: build PASS, test PASS, migrate+backfill prod OK, role Foreman/Operator terdaftar, login 200.
