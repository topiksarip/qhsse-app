# HANDOFF: Inspeksi Multi-Unit (Mode Sesi)

Tanggal: 2026-07-17
Commit: 4d4c167 (develop) — deployed ke ubuntu-5
Spec: docs-qhsse/specs/inspection-multi-unit.md
Intent: docs-qhsse/intent/inspection-multi-unit.md
Plan: tasks/plan.md | Todo: tasks/todo.md

## Yang Ditambahkan
- Entitas baru `InspectionUnit` (tabel `inspection_units`): tiap inspeksi bisa punya
  banyak unit fisik. Status: pending | done | cancelled.
- `inspection_results` ditambah `inspection_unit_id` (hasil checklist melekat per-unit).
  Migrasi backfill: 1 default unit per inspection lama + link results.
- `StoreInspectionRequest`: `units[]` wajib min 1 (tolak kalau kosong).
- `InspectionController::store`: buat InspectionUnit per identifier.
- Endpoint baru:
  - `PUT /inspection/checklists/{inspection}/units/{unit}` — simpan hasil 1 unit → status done.
  - `POST /.../units/{unit}/cancel` — cancel unit (wajib cancelled_reason) → status cancelled.
  - `GET /.../{inspection}/export-units` — export CSV 1 baris per unit.
- `complete` guard: tolak (session error) kalau masih ada unit pending.
- `Inspection::canBeCompleted()` — true bila semua unit done/cancelled.
- Frontend:
  - `SearchableMultiSelect.tsx` (reusable, search + multi-select).
  - `Form.tsx`: section Daftar Unit (paste/typing + multi-select searchable, wajib ≥1).
  - `Show.tsx`: unit-per-page — dropdown pilih unit dgn badge ✓/✗/●, tombol Simpan
    Hasil, Cancel Inspeksi, Selesaikan (terkunci kalau ada pending), Export Unit.
- Role baru: **Foreman** & **Operator** (inspection.view + create + execute + export).

## Verifikasi
- `npm run build`: PASS
- `php artisan test` InspectionTest: 25 passed (78 assertions)
- Migrasi + backfill di prod: 4 inspections → 4 units, 0 unlinked results.
- Role Foreman & Operator terdaftar di prod.
- Login http://18.192.98.211/login → 200.

## Catatan Deployment
- Deploy: git pull → composer install --no-dev → npm ci → npm run build →
  migrate --force → optimize:clear → seed RolesAndPermissionsSeeder →
  restart php8.3-fpm + qhsse-queue.
- Migrasi backfill aman di DB besar (loop per inspection dalam up()).

## Known Issues / Deferred
- Export per-unit hanya CSV (belum Excel native) — sesuai CsvExporter ada.
- Single-unit legacy tetap jalan via 1 default unit (identifier = inspection_number).
- Belum ada master Asset individual global (di luar scope, daftar unit per-sesi).
- Tidak ada notifikasi otomatis saat unit dicancel (bisa ditambah nanti).

## Cara Uji Manual
1. Login sebagai QHSSE/Admin/Foreman.
2. Buat Inspeksi: isi template, site, inspector, jadwal, lalu di "Daftar Unit"
   tempel 94 baris (atau ketik), lalu pilih unit di dropdown searchable → Simpan.
3. Buka inspeksi → Mulai Inspeksi.
4. Pilih unit di dropdown, isi checklist, Simpan Hasil (unit dapat ✓).
5. Untuk unit yang tidak diinspeksi: Cancel Inspeksi (isi alasan).
6. Setelah semua done/cancelled → tombol Selesaikan aktif.
7. Export Unit → CSV 1 baris per unit.
