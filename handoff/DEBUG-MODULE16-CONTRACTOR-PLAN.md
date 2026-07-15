# DEBUG-MODULE16-CONTRACTOR-PLAN.md — Debug Mendalam Modul 16 (Contractor Management)

**Tanggal:** 2026-07-15
**Modul:** `16-contractor-management` (Phase 16 — Contractor + Prequalification + Evaluation)
**Controller:** `App\Http\Controllers\Modules\Contractor\ContractorController`
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴🔴 **CRITICAL — docs 677 lines tapi ZERO tests + controller CRUD DOANG: prequalification + evaluation HILANG TOTAL. Plus notif silent, command expiry hilang, scope bocor, tidak audit.**
**Progres Eksekusi:** ✅ **WS-1 SELESAI** (2026-07-15) — prequalification + evaluation; lihat `handoff/DEBUG-MODULE16-WS1-EXECUTION.md`. ✅ **WS-3 SELESAI** (2026-07-15) — command + schedule; lihat `handoff/DEBUG-MODULE16-WS3-EXECUTION.md`. ✅ **WS-4 SELESAI** (2026-07-15); ✅ **WS-5 SELESAI** (2026-07-15); ✅ **WS-6 SELESAI** (2026-07-15); ✅ **WS-2 SELESAI** (2026-07-15) — lihat `handoff/DEBUG-MODULE16-WS4-WS5-EXECUTION.md` + `handoff/DEBUG-MODULE16-WS6-WS2-EXECUTION.md`. ✅ **WS-7 SELESAI** (2026-07-15) — tests; ✅ **WS-8 SELESAI** (2026-07-15) — build green; lihat `handoff/DEBUG-MODULE16-WS7-WS8-EXECUTION.md`. **MODUL 16 FULLY COMPLETE (WS-1..8).**

---

## 0. Konteks & Bukti Segar

Modul 16 (Contractor) **punya docs sangat lengkap** (WORKFLOW.md 677 lines: lifecycle status, prequalification
flow, evaluation flow, audit trail, notification events, full controller examples). TAPI:
- **ZERO tests** (`find tests -ipath "*ontractor*"` = empty).
- Controller `ContractorController` (305 lines) **HANYA CRUD**: `index/create/store/show/edit/update/destroy/export`.
- **TIDAK ADA** `storeEvaluation` (§4/§7), `setPrequalified` (§3/§7), `revokePrequalified` (§3/§7).
- **TIDAK ADA** command expiry (§3).

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G2 | 🔴🔴 **Fitur prequalification + evaluation HILANG** | Controller L1-305 hanya CRUD; `storeEvaluation`/`setPrequalified`/`revokePrequalified` TIDAK ADA | §3/§4/§7: 3 method + routes (POST /prequalify, DELETE /prequalify, POST /evaluations) |
| G1 | 🔴 **NOTIF SILENT** | `grep NotificationService` Contractor = **empty**; store/update/destroy tdk notif | §6: `contractor.registered`/`evaluated`/`prequalified`/`expiring_soon` |
| G3 | 🔴 **Command expiry HILANG** | `find app/Console -iname "*ontractor*"` = empty; schedule hanya `documents:check-expiry` | §3: `CheckExpiringPrequalification` + daily 08:00 |
| G4 | 🔴 **Scope BOCOR** | `index` L28-101 / `export` L223-304 TIDAK scope sama sekali | Semua user lihat semua contractor (celah authz) |
| G5 | 🟡 **`store` tidak audit** | L140 hanya `activityService->log`; tdk `auditService->created` | §5: Create = Audit + Activity |
| G6 | 🔴 **`update` tidak audit + tidak log status change** | L177-203 hanya `activityService->log`; tdk `auditService->updated`; tdk `contractor.status_changed` | §5: Update = Audit; status change = audit event |
| G7 | 🟡 **`destroy` tidak authorize** | L205-221 langsung delete; tdk `$this->authorize('delete', $contractor)` | Semua modul lain authorize tiap method |
| G8 | 🔴 **`update` tidak cek transition rules** | L177-191 langsung update `contract_status`; tdk guard blacklisted→active (Admin only) | §2: blacklisted→active hanya Admin |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `ContractorController` (`app/Http/Controllers/Modules/Contractor/`) — **CRUD only** |
| Model | `Contractor` (`app/Models/Modules/Contractor/`) — `authorized_sites` JSON, `contract_status`, `approval_status`, `is_prequalified`, `prequalified_until`, `safety_rating` |
| Policy | `ContractorPolicy` (`app/Policies/Modules/Contractor/`) — perlu cek `evaluate`/`update` scope |
| Requests | `StoreContractorRequest`, `UpdateContractorRequest` (perlu `StoreContractorEvaluationRequest`, `UpdateContractorPrequalificationRequest`) |
| Routes | `routes/modules/contractor.php` (require L233) — **hanya resource + export**; perlu tambah `evaluations`, `prequalify` |
| Tests | **NONE** — `tests/Feature/Modules/Contractor/` kosong |
| Docs | `docs-qhsse/modules/16-contractor-management/` (✅ lengkap 677 lines) |
| Command | `app/Console/Commands/CheckExpiringPrequalification.php` → **HILANG** |

