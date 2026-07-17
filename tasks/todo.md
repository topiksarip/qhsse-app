# Task List: Inspeksi Multi-Unit (Mode Sesi)

Spec: docs-qhsse/specs/inspection-multi-unit.md
Plan: tasks/plan.md

## Phase 1: Data Foundation
- [ ] Task 1: Migration inspection_units + alter inspection_results (backfill legacy)
- [ ] Task 2: Model InspectionUnit + relasi Inspection/InspectionResult

## Phase 2: Backend — Store & Unit lifecycle
- [ ] Task 3: StoreInspectionRequest validasi units[] (min 1)
- [ ] Task 4: InspectionController.store buat InspectionUnit (multi / default)
- [ ] Task 5: Endpoint simpan hasil 1 unit (PUT units/{unit}) + request + status done
- [ ] Task 6: Endpoint cancel unit (POST units/{unit}/cancel) + request + status cancelled
- [ ] Task 7: complete guard (409 jika ada pending) + route baru

## Phase 3: Backend — Show & Export
- [ ] Task 8: InspectionController.show muat units + per-unit results
- [ ] Task 9: Export per-unit (CsvExporter) di controller export

## Phase 4: Frontend — Reusable + Form
- [ ] Task 10: SearchableMultiSelect.tsx (reusable)
- [ ] Task 11: Form.tsx section Daftar Unit (build list + multi-select, units wajib)

## Phase 5: Frontend — Show eksekusi
- [ ] Task 12: Show.tsx ubah jadi unit-per-page (dropdown tanda, simpan, cancel, complete terkunci)

## Phase 6: Permission & Roles
- [ ] Task 13: CorePermissions tambah role Foreman & Operator (view+create+execute)

## Phase 7: Tests & Build
- [ ] Task 14: InspectionTest perluas (multi-unit, cancel, complete guard, permission, export)
- [ ] Task 15: npm run build + php artisan test hijau

## Checkpoints
- CP1 (after T1-T2): migrasi + model jalan
- CP2 (after T3-T7): backend unit lifecycle teruji PHP
- CP3 (after T8-T12): UI end-to-end
- CP4 (after T13-T15): permission + test + build, siap deploy
