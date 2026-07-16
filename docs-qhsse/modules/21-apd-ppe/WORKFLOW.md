# Workflow — APD / PPE Management

> Phase 21. Menggunakan Workflow Core (shared `workflow_histories`) untuk issuance, dan pola scheduled job untuk inspeksi/expiry.

---

## 1. Issuance Lifecycle (Workflow Core)

Status: `draft → requested → approved → issued → returned|disposed|rejected`

```
        ┌─────────────┐
        │    draft    │  (admin/QHSSE buat langsung)
        └──────┬──────┘
               │ (langsung issue)
               ▼
        ┌─────────────┐       ┌──────────────┐
        │   issued    │◄──────│   approved    │
        └──────┬──────┘       └──────┬───────┘
               │                     │ (approve, perm apd.approve)
               │                     ▲
               │              ┌──────┴───────┐
               │              │  requested   │  (request, perm apd.request)
               │              └──────────────┘
               │
        ┌──────┴───────┐
        │  returned    │  (perm apd.issue, kembali ke stok)
        └──────────────┘
               │
        ┌──────┴───────┐
        │  disposed    │  (rusak/expired, perm apd.issue)
        └──────────────┘

   requested ──(reject)──► rejected
```

### Transisi & Permission
| From → To | Actor | Permission | Efek Stok |
|---|---|---|---|
| draft → issued | QHSSE Officer | `apd.issue` | serial: status `available`→`in_use`, set `current_holder`; batch: `available_quantity -= qty` |
| (any) → requested | Supervisor/Employee/Contractor | `apd.request` | tidak ubah stok |
| requested → approved | Dept Head/Supervisor | `apd.approve` | tidak ubah stok |
| approved → issued | QHSSE Officer | `apd.issue` | consume stok (lihat draft→issued) |
| issued → returned | QHSSE Officer | `apd.issue` | serial: `in_use`→`available` (atau `damaged`/`expired`); batch: `available_quantity += qty` |
| issued → disposed | QHSSE Officer | `apd.issue` | serial: status `disposed`; batch: `available_quantity -= qty` (seluruh sisa jika lot) |
| requested → rejected | Dept Head/Supervisor | `apd.approve` | — |

Setiap transisi dicatat di `workflow_histories` (`module_name='apd'`, `reference_id=issuance.id`) + `activity_logs`.

---

## 2. Receive Stok (tanpa workflow)

1. User (`apd.create`) buka form "Terima Stok" untuk sebuah katalog.
2. Pilih `track_type`:
   - `serial`: input `serial_number` (bisa multi baris → banyak row), `received_date`, `expiry_date` (opsional), `next_inspection_date` (dihitung `received_date + default_lifespan_months`).
   - `batch`: input `quantity`, `lot_number`, `received_date`, `expiry_date`.
3. Simpan → `apd_items` (status `available`), audit `item.received`.
4. Jika `available_quantity` (batch) < `min_stock` katalog → notifikasi `apd.low_stock`.

---

## 3. Inspeksi APD

### 3.1 Scheduled (otomatis)
- Saat serial di-issue: `next_inspection_date = issue_date + catalog.default_lifespan_months` bulan.
- Scheduler harian: item dengan `next_inspection_date < now()` & status `in_use`/`available` → flag overdue + notifikasi `apd.inspection_overdue`.

### 3.2 Incidental / Manual
- QHSSE Officer (`apd.inspect`) buat inspeksi: pilih item serial, `inspection_type` (`incidental`/`manual`), `result`, `condition`, foto (collection `inspection`).
- Jika `result='tidak_layak'` → item status `damaged`; wajib return/dispose.
- Update `next_inspection_date` (jika scheduled lulus).

### 3.3 Expiry
- Scheduler harian: `expiry_date < now()` → status `expired` + notifikasi `apd.expired`.

---

## 4. Integrasi ke Modul Lain

### 4.1 Risk Register → APD Wajib
- Di Show Risk Register, ada panel "APD Wajib" diisi via `risk_apd_requirements` (link ke `apd_catalogs`).
- Saat buat issuance, sistem rekomendasikan katalog dari hazard terkait pemegang (optional helper).

### 4.2 Incident Reporting
- Field di `incident_reports` (modul 01): `ppe_involved` (boolean), `ppe_id` (FK→apd_items, nullable), `ppe_failure` (boolean).
- Jika `ppe_failure = true` → tombol "Buat CAPA" di Show Incident.
- Show APD Item menampilkan incident terkait.

### 4.3 Inspection (modul 02)
- Temuan inspeksi dengan `type='ppe_not_used'` → escalate ke CAPA (`capa.actions`) via tombol di Show Inspection.

### 4.4 Training (modul 08)
- TrainingRecord `type='ppe_fit_test'` memiliki `apd_item_id` (FK→apd_items, respirator serial).
- Show APD Item menampilkan training fit-test terkait.

### 4.5 Search
- `SearchController::modules()` tambah:
  - `apd_items` → label "APD / Inventori", route `apd.items.index`, permission `apd.view`, kolom `serial_number`,`location_note`,`condition`.
  - `apd_issuances` → label "APD / Penugasan", route `apd.issuances.index`, permission `apd.view`, kolom `issue_number`,`notes`.

---

## 5. Dashboard Widget Flow

Di `Dashboard` (shell existing), tambah section "APD":
1. Query stok rendah (batch `available_quantity < min_stock` + katalog di bawah ambang).
2. Query overdue inspeksi / kadaluarsa (`next_inspection_date < now()` OR `expiry_date < now()`).
3. Compliance per site: hitung Employee per site yang seharusnya pegang APD wajib (dari `risk_apd_requirements` via departemen/hazard) vs yang sudah `issued`.
4. Top hazard: `risk_apd_requirements` group by `risk_register_id`.

Widget hanya tampil jika user punya `apd.view`.

---

## 6. Notification Scheduler

`App\Console\Commands\ApdScheduler` (atau trait di `Kernel`):
- Setiap hari 07:00:
  - `apd.inspection_overdue` → QHSSE Officer site terkait.
  - `apd.expired` → QHSSE Officer site terkait.
  - `apd.low_stock` → QHSSE Officer (jika stok di bawah ambang).

---

## 7. Permission Gating (server-side)

Semua endpoint Issuance/Inspection/Catalog di-gate via Policy:
- `viewAny`/`view`: `apd.view`
- `create`: `apd.create`
- `update`: `apd.update`
- `delete`: `apd.delete` (Admin)
- `issue`: `apd.issue`
- `inspect`: `apd.inspect`
- `approve`: `apd.approve`
- `request`: `apd.request`
- export: `apd.export`

UI hanya menyembunyikan tombol; otorisasi selalu di backend.
