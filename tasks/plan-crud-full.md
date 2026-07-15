# PLAN — Full CRUD di Seluruh Modul QHSSE (Create/Read/Update/Delete + Permission CRUD Lengkap)

**Tanggal:** 2026-07-16
**Stack:** Laravel 12 · Inertia React + TS · spatie/laravel-permission
**Branch target:** `develop`
**Keputusan user:** "Semua permission ada CRUD" → FULL CRUD di SEMUA modul termasuk Asset family, diterapkan lewat **SoftDeletes** (bukan hard-delete) agar audit trail tetap utuh. `AssetEquipmentSafetyTest` di-relax menjadi "soft-delete, bukan hard-delete".

---

## 1. Tujuan
Setiap modul/element yang memiliki aksi **Create** MUST juga memiliki aksi **Delete** (dan sudah punya Read/Update). Setiap module-group HARUS punya permission quadruplet: `*.view`, `*.create`, `*.update`, `*.delete` (ditambah `*.export` bila relevan). Konsistensi backend↔frontend↔permission.

## 2. Audit Status Saat Ini (faktual, hasil grep/route:list)
### Backend
- Route `DELETE` (destroy) SUDAH ada untuk: audits, campaigns, capa.actions, contractors, document.control, emergency.contacts/drills/plans, environment.records, incident.reports, inspection.checklists, inspection.templates, investigation.reports, legal.registers, permit.work, quality.complaints, quality.ncrs, report-templates, risk.registers, saved-reports, security.incidents, security.patrols, security.visitors, training.programs, training.records, + semua core (companies, sites, departments, employees, positions, users, areas, categories, priorities, severities, statuses, risk-matrix, comments, files).
- **GAP: `assets` family** (asset, asset.certificates, asset.inspections) → TIDAK ada route destroy, controller destroy, dan policy `delete()` di-set `return false`.
- Controller `destroy()` SUDAH ada untuk semua modul bisnis kecuali Asset family + LegalObligation (sub) — perlu cek nested.
- Permission `.delete` (28 string) SUDAH ada di `CorePermissions::all()`, termasuk `asset.management.delete`, `asset.certificates.delete`, `asset.inspections.delete` (tadi unused). Perlu verifikasi semuanya ter-roleMap-kan ke QHSSE roles.

### Frontend (Index.tsx dengan DeleteWithConfirm)
- SUDAH ada (24 halaman): Audit, Capa, Campaign, Contractor, CustomerComplaint, DocumentControl, Emergency(Plans/Drills/Contacts), Environmental, Incident, Inspection(+Templates), Investigation, LegalCompliance, Ncrs, Permit, Quality/Ncrs, Reporting/ReportTemplate, RiskManagement, Security(Incidents/Patrols/VisitorLog), Training(Programs/Records).
- **GAP: 3 halaman** = `Asset/Index`, `Asset/Certificate/Index`, `Asset/Inspection/Index` → belum ada tombol delete.
- `Reporting/SavedReport/Index` & `Training/Matrix/Index` → create=0 delete=0 (Matrix = view matrix, SavedReport = has route destroy tapi belum tombol; PERLU tombol delete di SavedReport).

## 3. Scope
**INCLUDE (full CRUD):**
1. Asset family (asset, certificate, inspection) — via SoftDeletes.
2. SavedReport — tambah tombol delete (route sudah ada).
3. Audit & standardize permission quadruplet untuk SEMUA module-group (pastikan `*.delete` ter-roleMap ke QHSSE Manager/Officer/Supervisor/Security Officer sesuai konteks).
4. Sub-modul bersarang yang punya create → pastikan destroy ada (Legal Obligation, Inspection Checklist item, CAPA action sudah ada; verifikasi Asset cert/inspection nested).

**EXCLUDE:**
- Core platform (sudah full CRUD).
- Matrix view (bukan entity create).
- Hard-delete untuk Asset (pakai SoftDeletes).

## 4. Pendekatan per Layer

### A. Backend — Asset Family SoftDeletes
1. Migration: tambah `deleted_at` (SoftDeletes) ke `assets`, `asset_certificates`, `asset_inspections`.
2. Model: `use SoftDeletes;` + `$dates`/`protected $hidden` sesuai konvensi.
3. Route (`routes/modules/asset.php`): kembalikan
   - `Route::delete('/{asset}', ...'destroy')->middleware('permission:asset.management.delete')`
   - nested cert/inspection destroy dengan middleware `asset.certificates.delete` / `asset.inspections.delete`.
