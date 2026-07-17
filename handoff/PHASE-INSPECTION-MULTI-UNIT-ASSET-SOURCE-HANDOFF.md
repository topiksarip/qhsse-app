# HANDOFF: Inspeksi Multi-Unit — Revisi Sumber Daftar Unit (Asset Master)

Tanggal: 2026-07-17 (revisi)
Spec: docs-qhsse/specs/inspection-multi-unit.md §14
Intent: docs-qhsse/intent/inspection-multi-unit.md
Plan: tasks/plan.md | Todo: tasks/todo.md

## Perubahan (revisi dari 4d4c167)
User mengubah sumber **Daftar Unit** dari free-text per-sesi menjadi **master Asset/Alat**:

- Migrasi `2026_07_17_140000_add_asset_id_to_inspection_units.php`: tambah kolom
  `asset_id` (FK -> assets, nullOnDelete) di `inspection_units`.
- `InspectionUnit`: `asset_id` fillable + relasi `asset()` belongsTo Asset.
- `StoreInspectionRequest`: `asset_ids[]` wajib min 1, tiap `exists:assets,id`.
  (Field `units` dihapus dari validasi store.)
- `InspectionController::create`: kirim `assets` (active, id/asset_number/name/serial).
- `InspectionController::store`: buat 1 `InspectionUnit` per asset terpilih;
  `asset_id` = id, `identifier` = asset_number (fallback name).
- `Form.tsx`: section Daftar Unit kini murni `SearchableMultiSelect` terhadap daftar
  `assets` (`asset_number — name`), tanpa textarea/paste.
- `AssetFactory` BARU (database/factories/Modules/Asset/AssetFactory.php) untuk test.
- `InspectionTest`: 25 tests, pakai `asset_ids` (bukan `units`).
- Legacy (backfill awal) tetap `asset_id = null`, `identifier` bebas → tetap jalan.

## Verifikasi Lokal
- `php artisan migrate --force`: PASS (add asset_id)
- `php artisan test InspectionTest`: 25 passed (78 assertions)
- `npm run build`: PASS

## Catatan Deployment
- Deploy sama seperti sebelumnya; migration baru aman (ADD nullable column + FK).
- Prod `assets` saat ini 0 (belum di-seed). Daftar dropdown akan kosong sampai
  AssetSeeder dijalankan / asset dimasukkan. Legacy inspection tetap bisa dieksekusi.

## Known Issues / Deferred (update)
- Dropdown asset kosong di prod sampai ada data asset (seed/fill).
- Export per-unit tetap CSV.
- Tidak ada notifikasi cancel (deferred).
- Master Asset per-individual sudah terpenuhi lewat tabel `assets` (revisi ini).
