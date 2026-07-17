# Spec: Inspeksi Multi-Unit (Mode Sesi)

Status: DRAFT (2026-07-17) — menunggu review user.
Intent: docs-qhsse/intent/inspection-multi-unit.md (CONFIRMED).

## 1. Objective

Memungkinkan satu "Inspeksi" menampung **banyak unit fisik** (misal 94 wire rope
sling) dalam 1 sesi, bukan 94 form terpisah. Tiap unit punya **hasil checklist
sendiri** (ketertelusuran per-unit).

Kebutuhan dari wawancara:
- Dua mode tetap didukung: inspeksi **1 unit** (sesi berisi 1 unit) DAN sesi **>1 unit**.
- Daftar unit dibuat **per sesi** (bukan master Asset global, bukan per-template).
- User input list unit (bisa 94) lalu pilih ke inspeksi via **dropdown SEARCHABLE + MULTI-SELECT**.
- Eksekusi **satu unit per halaman**: pilih unit → isi checklist → "Simpan Hasil".
  Unit tersimpan dapat **tanda ✓** di dropdown.
- Tombol "Selesaikan Inspeksi" **AKTIF hanya jika semua unit berstatus `done` ATAU `cancelled`**.
  Unit terlewat harus diklik **"Cancel Inspeksi"** agar tidak memblokir penyelesaian.
- User: inspector, QHSSE, **operator**, **foreman** (perlu role/permission).

User stories:
- Sebagai foreman, saya bisa buat 1 inspeksi sling berisi 94 unit dari daftar yang
  saya ketik, lalu inspeksi jalan per-unit tanpa 94 form.
- Sebagai inspector, saya bisa tandai unit rusak (`is_unsafe`) dan foto tiap unit.
- Sebagai QHSSE, saya hanya bisa "Selesaikan" kalau semua unit sudah dihandle
  (done/cancelled) — tidak ada unit terlewat.

## 2. Tech Stack

Sesuai project (AGENTS.md / SOUL.md):
- Laravel 12 + Inertia React + TypeScript + Tailwind CSS
- Spatie Laravel Permission (server-side authorization)
- SQLite (local test) / PostgreSQL (prod)
- Core services: NumberingService, WorkflowService, AuditService, ActivityService,
  ManagedFileService (upload foto), ListQuery, CsvExporter

## 3. Commands

```
Build:        npm run build
Test (PHP):   php artisan test  (atau: make test)
Test module:  php artisan test tests/Feature/Modules/Inspection/InspectionTest.php
Lint/style:   ikut konvensi repo (PSR-12 PHP, Prettier/ESLint TS)
Migrate:      php artisan migrate --force
Seed perm:    php artisan db:seed --class=RolesAndPermissionsSeeder
```

## 4. Project Structure

```
database/migrations/
  2026_07_17_130000_create_inspection_units_table.php
  2026_07_17_130100_add_inspection_unit_id_to_results.php   (backfill legacy)
app/Models/Modules/Inspection/
  Inspection.php            (relasi units())
  InspectionUnit.php        (BARU)
  InspectionResult.php      (tambah inspection_unit_id + relasi unit())
app/Http/Requests/Modules/Inspection/
  StoreInspectionRequest.php     (tambah validasi units[])
  SaveInspectionUnitResultRequest.php (BARU: per-unit result)
  CancelInspectionUnitRequest.php     (BARU: reason)
app/Http/Controllers/Modules/Inspection/
  InspectionController.php       (store buat units; show per-unit; unitResult; cancelUnit; complete guard)
resources/js/Pages/Modules/Inspection/
  Form.tsx     (tambah section Daftar Unit: build list + multi-select searchable)
  Show.tsx     (ubah jadi unit-per-page + dropdown tanda + tombol selesaikan terkunci)
resources/js/Components/
  SearchableMultiSelect.tsx  (BARU: reusable dropdown searchable multi-select)
tests/Feature/Modules/Inspection/
  InspectionTest.php  (tambah test multi-unit)
database/seeders/
  CorePermissions.php  (tambah role foreman/operator ke inspectionFull/inspectionView bila perlu)
```

## 5. Code Style