4. Controller: kembalikan `destroy()` tapi pakai `$asset->delete()` (soft) + audit/activity log + `$this->authorize('delete', $model)`.
5. Policy: `AssetPolicy/AssetCertificatePolicy/AssetInspectionPolicy::delete()` kembalikan cek permission (bukan `return false`). Untuk index `can('delete', Model::class)` class-string → policy `delete(User $user, ?Model $model=null)` nullable (sudah pola di ReportTemplate/Campaign).
6. `CorePermissions`: `asset.*.delete` SUDAH ada di `all()`; pastikan masuk roleMap `$assetFull` (tadi sempat saya cabut — kembalikan).

### B. Backend — Permission CRUD Standardisasi
1. Audit `CorePermissions::all()` vs `roleMap`: pastikan tiap module-group punya `view/create/update/delete` + `export` (bila ada). Modul tanpa delete permission → tambah.
2. Pastikan seeder (`RolesAndPermissionsSeeder`) sinkron (jalankan `--force` di prod).

### C. Frontend — Tombol Delete
1. `Asset/Index.tsx`, `Asset/Certificate/Index.tsx`, `Asset/Inspection/Index.tsx`: kembalikan DeleteWithConfirm (routeName `assets.destroy` / `assets.certificates.destroy` / `assets.inspections.destroy`, permission `asset.*.delete`, `can.delete` dari controller index).
2. `Reporting/SavedReport/Index.tsx`: tambah DeleteWithConfirm (`saved-reports.destroy`).
3. Pastikan semua Index lain yang punya create sudah punya delete (verifikasi ulang post-implementasi).

### D. Tests
1. Relax `AssetEquipmentSafetyTest`:
   - `it uses status lifecycle without destructive asset or compliance-history routes` → ubah ekspektasi menjadi route destroy **ADA** (soft-delete) — atau pisah: route ada tapi model pakai SoftDeletes (assert `deleted_at` column exists, bukan "no delete route").
   - `it preserves asset compliance history without soft-delete` → ubah menjadi `it soft-deletes asset compliance history (audit retained)` → assert `assertSoftDeleted()` bukan `assertDatabaseMissing('deleted_at')`.
2. Tambah delete smoke test per modul (happy path + permission block) agar full-CRUD terjamin.

## 5. Urutan Implementasi (YOLO terkontrol, incremental)
1. **Phase A — Asset SoftDeletes:** migration + model + route + controller + policy + permission roleMap + frontend 3 halaman. Test relax + smoke. ✅ verifikasi build+test.
2. **Phase B — SavedReport delete button** + verifikasi route.
3. **Phase C — Permission audit & standardization:** pastikan semua module-group punya quadruplet + roleMap. Seed prod.
4. **Phase D — Full regression:** `npm run build` + `php artisan test --parallel` (target: 0 regression dari baseline 13 pre-existing unrelated).

## 6. Definition of Done
- Tiap modul dengan create → punya destroy route + controller + policy delete + permission `*.delete` di roleMap + tombol DeleteWithConfirm di Index.
- Asset family: soft-deletable, audit trail utuh, safety test relaxed & green.
- `CorePermissions::all()` lengkap quadruplet per module-group.
- `npm run build` green; targeted delete tests green; tidak ada regression baru.
- Handoff + Decision Log update.

## 7. Risk
- **Asset audit integrity:** di-mitigasi SoftDeletes (data tidak hilang fisik, bisa restore/audit).
- **Permission blow-up:** semua role dapat delete — sesuai instruksi user "semua permission ada CRUD"; jika ingin lebih selektif, bisa di-tune di roleMap (Super Admin/Admin/QHSSE full; Supervisor read-only delete).
- **13 pre-existing test failures** (files/comments/nav/NCR) tidak terkait — tetap out-of-scope.

## 8. Catatan
- Tombol delete sudah ada di 24 halaman dari Phase 4 + sesi ini; pekerjaan utama sesi ini = Asset family (SoftDeletes) + SavedReport + permission standardization + relax safety test.
- Deploy ubuntu-5 setelah tiap phase: git pull → npm ci → npm run build → optimize:clear → seed --force → restart php-fpm + `qhsse-queue.service`.
