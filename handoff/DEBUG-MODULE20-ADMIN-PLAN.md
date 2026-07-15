# DEBUG-MODULE20-ADMIN-PLAN.md — Debug Mendalam Modul 20 (Admin & Master Data)

**Tanggal:** 2026-07-15
**Modul:** `20-admin-master-data` (Phase 20 — User Admin + Bulk Import + Admin Dashboard)
**Controller:** `App\Http\Controllers\Core\{UserAdminController, BulkImportController, AdminDashboardController}`
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Sehat — route `core.users.*` SUDAH gate `permission:` (bukan celah authz). Gap: UserAdmin TIDAK audit via AuditService, `destroy` tdk cek self-lock/admin-terakhir, ZERO test khusus UserAdminController.**

---

## 0. Konteks & Bukti Segar (Koreksi Penting)

Pemeriksaan awal menduga `UserAdminController` TIDAK `authorize()` = celah authz. **Bukan.**
Route `core.users.*` SUDAH di-gate di `routes/core.php`:
- L140 `permission:core.users.view` → index
- L142 `permission:core.users.create` → create/store
- L146 `permission:core.users.update` → edit/update
- L150-151 `permission:core.users.deactivate` → delete

Jadi controller TIDAK perlu `authorize()` (route-based gate). **Authz AMAN.**

`BulkImportController` SUDAH benar: `$request->user()->can($config['permission'])` + `abort_unless(...,403)`
(L42/L48/L65). Test `AdminToolingTest` L98 konfirmasi audit `bulk_import_completed` ADA.

`AdminDashboardController` route `permission:core.sites.view` (admin.php L13). AMAN.

**Gap riil (level polish):**

| # | Gap | Bukti | WORKFLOW.md §1 mensyaratkan |
|---|-----|------|--------------------------|
| G1 | 🟡 **UserAdmin TIDAK audit via AuditService** | `UserAdminController` L1-93 TIDAK inject `AuditService`/`ActivityService` (grep empty); `store`/`update`/`destroy` tdk catat | "Changes tracked via AuditService and ActivityService" |
| G2 | 🔴 **`destroy` TIDAK cek self-lock / admin-terakhir** | L87-92 `$user->update(['is_active'=>false])` tanpa cek `$user->id === auth()->id()` atau "admin terakhir" | Bisa mengunci diri / sistem kehilangan admin |
| G3 | 🟡 **ZERO test khusus UserAdminController** | `tests/Feature/Core/IdentityCoreTest.php`/`RbacCoreTest.php` cover RBAC umum; TIDAK ada test `store` audit + `destroy` self-lock | Smoke + permission + edge |
| G4 | 🟢 **`BulkImportController` SUDAH benar** | permission check + audit `bulk_import_completed` (test L98) | ✅ bukan gap |
| G5 | 🟢 **`AdminDashboardController` SUDAH benar** | route `permission:core.sites.view` | ✅ bukan gap |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `UserAdminController`, `BulkImportController`, `AdminDashboardController` (`app/Http/Controllers/Core/`) |
| Routes | `routes/core.php` (L139-152 users) + `routes/admin.php` (dashboard + import) — **SUDAH gate permission** ✅ |
| Tests | `tests/Feature/Core/AdminToolingTest.php` (import+dashboard) ✅; `IdentityCoreTest`/`RbacCoreTest`/`DashboardShellTest` (RBAC umum) |
| Docs | `docs-qhsse/modules/20-admin-master-data/` (WORKFLOW.md 9 lines: "No Workflow; audit via AuditService+ActivityService") |
| Policies | TIDAK ada (gate via route `permission:`) |

---

## 2. Workstream

### WS-1: Audit UserAdmin (G1)  🟡
- **Bug:** `store`/`update`/`destroy` TIDAK catat ke `audit_logs`.
- **Fix:** inject `AuditService` + `ActivityService`; panggil `auditService->created/updated/log`
  di store/update/destroy (event: `user.created`/`user.updated`/`user.deactivated`).
- **DoD:** audit_logs konsisten; test cover.

### WS-2: Self-lock / admin-terakhir guard (G2)  🔴
- **Bug:** `destroy` (L87-92) bisa nonaktifkan diri sendiri atau satu-satunya admin.
- **Fix:** di `destroy`, `abort_if($user->id === auth()->id(), 422, 'Tidak dapat menonaktifkan akun sendiri.')`;
  cek "jika user punya role Admin/Super Admin dan ini satu-satunya admin aktif → abort".
- **DoD:** self-lock + last-admin terblokir; test cover.

### WS-3: Tests UserAdmin (G3)  🟡
- **Bug:** ZERO test khusus `UserAdminController`.
- **Fix:** buat `UserAdminTest`: store (audit + permission), update (audit + role sync),
  destroy (self-lock 422 + last-admin 422 + deactivate sukses).
- **DoD:** coverage G1/G2; suite PASS.

### WS-4: Frontend  🟡
- **Debug:** Users/Index/Form render benar; `npm run build` green.
- **DoD:** UI benar; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE17-ASSET-PLAN.md G4/G5 + DEBUG-MODULE14-LEGAL-PLAN.md G4/G5 + DEBUG-MODULE19-REPORTING-PLAN.md G2/G3:** tidak audit via `AuditService` — Admin G1 SAMA.
- **DEBUG-MODULE2-CAPA-PLAN.md (403) + Modul 5/13 (celah authz):** Admin BUKAN celah — route SUDAH gate (beda dari Modul 2/5/13).
- **DEBUG-MODULE7-TRAINING-PLAN.md + Modul 8/16 (ZERO tests):** Admin G3 SAMA (tapi import SUDAH ada test).
- **Decision Log:** "Audit via `AuditService` wajib untuk semua critical op termasuk user management; route `permission:` gate = authz benar (controller tdk perlu `authorize()`)."

---

## 4. Urutan Eksekusi

1. **WS-2** (self-lock guard — CRITICAL safety) — cegah lockout.
2. **WS-1** (audit) — compliance.
3. **WS-3** (tests) — coverage.
4. **WS-4** (frontend).

---

## 5. Commands Verifikasi

```bash
# Cek audit di UserAdminController (harus empty = GAP)
grep -n "AuditService\|auditService\|ActivityService" app/Http/Controllers/Core/UserAdminController.php

# Cek route gate (harus ADA = authz aman)
grep -n "core.users" routes/core.php

# Cek self-lock guard (harus empty = GAP)
grep -n "auth()->id()\|last.*admin\|Super Admin" app/Http/Controllers/Core/UserAdminController.php

# Cek tests UserAdmin
grep -rln "UserAdminController\|core.users.store" tests/Feature/Core/

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 20 Total)

- [ ] WS-2: `destroy` cegah self-lock + last-admin; test cover.
- [ ] WS-1: `store`/`update`/`destroy` audit via `AuditService`; test cover.
- [ ] WS-3: `UserAdminTest` coverage G1/G2; suite PASS.
- [ ] WS-4: `npm run build` green; UI benar.
- [ ] Cross-link Modul 17/14/19/7/8/16, Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 20 **SEHAT** — tidak ada celah authz (route SUDAH gate `permission:`).
- `BulkImportController` SUDAH benar (permission + audit `bulk_import_completed`).
- `AdminDashboardController` SUDAH benar (route gate).
- **Satu gap riil**: `UserAdminController` TIDAK audit (G1) + `destroy` bisa lockout (G2).
- TIDAK ada test khusus UserAdmin (G3) — tapi import/dashboard SUDAH ada test.
- Ini modul terakhir yang di-plan. Setelah ini, **semua 12 modul rilis + 8 ekstra = 20 plan debug lengkap**.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
