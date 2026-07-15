# DEBUG-MODULE16-WS1-EXECUTION.md — Eksekusi WS-1: Contractor Prequalification + Evaluation

**Tanggal:** 2026-07-15
**Modul:** 16 (Contractor Management / `16-contractor-management`)
**Workstream:** WS-1 (Priority 🔴🔴 — fitur prequalification + evaluation HILANG TOTAL)
**Metode:** systematic-debugging (Iron Law: root-cause evidence before fix)

---

## Root-Cause Evidence (sebelum fix)

| Temuan | Bukti |
|--------|-------|
| Controller CRUD-only, TIDAK ada method transisi | `ContractorController` L1-305 hanya `index/create/store/show/edit/update/destroy/export`; grep `storeEvaluation\|setPrequalified\|revokePrequalified` = empty |
| Routes HANYA resource + export | `routes/modules/contractor.php` tidak ada `/prequalify`, `/evaluations` |
| **Model `ContractorEvaluation` TIDAK ADA** | `find app/Models -ipath "*ontractor*"` → hanya `Contractor.php` |
| **Field `is_prequalified`/`prequalified_until`/`safety_rating` TIDAK ADA di migration + model fillable** | grep migration = empty; model `$fillable` tidak punya |
| **Permission `contractor.management.evaluate` TIDAK ADA** | `CorePermissions` hanya view/create/update/delete/export/approve; DB `Permission::where('contractor.management.evaluate')->exists()` = NO |
| Policy TIDAK ada method `evaluate` | `ContractorPolicy` hanya view/create/update/delete/export/approve/suspend/activate/terminate |
| **TIDAK ADA Factory** (Contractor/Evaluation) | `find database/factories -ipath "*ontractor*"` = only migration + seeder |

**Kesimpulan:** WS-1 butuh skema baru (migration + model + factory) + permission + policy + 3 controller method + routes + tests. Sesuai WORKFLOW.md §3/§4/§7.

---

## Perubahan (delta)

### 1. Migration (WS-1a)
- `database/migrations/2026_07_15_120000_add_prequalification_to_contractors_table.php`
  → `contractors`: +`is_prequalified` (bool default false), +`prequalified_until` (date), +`safety_rating` (string nullable) + indexes.
- `database/migrations/2026_07_15_120001_create_contractor_evaluations_table.php`
  → tabel `contractor_evaluations`: contractor_id FK, evaluation_date, evaluator_id, criteria (json), total_score (int), result (string), notes, audit fields, softDeletes.

### 2. Model + Relasi + Factory (WS-1b)
- **BARU** `app/Models/Modules/Contractor/ContractorEvaluation.php` — `$fillable`, `criteria` cast array, `total_score` int, relasi `contractor`/`evaluator`, static `deriveResult()` (≥80 pass / ≥60 conditional / else fail).
- `Contractor.php`: +`is_prequalified`/`prequalified_until`/`safety_rating` ke `$fillable`; +`casts` (`is_prequalified` boolean, `prequalified_until` date); +relasi `evaluations()`.
- **BARU** `database/factories/Modules/Contractor/ContractorFactory.php` + `ContractorEvaluationFactory.php`.

### 3. Permission + Policy (WS-1c)
- `CorePermissions::all()`: +`contractor.management.evaluate`.
- `roleMap`: +evaluate ke `$contractorFull` + `$contractorCreate` (QHSSE Manager/Officer dapat evaluate).
- `ContractorPolicy`: +method `evaluate(User, Contractor)` → `can('contractor.management.evaluate')`.

### 4. Controller (WS-1d)
- Inject `AuditService` + `NotificationService` di constructor.
- **BARU** `storeEvaluation()` — authorize `evaluate`; hitung `total_score` (sum criteria), derive `result`, create evaluation (append-only, `evaluator_id` = actor), recalc `safety_rating` (avg 3 latest: ≥85 excellent / ≥70 good / ≥55 fair / else poor), audit `created` + `safety_rating_updated`, activity, notify `contractor.evaluated` → QHSSE Managers.
- **BARU** `setPrequalified()` — authorize `update`; set `is_prequalified=true` + `prequalified_until` (future date); audit `updated`, activity, notify `contractor.prequalified` → stakeholders (creator + QHSSE Mgr/Off).
- **BARU** `revokePrequalified()` — authorize `update`; guard sudah prequalified; set false + null; audit + activity.
- Private helpers: `calculateSafetyRating()`, `getQhsseManagers()`, `getContractorStakeholders()`.

### 5. Requests (WS-1d)
- **BARU** `StoreContractorEvaluationRequest` — `evaluation_date` (date ≤ today), `criteria` (array min 1, each int 0-100), `notes` nullable. authorize `contractor.management.evaluate`.
- **BARU** `UpdateContractorPrequalificationRequest` — `prequalified_until` (date > today). authorize `contractor.management.update`.

### 6. Routes (WS-1e)
- `POST /contractors/{contractor}/prequalify` → `setPrequalified` (`contractors.prequalify`).
- `DELETE /contractors/{contractor}/prequalify` → `revokePrequalified` (`contractors.prequalify.revoke`).
- `POST /contractors/{contractor}/evaluations` → `storeEvaluation` (`contractors.evaluations.store`).

### 7. Tests (WS-1f)
- **BARU** `tests/Feature/Modules/ContractorPrequalificationTest.php` — 7 tests / 20 assertions:
  - set prequalification (Officer) ✓
  - revoke prequalification ✓
  - reporter diblokir prequalify (403) ✓
  - revoke saat belum prequalified → error ✓
  - store evaluation + recalc safety_rating (excellent) ✓
  - mid score → conditional + fair rating ✓
  - reporter diblokir evaluate (403) ✓

---

## Bug Ditemukan Selama Eksekusi (honest notes)
1. **`is_prequalified` tidak di `$casts`** → Eloquent return `1` (int) bukan `true`; Pest `toBeTrue()` fail. Diperbaiki dengan menambah `'is_prequalified' => 'boolean'` + `'prequalified_until' => 'date'` ke `$casts`.
2. **`routes/modules/contractor.php` kehilangan `});` penutup group** setelah menambah route → Parse error. Diperbaiki.
3. **Policy `evaluate` patch menghapus signature `suspend`** → diperbaiki manual.
4. **LSP false-positive** (namespace `Modules\Contractor` vs `Models\Modules\Contractor`) — diabaikan; `php -l` sebagai sumber kebenaran.

---

## Verifikasi (fresh, real execution)

```
php -l (semua file WS-1)              → No syntax errors
php artisan migrate --force           → 2 migration DONE (local sqlite)
php artisan test --filter ContractorPrequalificationTest
                                   → 7 passed / 20 assertions
npm run build                         → ✓ built in 6.39s
```

---

## Status
✅ **WS-1 SELESAI & TERVERIFIKASI.** Fitur prequalification + evaluation kini functional: contractor bisa di-prequalify/revoke + dievaluasi (append-only) dengan safety_rating terhitung otomatis, audit + activity + notif sesuai WORKFLOW.md §3/§4/§7.

## Sisa WS Modul 16 (lihat plan)
- WS-2 notif (registered/expiring) — sebagian sudah (evaluated/prequalified via WS-1)
- WS-3 command expiry `CheckExpiringPrequalification` + schedule (§3)
- WS-4 scope `core.scope.*` (index/export bocor)
- WS-5 audit store/update + transition guard (blacklisted→active Admin only)
- WS-6 destroy authorize
- WS-7/WS-8 tests CRUD + frontend
