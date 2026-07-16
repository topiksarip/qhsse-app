# APD Phase C — Inspection (HANDOFF)

**Commit:** b7b7ca6 (develop)
**Deployed:** ubuntu-5 `/var/www/qhsse-app` @ b7b7ca6
**Date:** 2026-07-17

## Scope

Inspeksi APD/PPE: catat hasil layak / tidak_layak, foto bukti, jadwal (scheduled vs
incidental), dan efek otomatis `tidak_layak` → item status `damaged` + activity/audit.

## Yang Dibangun

### Backend
- **Migration** `2026_07_17_100000_create_apd_inspections_table.php`
  - `apd_item_id` (FK, cascade), `apd_issuance_id` (nullable), `inspection_type`
    (scheduled|incidental|manual), `inspected_by`, `inspection_date`, `result`
    (layak|tidak_layak), `condition`, `next_inspection_date`, `notes`.
  - SoftDeletes + indexes `(apd_item_id, inspection_date)`, `(result, created_at)`.
  - Tidak pakai DB `check` (Laravel Blueprint tidak support; enum divalidasi di request).
- **Model** `App\Models\Modules\Apd\ApdInspection`
  - `Auditable`, `SoftDeletes`, relasi `item()`, `issuance()`, `inspector()`, `files()`.
  - Helper `getInspectionTypes()`, `getResults()`, `getConditions()`.
- **Permission** `apd.inspect` di `CorePermissions` (`all()` + `$apdFull` roleMap).
- **Policy** `ApdInspectionPolicy` (view/update/delete gated `apd.inspect`).
- **Access** `ApdAccess::scopeInspection()` + `canViewInspection()` (fail-closed scope).
- **Request** `StoreApdInspectionRequest` — validasi field + scope item via
  `ApdAccess::canUseLocation` + `photos[]` (image, max 5MB, max 5 file).
- **Controller** `ApdInspectionController` — index/create/store/show/export.
  - `store`: dalam `DB::transaction`, buat inspection, bila `tidak_layak` set item
    `status=damaged` + `condition` + activity `apd.item.damaged`; bila layak & ada
    condition update condition item. Foto via `ManagedFileService` collection `inspection`
    (`FileReference('apd', inspection->id, 'inspection')`).
  - **Bug fix penting:** closure `DB::transaction` harus `use ($data, $actor, $request)`
    agar `$request->file('photos')` tersedia.
- **Routes** `routes/modules/apd.php` — `apd.inspections.{index,create,store,show,export}`
  (gate `apd.inspect` untuk create/store).
- **Search** `SearchController` — entry `apd_inspections` (route show/index, scope inspection).
  Juga hapus duplikat entry `apd_issuances`.

### Frontend (React + Inertia)
- `Inspections/Index.tsx` — list + filter (result/type/search) + status badge + link foto.
- `Inspections/Form.tsx` — pilih item, inspection_type, tanggal, hasil, kondisi,
  next date, notes, upload foto (`forceFormData: true` + `photos[]`). Pre-select item
  dari `?apd_item_id=`.
- `Inspections/Show.tsx` — detail + galeri foto + activity log.
- CTA **"Inspeksi"** di `Items/Show` (bila status ≠ disposed/lost) dan `Issuances/Show`
  (bila status = issued). Controller pass `can.inspect`.

## Tests
`tests/Feature/Modules/Apd/ApdInspectionTest.php` (6 tests, 23 assertions) — all green.
Total APD suite: **21 passed** (Phase A 8 + B 7 + C 6).
Search/Nav suite: 15 passed.

## Production Smoke (ubuntu-5)
- `apd_inspections` table exists, columns correct.
- `apd.inspect` permission id=271, granted to Admin / QHSSE Manager / QHSSE Officer / Super Admin.
- 5 `apd.inspections` routes live; `/login` returns 200.

## Known Limitations / Deferred
- Edit/hapus inspection belum di-build (route/UI tidak dibuat; policy punya method tapi
  belum terpakai). Sesuai scope minimal Phase C.
- Workflow state machine tidak dipakai untuk inspeksi (inspeksi adalah event catat,
  bukan alur transisi panjang). Bila nanti perlu approval inspeksi bisa ditambah.
- Tidak ada seeder demo inspeksi otomatis (demo issuance ada di Phase B).

## Next
Phase D — Integrasi + Dashboard + Search (risk_apd_requirements, incident PPE link,
training fit-test, dashboard widgets, search entries).
