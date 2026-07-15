# DEBUG-MODULE9-ENVIRONMENT-PLAN.md — Debug Mendalam Modul 9 (Environmental Management)

**Tanggal:** 2026-07-15
**Modul:** `10-environmental-management` (Phase 10 — simple status, TAPI TIDAK FUNCTIONAL)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴🔴 **CRITICAL: TIDAK ADA method transisi — record tidak bisa diproses. Plus hardcode role, scope manipulatif, no notif, no test.**
**Progres Eksekusi:** ✅ **WS-1 SELESAI** (2026-07-15) — lihat `DEBUG-MODULE9-WS1-EXECUTION.md`.
Transisi `recorded→investigated→action_open→closed` + direct close SUDAH functional + ter-test (7 test/18 assertions).
WS-2 (notif exceedance store) + WS-3/4 (scope/policy) + WS-5 (tests CRUD) + WS-6/7/8 (guard/UI) masih terbuka.

---

## 0. Konteks & Bukti Segar

- Modul 9 pakai **simple status** (tidak WorkflowService — sesuai WORKFLOW.md §1/§4).
- **TAPI controller HANYA punya `index/create/store/show/edit/update/export`** — TIDAK ADA
  `investigate`/`openAction`/`close` (method transisi). Routes (`routes/modules/environment.php`)
  juga HANYA CRUD/export. **Records dibuat tapi TIDAK BISA DIPROSES** (stuck `recorded` forever).
- Ini adalah **module paling tidak lengkap** dari semua yang di-review (lebih parah dari Modul 7/8).

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | **TIDAK ADA method transisi** | Controller L1-286 + route L1-17: hanya CRUD/export; `investigate`/`openAction`/`close` HILANG | §3/§7: transisi `recorded→investigated→action_open→closed` wajib |
| G2 | Tidak inject `NotificationService` | ctor L23-29 tidak inject; tidak ada `notify` | §7: notif `environment.investigated`/`closed`/`exceedance_detected` |
| G3 | Scope manipulatif `?scope=all` | `index` L38-48 / `export` L237-246 pakai `$request->input('scope','all')` TANPA cek `core.scope.all` | User tanpa `core.scope.all` bisa lihat lintas site |
| G4 | Policy hardcode role | `EnvironmentalRecordPolicy` `view` L28-48 / `update` L74 pakai `hasAnyRole([6 role])` | Antipattern sama Modul 7 G1 |
| G5 | `update()` tidak cek status (rely policy) | L192-228 langsung update; gated hanya via Policy `update` (status!=='closed') | §6: closed terminal; OK via policy TAPI inkonsisten dgn Modul 5/6 yang cek di controller |
| G6 | TIDAK ada feature test | `tests/Feature/Modules/Environment/` tidak ada | Zero coverage |
| G7 | Exceedance tidak notif QHSSE | `store` L152 hanya `activityService->log`, TIDAK `notifyMany` | §7: exceedance → notif QHSSE team |
| G8 | `show()` tidak `availableTransitions` | L167-179 tidak kirim transitions/abilities | UI tidak bisa render tombol aksi |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `EnvironmentalRecordController` (`app/Http/Controllers/Modules/Environment/`) |
| Model | `EnvironmentalRecord` (`app/Models/Modules/Environment/`) — ada `calculateExceedance()`, `capaAction()`, `reporter()` |
| Policy | `EnvironmentalRecordPolicy` (`app/Policies/Modules/Environment/`) |
| Requests | `StoreEnvironmentalRecordRequest`, `UpdateEnvironmentalRecordRequest` |
| Routes | `routes/modules/environment.php` (require di `routes/modules.php` L217) |
| Frontend | `resources/js/Pages/Modules/Environmental/{Index,Show,Form}.tsx` |
| Tests | `tests/Feature/Modules/Environment/` → **TIDAK ADA** |
| Spec | `docs-qhsse/modules/10-environmental-management/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |

---

## 2. Workstream

### WS-1: Implementasi method transisi (G1)  🔴🔴 CRITICAL — ✅ SELESAI 2026-07-15
- **Bug:** module tidak bisa diproses — record stuck `recorded`.
- **Fix:** tambah method `investigate`/`openAction`/`close` di controller (sesuai WORKFLOW.md §7):
  - `investigate`: `abort_if(status!=='recorded')`; set `investigated`; audit + activity + notif reporter.
  - `openAction`: `abort_if(status!=='investigated')`; buat/link CAPA (`source_module='environment'`);
    set `status='action_open'`, `capa_action_id`; audit + activity.
  - `close`: `validate(['reason'=>'required|min:10'])`; `abort_if(status==='closed')`; set `closed`;
    audit + activity + notif reporter + stakeholder.
  - Tambah route `POST /environment/records/{record}/investigate` dll di `routes/modules/environment.php`.
  - Policy: method `investigate`/`close` + static `getAvailableTransitions`.
  - **Catatan permission:** pakai `environment.records.approve` (ADA di CorePermissions) bukan
    `environment.records.investigate` (TIDAK ada). Lihat `DEBUG-MODULE9-WS1-EXECUTION.md`.
- **Verifikasi:** `recorded` → investigate → investigated → openAction → action_open → close → closed.
- **DoD:** ✅ full lifecycle jalan; 7 test/18 assertions PASS; `npm run build` green.
- **Deliverable:** `handoff/DEBUG-MODULE9-WS1-EXECUTION.md`.

### WS-2: Inject + kirim notif (G2/G7)  🔴
- **Bug:** ctor tidak inject `NotificationService`; exceedance/transisi silent.
- **Fix:** inject `NotificationService`; notif di `store` (exceedance→QHSSE), `investigate`, `close`
  (resolve recipient via `core.scope.*`, BUKAN hardcode role).
- **Verifikasi:** `core_notifications` terisi.
- **DoD:** notif jalan; test cover.

### WS-3: Scope `core.scope.*` bukan `?scope=` manipulatif (G3)  🔴
- **Bug:** `index`/`export` pakai `?scope=all` tanpa cek permission.
- **Fix:** ganti ke `visibleQuery` berbasis `core.scope.*` (seperti Modul 5/6/8 WS-4).
- **Verifikasi:** user `core.scope.site` + `?scope=all` → tetap site sendiri.
- **DoD:** scope aman; test cover.

### WS-4: Policy hardcode role → `core.scope.*` (G4)  🔴
- **Bug:** Policy `view`/`update` hardcode 6 role.
- **Fix:** ganti ke `core.scope.*` (sama Modul 7 WS-1). Atau setidaknya gunakan permission +
  scope, bukan nama role.
- **DoD:** policy tanpa hardcode role; test cover.

### WS-5: Feature tests (G6)  🔴
- **Bug:** zero coverage.
- **Fix:** `tests/Feature/Modules/Environment/EnvironmentalRecordTest.php`:
  - CRUD + permission + audit
  - lifecycle transisi (WS-1)
  - exceedance auto-calc + notif
  - scope enforcement
  - closed terminal (no edit)
- **DoD:** minimal 12 test; suite PASS.

### WS-6: `update()` cek status di controller (G5)  🟡
- **Bug:** rely policy; inkonsisten dgn Modul 5/6.
- **Fix:** `abort_if(status==='closed', 422)` di `update()` untuk defense-in-depth.
- **DoD:** update blocked saat closed; test cover.

### WS-7: `show()` kirim `availableTransitions` (G8)  🟡
- **Fix:** di `show()` tambah `availableTransitions` (dari `getAvailableTransitions(status)`) +
  `abilities` (permission-gated) untuk UI.
- **DoD:** UI render tombol aksi; test cover props.

### WS-8: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green).
- **Debug:** Show render `availableTransitions` + action buttons (investigate/openAction/close w/
  reason modal); Index scope filter; exceedance badge.
- **DoD:** `npm run build` green; UI sesuai spec.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE7-TRAINING-PLAN.md G1 + DEBUG-MODULE8-PERMIT-PLAN.md G6:** hardcode role / scope
  manual — Modul 9 PALING PARAH (G1 no transition + G3/G4).
- **DEBUG-MODULE4-INSPECTION-PLAN.md G2:** notif silent (G2) — Modul 9 SAMA.
- **DEBUG-MODULE8-PERMIT-PLAN.md WS-1:** Modul 8 punya transition tapi lemah; Modul 9 malah TIDAK
  ADA transition — harus diimplementasi penuh sesuai WORKFLOW.md §7.
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission, bukan hardcode.
- **Decision Log:** "Semua modul wajib punya method transisi sesuai spec; BANNED hardcode role;
  scope via `core.scope.*`."

---

## 4. Urutan Eksekusi

1. **WS-1** (transisi — CRITICAL) — module jadi functional.
2. **WS-2** (notif) — silent gap.
3. **WS-3** (scope) — authz.
4. **WS-4** (policy) — hardcode role.
5. **WS-5** (tests) — coverage.
6. **WS-6/7/8** — update guard, show props, frontend.

---

## 5. Commands Verifikasi

```bash
# Cek method transisi ada?
grep -rn "function investigate\|function openAction\|function close" app/Http/Controllers/Modules/Environment/
# (empty = CRITICAL GAP)