---

## 2. Workstream

### WS-1: Implementasi fitur prequalification + evaluation (G2)  🔴🔴 CRITICAL — ✅ SELESAI 2026-07-15
- **Bug:** 3 method HILANG → contractor tidak bisa di-prequalify / dievaluasi.
- **Fix:** migration (prequalification fields + tabel `contractor_evaluations`), model `ContractorEvaluation` + relasi, factory, permission `contractor.management.evaluate`, policy `evaluate`, 3 controller method (`storeEvaluation`/`setPrequalified`/`revokePrequalified`) + inject Audit/Notif, 3 routes, 7 tests PASS.
  1. Tambah routes: `POST /contractors/{contractor}/prequalify` (setPrequalified),
     `DELETE /contractors/{contractor}/prequalify` (revokePrequalified),
     `POST /contractors/{contractor}/evaluations` (storeEvaluation) sesuai WORKFLOW.md §7.
  2. Implementasi `setPrequalified`/`revokePrequalified`/`storeEvaluation` (hitung total_score,
     derive result, recalc safety_rating, audit, activity, notif) per §4/§7.
  3. Evaluation **append-only** (tidak ada edit/destroy) — sesuai §4 constraint.
- **Verifikasi:** prequalify + evaluation jalan; safety_rating terhitung.
- **DoD:** fitur jalan; test cover.

### WS-2: Implementasi notif (G1)  🔴 — ✅ SELESAI 2026-07-15
- **Fix:** inject `NotificationService`; notif `contractor.registered` (store), `contractor.evaluated`
  (storeEvaluation), `contractor.prequalified` (setPrequalified), `contractor.expiring_soon` (command).
- **DoD:** notif jalan; test cover.

### WS-3: Implementasi command expiry (G3)  🔴 — ✅ SELESAI 2026-07-15
- **Bug:** `CheckExpiringPrequalification` command + schedule HILANG → prequalification kedaluwarsa TANPA reminder.
- **Fix:** command baru (`contractor:check-prequalification-expiry`, notif `contractor.expiring_soon` ke QHSSE Mgr/Off + creator dalam 30 hari) + schedule `dailyAt('08:00')` di `routes/console.php`; 3 tests PASS.

### WS-4: Scope `core.scope.*` (G4)  🔴 — ✅ SELESAI 2026-07-15
- **Bug:** `index`/`export` TIDAK scope → celah authz (cross-site leak).
- **Fix:** `ContractorAccess` (mirror `AssetAccess`, via `authorized_sites` JSON) + inject di controller + `scope()` di `index`/`export` + `canView` di policy `view`; tests cover (WS-4 enforces `core.scope.*`).
- **DoD:** user `core.scope.site` hanya lihat contractor di site-nya; test cross-site block.

### WS-5: Audit + status change (G5/G6/G8)  🔴 — ✅ SELESAI 2026-07-15
- **Bug:** `store`/`update` TIDAK audit; guard blacklisted→active tidak ada; `contract_status` request tidak punya `blacklisted`.
- **Fix:** `store`→`auditService->created`; `update`→`auditService->updated` + `contractor.status_changed` log + guard (blacklisted→active hanya Admin/Super Admin); request +`'blacklisted'` ke `contract_status` in; tests cover.

### WS-6: `destroy` authorize (G7)  🟡 — ✅ SELESAI 2026-07-15
- **Bug:** `destroy` TIDAK `authorize('delete')` + TIDAK audit.
- **Fix:** + `authorize('delete', $contractor)` + `auditService->deleted`; test cover (WS-6).

### WS-7: Tests (G1-G8)  🔴🔴
- **Bug:** ZERO tests.
- **Fix:** buat `ContractorTest` (CRUD + scope + audit), `ContractorEvaluationTest` (WS-1/WS-2),
  `ContractorPrequalificationTest` (WS-1/WS-2), command test (WS-3). Smoke + permission + edge.
- **DoD:** suite PASS; coverage G1-G8.

### WS-8: Frontend  🟡
- **Debug:** Show render prequalification badge + evaluation history + safety_rating; Index scope
  filter. `npm run build` green.
- **DoD:** UI benar; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE9-ENVIRONMENT-PLAN.md G1:** no transitions / CRUD only — Contractor G2 SAMA (fitur
  utama HILANG: prequalification + evaluation). Ini level **CRITICAL non-functional**.
- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3 + DEBUG-MODULE11-COMMUNICATION-PLAN.md G1 + DEBUG-MODULE13-RISK-PLAN.md G1 + DEBUG-MODULE14-LEGAL-PLAN.md G1 + DEBUG-MODULE15-EMERGENCY-PLAN.md G1:** notif silent — Contractor G1 SAMA.
- **DEBUG-MODULE14-LEGAL-PLAN.md G2 (command HILANG):** Contractor G3 SAMA (expiry command hilang).
- **DEBUG-MODULE13-RISK-PLAN.md G2 / DEBUG-MODULE5-AUDIT-PLAN.md G5:** scope bocor — Contractor G4 SAMA.
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission/site, bukan hardcode role.
- **Decision Log:** "Fitur utama (prequalification/evaluation) wajib ada kalau WORKFLOW sebutkan;
  BANNED controller CRUD-only tanpa transition method; command scheduled wajib ada; semua status
  change audit + guard; notif via `NotificationService`."

---

## 4. Urutan Eksekusi

1. **WS-1** (prequalification + evaluation — CRITICAL) — fitur hilang.
2. **WS-7** (tests — ZERO coverage) — baseline safety.
3. **WS-2** (notif) — silent gap.
4. **WS-3** (command expiry) — compliance.
5. **WS-4** (scope) — authz.
6. **WS-5** (audit + transition guard) — integrity.
7. **WS-6/8** (destroy authorize, frontend).

---

## 5. Commands Verifikasi

```bash
# Cek fitur hilang
grep -rn "storeEvaluation\|setPrequalified\|revokePrequalified" app/Http/Controllers/Modules/Contractor/   # empty = HILANG
grep -n "prequalify\|evaluations" routes/modules/contractor.php   # empty = routes HILANG

# Cek notif + command
grep -rn "NotificationService" app/Http/Controllers/Modules/Contractor/   # empty = SILENT
find app/Console -iname "*ontractor*"   # empty = COMMAND MISSING

# Cek scope
grep -n "core.scope\|whereJsonContains('authorized_sites'" app/Http/Controllers/Modules/Contractor/ContractorController.php   # empty = SCOPE BOCOR

# Repro G4: index tanpa scope (user site A lihat contractor site B)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('QHSSE Officer');
\$c=App\Models\Modules\Contractor\Contractor::factory()->create();
\$ctrl=app(App\Http\Controllers\Modules\Contractor\ContractorController::class);
// index() tanpa filter → semua contractor tampil (SCOPE BOCOR)
"

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 16 Total)

- [x] WS-1: routes + `setPrequalified`/`revokePrequalified`/`storeEvaluation` (append-only); test cover. (✅ 2026-07-15)
- [x] WS-3: `CheckExpiringPrequalification` + schedule `dailyAt('08:00')`; test cover. (✅ 2026-07-15)
- [ ] WS-2: `NotificationService` inject + notif registered/evaluated/prequalified/expiring_soon; test cover. (evaluated/prequalified SUDAH di WS-1; expiring_soon di WS-3; sisa: registered)
- [ ] WS-7: `ContractorTest` + evaluation/prequalification/command tests; suite PASS.
- [x] WS-4: `ContractorAccess` + `scope()` di `index`/`export` + `view` policy; test cross-site block. (✅ 2026-07-15)
- [x] WS-5: `store`/`update` audit + `contractor.status_changed` + guard blacklisted→active Admin-only; request +`blacklisted`; test cover. (✅ 2026-07-15)
- [x] WS-6: `destroy` authorize + audit `deleted`; test cover. (✅ 2026-07-15)
- [x] WS-2: `store` notif `contractor.registered`; test cover. (✅ 2026-07-15)
- [ ] Cross-link Modul 9/4/11/13/14/15, Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 16 adalah **CRITICAL non-functional** seperti Modul 9: docs lengkap (677 lines) tapi
  controller **CRUD-only** — fitur inti (prequalification + evaluation) **TIDAK diimplementasikan**.
  Contractor tidak bisa di-prequalify atau dievaluasi → modul tidak berguna untuk tujuan QHSSE.
- **ZERO tests** — risiko tinggi saat eksekusi WS-1 (bisa break existing CRUD).
- **Notif silent** (G1) + **command expiry hilang** (G3) — sama Modul 14.
- **Scope bocor** (G4) — sama Modul 13 (index tidak scope).
- **Tidak audit** (G5/G6) + **tidak cek transition** (G8) — sama Modul 14/15.
- Ini modul ke-2 dari kategori "docs lengkap tapi implementasi CRUD-doang" setelah Modul 9.
  Pattern yang berulang: **WORKFLOW.md ditulis tapi controller tidak diimplementasikan sesuai docs**.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
