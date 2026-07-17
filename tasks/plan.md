# Implementation Plan: Inspeksi Multi-Unit (Mode Sesi)

Berdasarkan: docs-qhsse/specs/inspection-multi-unit.md (DRAFT, open questions RESOLVED)
Intent: docs-qhsse/intent/inspection-multi-unit.md (CONFIRMED)

## Overview
Menambahkan konsep "Inspection Unit" ke modul Inspection agar 1 sesi inspeksi bisa
berisi banyak unit fisik (misal 94 wire rope sling) dengan hasil checklist per-unit.
Eksekusi berjalan unit-per-page; inspeksi hanya bisa diselesaikan jika semua unit
berstatus done/cancelled. Menambah role Foreman & Operator (create+execute) dan export
hasil per-unit.

## Architecture Decisions
- `inspection_units` sebagai entity baru; `inspection_results` di-relasikan ke unit
  via `inspection_unit_id` (NOT NULL setelah backfill legacy).
- Backfill: migrasi membuat 1 unit default per inspection lama, lalu set results.unit_id.
- Mode single-unit tetap jalan: store tanpa `units` → buat 1 default unit otomatis.
- `units` wajib >=1 di form (validasi tolak kalau kosong).
- Isi/cancel/selesaikan pakai permission `inspection.checklists.execute` (sudah ada).
- Foreman/Operator: role baru di CorePermissions, dapat view+create+execute inspection.
- Export per-unit: 1 baris per unit, kolom = identifier + tiap item checklist + status.

## Task List

### Phase 1: Data Foundation
- [ ] Task 1: Migration inspection_units + alter inspection_results (+ backfill legacy)
- [ ] Task 2: Model InspectionUnit + relasi Inspection/InspectionResult

### Checkpoint 1: Schema & model
- [ ] Migrasi jalan di local (sqlite) & prod (pg); model ter-load

### Phase 2: Backend — Store & Unit lifecycle
- [ ] Task 3: StoreInspectionRequest validasi units[] (min 1)
- [ ] Task 4: InspectionController.store buat InspectionUnit (multi / default)
- [ ] Task 5: Endpoint simpan hasil 1 unit (PUT units/{unit}) + request + status done
- [ ] Task 6: Endpoint cancel unit (POST units/{unit}/cancel) + request + status cancelled
- [ ] Task 7: complete guard (409 jika ada pending) + route baru

### Checkpoint 2: Backend unit lifecycle
- [ ] Test PHP: buat multi-unit, simpan 1 unit, cancel, complete guard

### Phase 3: Backend — Show & Export
- [ ] Task 8: InspectionController.show muat units + per-unit results
- [ ] Task 9: Export per-unit (CsvExporter) di controller export

### Phase 4: Frontend — Reusable + Form
- [ ] Task 10: SearchableMultiSelect.tsx (reusable)
- [ ] Task 11: Form.tsx section Daftar Unit (build list + multi-select, units wajib)

### Phase 5: Frontend — Show eksekusi
- [ ] Task 12: Show.tsx ubah jadi unit-per-page (dropdown tanda, simpan, cancel, complete terkunci)

### Checkpoint 3: End-to-end UI
- [ ] Flow buat 94 unit + eksekusi per-unit + selesaikan terkunci

### Phase 6: Permission & Roles
- [ ] Task 13: CorePermissions tambah role Foreman & Operator (view+create+execute)

### Phase 7: Tests & Build
- [ ] Task 14: InspectionTest perluas (multi-unit, cancel, complete guard, permission, export)
- [ ] Task 15: npm run build + php artisan test hijau

### Checkpoint 4: Complete
- [ ] Semua acceptance criteria spec terpenuhi; review user; deploy ubuntu-5

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Backfill legacy results gagal (unit_id null) | High | Migrasi buat default unit per inspection, set NOT NULL setelah backfill; jalankan di transaction |
| Show.tsx rewrite besar merusak UI existing | Med | Ubah bertahap; jaga mode single-unit via default unit; test build |
| SearchableMultiSelect aksesibel/tidak stabil | Low | Komponen reusable sederhana; test manual di browser |
| Permission Foreman/Operator salah map | Med | Ikuti pola $inspectionFull subset; seed ulang & verify di prod |

## Parallelization
- Task 10 (SearchableMultiSelect) bisa paralel dengan Phase 2 backend (kontrak sudah jelas).
- Task 13 (role) paralel dengan Phase 4 frontend.
- Sisanya sequential (dependency chain).