# Cek route transisi
grep -n "investigate\|openAction\|close" routes/modules/environment.php
# (empty = routes missing)

# Repro G3: scope manipulasi
curl -s 'http://localhost/environment/records?scope=all'  # tanpa core.scope.all lihat semua?

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 9 Total)

- [x] WS-1: method `investigate`/`openAction`/`close` + route; full lifecycle jalan; test cover. (✅ 2026-07-15)
- [ ] WS-2: notif exceedance + transisi via `NotificationService`; test cover. (transisi SUDAH notif; sisa: exceedance store)
- [ ] WS-3: scope `core.scope.*` (bukan `?scope=` manipulatif); test cover.
- [ ] WS-4: Policy tanpa hardcode role; test cover.
- [ ] WS-5: feature tests minimal 12; suite PASS.
- [ ] WS-6: `update()` blocked saat closed; test cover.
- [ ] WS-7: `show()` kirim `availableTransitions` + abilities; test props.
- [ ] WS-8: `npm run build` green; UI aksi benar.
- [ ] Cross-link Core/Master WS-6, Modul 7/8 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 9 adalah **module paling tidak lengkap** yang di-review: TIDAK ADA method transisi → record
  tidak bisa diproses sama sekali. Ini BUKAN sekadar gap, tapi **non-functional core feature**.
- G1 = blocker operasional tertinggi: Environmental Management tidak bisa digunakan untuk tujuan
  QHSSE (investigasi, CAPA, close).
- G3/G4 = authz: scope manipulatif + hardcode role (sama antipattern Modul 7).
- Berbeda dgn Modul 5/6 yang production-ready, Modul 9 butuh **implementasi transisi penuh** +
  perbaikan scope/notif.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