Ikuti pola existing controller (dependency injection core services di constructor,
`DB::transaction`, `Inertia::render`, server-side permission via route middleware
`permission:inspection.checklists.execute`). Frontend pakai `useForm` Inertia dengan
data di-state `useForm` (bukan argumen kedua ke `post/put` — lihat bug sebelumnya).
Contoh relasi model (Laravel):

```php
// InspectionUnit.php
class InspectionUnit extends Model {
    protected $fillable = ['inspection_id','identifier','status','notes','cancelled_reason'];
    public function inspection(): BelongsTo { return $this->belongsTo(Inspection::class); }
    public function results(): HasMany { return $this->hasMany(InspectionResult::class); }
}
```

## 6. Testing Strategy

- Pest feature tests di `tests/Feature/Modules/Inspection/InspectionTest.php`.
- Level: integration (HTTP request → DB assertion).
- Wajib cover:
  - Buat inspeksi dengan N unit → N rows di `inspection_units`.
  - Buat inspeksi TANPA unit → otomatis 1 default unit (mode single).
  - Simpan hasil 1 unit → unit status `done`, results terisi per-item.
  - Cancel 1 unit → status `cancelled`, tidak memblokir complete.
  - Complete DITOLAK (409) jika ada unit `pending`.
  - Complete BERHASIL jika semua `done`/`cancelled`.
  - Permission: role tanpa `inspection.checklists.execute` ditolak (403).
- Build `npm run build` harus hijau.

## 7. Data Model (Detail)

### Tabel BARU: `inspection_units`
```
id              bigint PK
inspection_id   FK -> inspections (cascade)
identifier      string  (misal "Sling-01", bebas diketik)
status          string  default 'pending'  -- pending | done | cancelled
notes           text nullable
cancelled_reason text nullable
created_at / updated_at
index(inspection_id), index(status)
```

### Tabel UBAH: `inspection_results`
```
+tambah: inspection_unit_id  FK -> inspection_units (nullable di migrasi awal, lalu backfill)
Unique jadi: (inspection_id, inspection_unit_id, inspection_item_id)
  -> untuk backfill legacy: buat 1 unit default per inspection, set results.unit_id,
     lalu ALTER set NOT NULL (di migrasi terpisah / batch).
```
Relasi `InspectionResult`: `unit()` BelongsTo InspectionUnit.

### Behavior `store` (InspectionController)
- Terima `units` = array identifier (bisa kosong).
- Jika `units` berisi: buat 1 `InspectionUnit` per identifier (status `pending`).
- Jika kosong: buat 1 `InspectionUnit` default (identifier = inspection_number,
  misal "UNIT-001" / "Tunggal") → menjaga mode single-unit tetap jalan.
- `inspection_results` TIDAK dibuat di store (dibuat saat simpan per-unit, seperti sekarang).

### Endpoint BARU / UBAH
- `PUT /inspections/{inspection}/units/{unit}` → simpan hasil checklist 1 unit
  (items.answer/remark/is_unsafe/photo). Set `unit.status='done'`.
  Middleware `permission:inspection.checklists.execute`.
- `POST /inspections/{inspection}/units/{unit}/cancel` → set `status='cancelled'`
  + `cancelled_reason` (wajib). Middleware `permission:inspection.checklists.execute`.
- `complete` (existing) → TAMBAH guard: abort 409 jika masih ada unit `pending`.

### `show` (InspectionController)
- Load `units` (dengan `results.photoFile`), `template.items`, site/area/inspector.
- Kirim ke Inertia: `inspection`, `units` (tiap unit berisi status + results),
  `files`.

## 8. Frontend

### Form.tsx (buat inspeksi) — section "Daftar Unit"
1. **Build list**: textarea "Tempel daftar (satu per baris)" + tombol "Tambah",
   atau input + Enter. Hasil jadi chips `availableUnits[]`.
2. **Multi-select searchable**: `SearchableMultiSelect` mengambil opsi dari
   `availableUnits`, user pilih subset → `selectedUnits[]` (ini yang jadi inspeksi).
   (Jika user tidak pilih apa-apa, dianggap semua `availableUnits` ikut — atau
   butuh minimal 1; akan dikonfirmasi di open question.)
