# HANDOFF — APD/PPE Module Phase A (Master Katalog + Inventori Stok)

**Date:** 2026-07-16
**Branch:** develop (commit `091bad6`)
**Status:** ✅ Complete, tested, deployed to ubuntu-5

## What was built (vertical slice)

Phase A delivers the APD/PPE **Master Katalog** + **Inventori Stok** foundation, fully
wired into the existing QHSSE core services (numbering, audit, activity, scope, RBAC, search).

### Backend
- **Migrations**
  - `apd_catalogs` — master katalog jenis APD (kode otomatis, kategori, tipe pelacakan
    serial/batch, SKU, manufacturer, model, standard, level perlindungan, masa pakai,
    interval inspeksi, biaya, stok minimum/reorder, site/department konteks, soft-delete).
  - `apd_items` — unit/batch stok (no. item otomatis, polymorphic holder, status/condition,
    lokasi, tanggal produksi/beli/terima/kedaluwarsa, inspeksi berikutnya, audit).
- **Models** `ApdCatalog`, `ApdItem` — `Auditable` + `SoftDeletes`, `ProvidesAuditContext`,
  `module_name = 'apd'`, scope via `ApdAccess`, recompute `active_quantity`.
- **Permissions** `apd.view/create/update/delete/export/receive` (issue/inspect/approve/
  request reserved for Phase B/C) wired into QHSSE Manager, QHSSE Officer, Supervisor,
  Department Head, Employee/Reporter, Contractor, Auditor, Top Management.
- **Policies** `ApdCatalogPolicy` (global master → any `apd.view`), `ApdItemPolicy` (scoped).
- **Scope service** `ApdAccess` (mirrors `AssetAccess` fail-closed org scope).
- **Controllers** `ApdCatalogController` (CRUD + export + audit/activity), `ApdItemController`
  (receive/list/show + auto next_inspection_date/expiry from catalog, scope, audit/activity).
- **Requests** `StoreApdCatalogRequest`, `UpdateApdCatalogRequest`, `ReceiveApdItemRequest`
  (server-side validation + cross-location reject).
- **Routes** `routes/modules/apd.php` (require'd in `routes/modules.php`).
- **Numbering** `apd` format `PPE-YYYY-NNNN` seeded in `NumberingFormatSeeder`.
- **Search** APD katalog + inventori added to `SearchController` (permission-gated, DB-agnostic).
- **Seeder** `ApdSeeder` (demo: helm, sepatu, sarung tangan + stock) registered in `DatabaseSeeder`.

### Frontend
- `resources/js/Pages/Modules/Apd/Catalog/{Index,CreateOrEdit,Show}.tsx`
- `resources/js/Pages/Modules/Apd/Items/{Index,CreateOrReceive,Show}.tsx`
- Menu "APD / PPE" added to `navConfig.ts` (operasional group, after Asset).

### Tests
- `tests/Feature/Modules/Apd/ApdPhaseATest.php` — 8 tests, all passing
  (route registration, global catalog visibility, permission block, auto-numbering,
  item receive lifecycle defaults, serial-required validation, item org scope).
- `NavigationConfigurationTest` updated to read `navConfig.ts` + assert APD entry.
- `npm run build` ✅ green.

## Design decisions (confirmed)
- **Catalog = global master** (any `apd.view` sees all); **Item = org-scoped** (fail-closed).
- `track_type` serial (qty=1, serial wajib) / batch (qty>1, no serial).
- Receive computes `next_inspection_date` (catalog.inspection_interval_days) and
  `expiry_date` (catalog.default_lifespan_months) from received_date.
- Holder polymorphic (employee/contractor/location) reserved for Phase B issuance.

## Deploy evidence (ubuntu-5)
- `git pull` ✅ · `npm ci && npm run build` ✅ · `migrate --force` ✅ (2 new tables)
- `php8.3-fpm` restarted ✅ · `qhsse-queue.service` restarted ✅
- `/login` → 200 · `route('apd.catalogs.index')` → `http://18.192.98.211/apd/catalogs` ✅
- 13 APD routes registered.

## Next (Phase B)
Issuance + Workflow Core (request → approve → issue → return/dispose), holder assignment,
`apd.issue/approve/request` enforcement, issuance history UI.

## Known limitations (Phase A)
- No file upload/photo yet (Phase C inspection photos).
- No issuance/return/dispose lifecycle (Phase B).
- Dashboard widgets for APD not yet added (Phase D).
