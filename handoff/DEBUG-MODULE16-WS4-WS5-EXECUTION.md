# DEBUG-MODULE16-WS4-WS5-EXECUTION.md — Eksekusi WS-4 (scope leak) + WS-5 (audit + transition guard)

**Tanggal:** 2026-07-15
**Modul:** 16 (Contractor Management)
**Workstream:** WS-4 (scope authz leak 🔴) + WS-5 (audit + transition guard 🔴)
**Metode:** systematic-debugging (Iron Law: root-cause evidence before fix)

---

## Root-Cause Evidence (sebelum fix)

| Temuan | Bukti |
|--------|-------|
| **WS-4:** `index()` + `export()` query TANPA scope | `Contractor::query()` di L38/L441 — semua user lihat SEMUA contractor (cross-site leak) |
| Model `Contractor` TIDAK punya `site_id` langsung | pakai `authorized_sites` JSON array |
| `ScopeService` global = **stub** return `true` (allow all) | `app/Core/Services/ScopeService.php` L34 — scope effektif no-op di seluruh app |
| Pattern `AssetAccess` ada | `app/Modules/Asset/AssetAccess.php` (scope via `core.scope.*` + `site_id`) — replikasi untuk Contractor |
| **WS-5:** `store()` TIDAK panggil `auditService->created` | L146 hanya `activityService->log` |
| `update()` TIDAK panggil `auditService->updated` + TIDAK log status change | L199 langsung `update()` |
| Transition guard blacklisted→active Admin only = **TIDAK ADA** | `update()` tidak cek |
| `contract_status` request TIDAK punya `blacklisted` | `Store`/`Update` Request `Rule::in([...])` tanpa `blacklisted` → inkonsisten dgn WORKFLOW §3 |

---

## Perubahan (delta)

### WS-4 — Scope enforcement
- **BARU** `app/Modules/Contractor/ContractorAccess.php` (mirror `AssetAccess`):
  - `scope($query, $user)`: `core.scope.all` → all; `core.scope.site/department/own` + `employee.site_id` → `whereJsonContains('authorized_sites', siteId)`; else `1=0`.
  - `canView($user, $contractor)`.
- Inject `ContractorAccess $access` di `ContractorController`.
- `index()`: `$query = $this->access->scope(Contractor::query()..., $request->user())`.
- `export()`: sama.
- `ContractorPolicy::view()`: + `app(ContractorAccess::class)->canView($user, $contractor)` setelah cek permission.

### WS-5 — Audit + transition guard
- `store()`: + `auditService->created($contractor, $user, 'contractor', $id)` (event `created`).
- `update()`:
  - Transition guard: `blacklisted → active` hanya `hasAnyRole(['Admin','Super Admin'])`, else `back()->withErrors`.
  - + `auditService->updated($contractor, $oldValues, $user, 'contractor', $id)` (event `updated`).
  - + `auditService->log('contractor.status_changed', ..., ['contract_status'=>$old], ['contract_status'=>$new], ...)` bila status berubah.
- `StoreContractorRequest` + `UpdateContractorRequest`: + `'blacklisted'` ke `contract_status` `Rule::in([...])` (fix inkonsistensi docs vs code).

### Tests
- **BARU** `tests/Feature/Modules/ContractorScopeAuditTest.php` — 6 tests / 29 assertions:
  - WS-4: direct `ContractorAccess::scope` excludes cross-site; index integration shows only same-site (scope.all sees all).
  - WS-5: `created` audit logged; `status_changed` + `updated` logged; blacklisted→active blocked for non-admin; allowed for admin.

---

## Verifikasi (fresh, real execution)

```
php -l (5 files)                                      → No syntax errors
php artisan test --filter Contractor                  → 20 passed / 79 assertions (WS-1+3+4+5)
npm run build                                         → ✓ built in 6.70s
```

---

## Status
✅ **WS-4 SELESAI & TERVERIFIKASI** (scope leak closed via `ContractorAccess`).
✅ **WS-5 SELESAI & TERVERIFIKASI** (audit create/update + status_change + transition guard).

## Sisa WS Modul 16
- WS-2 notif `registered` (evaluated/prequalified di WS-1, expiring_soon di WS-3)
- WS-6 destroy authorize (masih pakai activity only, tidak audit + tidak cek policy `delete` secara eksplisit — actually `authorizeResource` cover `delete`)
- WS-7/WS-8 tests CRUD + frontend