3. Submit: `post(route, { ...data, units: selectedUnits })`.

### Show.tsx (eksekusi) — unit-per-page
- Dropdown pilih unit: tiap opsi menampilkan identifier + badge
  ✓ done / ✗ cancelled / ● pending.
- Saat unit dipilih: render `template.items` sebagai form (yes_no / yes_no_na /
  safe_unsafe / na / scale / text / photo) — SAMA seperti sekarang, tapi hasil
  diikat ke `unit.results`.
- Tombol "Simpan Hasil" → `put` endpoint unit result → unit jadi done (dropdown update tanda).
- Tombol "Cancel Inspeksi" per unit → prompt alasan → unit cancelled.
- Tombol "Selesaikan Inspeksi" → `disabled` selama ada unit `pending`;
  jika semua done/cancelled → enabled (panggil `complete` existing).

### SearchableMultiSelect.tsx (reusable)
- Props: `options: {value,label}[]`, `value: string[]`, `onChange`, `placeholder`.
- Input text filter + checkbox list / tag. Aksesibel.

## 9. Permissions & Roles

- Isi hasil & cancel & selesaikan → `inspection.checklists.execute` (sudah ada).
- Lihat → `inspection.checklists.view` (sudah ada).
- **Role "Foreman" & "Operator"**: belum ada di `CorePermissions::roleMap()`.
  Implementasi: tambahkan role ini dan beri `inspectionFull` (atau subset
  view+create+execute) agar foreman/operator bisa jalan inspeksi. (Konfirmasi
  di open question: apakah foreman/operator butuh create template juga, atau
  hanya execute inspeksi.)

## 10. Boundaries

- ALWAYS: server-side authorization (route middleware + policy bila perlu),
  validasi input, file pakai ManagedFileService (private), audit trail di
  create/update/cancel/complete, `npm run build` + test hijau sebelum claim done.
- ASK FIRST: perubahan schema (`inspection_units`, alter `inspection_results`),
  penambahan role (Foreman/Operator), resource frontend baru.
- NEVER: commit `.env`, file public tanpa auth, UI-only permission, hapus test.

## 11. Success Criteria (testable)

1. `php artisan test` InspectionTest: semua test multi-unit hijau (lihat §6).
2. `npm run build` sukses.
3. Di prod: buat inspeksi dengan 94 unit via paste → 94 rows `inspection_units`.
4. Eksekusi per-unit: simpan hasil → tanda ✓ muncul; cancel → tanda ✗.
5. "Selesaikan" terkunci sampai semua unit done/cancelled.
6. Role Foreman & Operator bisa akses buat/eksekusi inspeksi (403 jika tidak punya permission).

## 12. Assumptions (harus dikoreksi bila salah)

1. Identifier unit diketik bebas (string), tidak link ke master Asset.
2. Mode single-unit lama tetap kompatibel via 1 default unit (backfill migrasi).
3. Permission execute cukup untuk isi/cancel/selesaikan (tidak perlu permission baru).
4. Foto per-unit pakai mekanisme `ManagedFileService` yang sudah ada (collection `inspection_result`).
5. `complete` guard pakai status unit; `overall_result` dihitung dari `is_unsafe` di results (logika existing dipertahankan).

## 13. Open Questions — RESOLVED (2026-07-17)

1. **Multi-select kosong** → **TOLAK** (wajib pilih ≥1 unit). Validasi: `units` minimal 1.
2. **Role Foreman/Operator** → hanya **create + execute inspeksi** (tidak buat template).
   Di seeder: beri `inspectionView + inspectionCreate + inspectionExecute`
   (alias `inspection.checklists.view/create/execute`), tanpa `update/delete/export` admin.
3. **Single-unit legacy** → **1 default unit** (UI sama). Disetujui.
4. **Export per-unit** → **YA**, diperlukan. `export()` controller tambah opsi ekspor
   hasil per-unit (1 baris per unit, kolom = identifier + tiap item checklist + status).

### Resolved assumptions (override §12 bila berbeda)
- `units` wajib ≥1 (tidak boleh kosong di form).
- Foreman/Operator: `view + create + execute` inspection saja.
- Export per-unit masuk scope implementasi.
