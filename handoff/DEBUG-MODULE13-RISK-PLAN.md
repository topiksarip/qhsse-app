# DEBUG-MODULE13-RISK-PLAN.md — Debug Mendalam Modul 13 (Risk Management)

**Tanggal:** 2026-07-15
**Modul:** `13-risk-management` (Phase 13 — RiskRegister, HIRADC/JSA)
**Controller:** `App\Http\Controllers\Modules\RiskManagement\RiskRegisterController` (folder `RiskManagement`, bukan `Risk`)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Matang di transition/validation, tapi BERLUBANG di notif (silent) + scope (Policy TODO cross-site).**

---

## 0. Konteks & Bukti Segar

Modul 13 (Risk) **sangat matang secara struktur**:
- 5 transition methods: `assess`/`needsControls`/`implementControls`/`monitor`/`obsolete` (L253-467).
- `assess` validasi risk-level matrix (severity × probability → risk_level_id) L269-277 ✅.
- `implementControls` cek `additional_controls` tidak kosong L359-361 ✅.
- Audit + Activity per transition ✅.
- Test `RiskRegisterTest` ADA (535 lines) ✅.
- Docs lengkap (MODULE_SPEC/WORKFLOW/DATA_MODEL/TEST_CASES/API_CONTRACT/UI_PAGES) ✅.

TAPI baca kode nemukan **2 lubang kritis + 1 minor**:

| # | Gap | Bukti | Dampak |
|---|-----|------|--------|
| G1 | 🔴 **Notif SILENT** | `grep NotificationService` di `RiskManagement/` = **empty**; ctor L31-37 TIDAK inject | `assess`/`needsControls`/`obsolete` tidak kirim notif (WORKFLOW.md §8/§10 wajibkan) |
| G2 | 🔴 **Scope BOCOR + Policy TODO cross-site** | `index` L39-115 / `export` L469+ TIDAK scope; `RiskRegisterPolicy` L35/77 `// TODO: implement site scope check` — Policy hardcode role TANPA cek site | User `core.scope.site` lihat/edit SEMUA site (celah authz di level Policy) |
| G3 | 🟡 **`show()` tidak `availableActions`** | Model hanya `isObsolete`/`canBeAssessed` (L126/131); `getAvailableActions` (WORKFLOW.md §9) TIDAK ada; `show()` L173-192 kirim `riskRegister` tanpa abilities | UI tidak tahu tombol aksi mana yg boleh |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controller | `RiskRegisterController` (`app/Http/Controllers/Modules/RiskManagement/`) |
| Model | `RiskRegister` (`app/Models/Modules/RiskManagement/`) |
| Policy | `RiskRegisterPolicy` (`app/Policies/Modules/RiskManagement/`) — **TODO site scope** |
| Requests | `StoreRiskRegisterRequest`, `UpdateRiskRegisterRequest`, `AssessRiskRegisterRequest` |
| Routes | `routes/modules/risk.php` (require di `routes/modules.php` L226) |
| Tests | `tests/Feature/Modules/RiskManagement/RiskRegisterTest.php` (✅ 535 lines) |
| Docs | `docs-qhsse/modules/13-risk-management/` (✅ lengkap) |

---

## 2. Workstream

### WS-1: Inject + kirim notif (G1)  🔴
- **Bug:** ctor tidak inject `NotificationService`; transition tidak notif.
- **Fix:** inject `NotificationService`; notif di `assess` (QHSSE Manager/owner), `needsControls`,
  `obsolete` (recipient via site scope, BUKAN hardcode role — pakai `core.scope.*` resolver).
  Sesuai WORKFLOW.md §8/§10 (tapi ganti `User::role('QHSSE Manager')` ke permission/site query).
- **Verifikasi:** `core_notifications` terisi; test cover.
- **DoD:** notif jalan; test cover.

### WS-2: Scope `core.scope.*` + perbaiki Policy (G2)  🔴🔴 CRITICAL (authz)
- **Bug:** `index`/`export` tidak scope; `RiskRegisterPolicy` TODO site scope (hardcode role tanpa
  cek site) → celah cross-site.
- **Fix:**
  1. `index`/`export`: terapkan `visibleQuery` berbasis `core.scope.*` (site scope).
  2. `RiskRegisterPolicy::view`/`update`/`assess`: ganti `// TODO site scope` dgn cek
     `core.scope.all` → allow; else `employee?->site_id === $riskRegister->site_id`. Hapus
     hardcode role sebagai satu-satunya gate (atau pertahankan role + tambah site check).
- **Verifikasi:** user `core.scope.site` + site A TIDAK lihat/edit record site B.
- **DoD:** scope aman di list + Policy; test cover (cross-site block).

### WS-3: `show()` `availableActions` (G3)  🟡
- **Fix:** tambah `getAvailableActions()` ke model (WORKFLOW.md §9) atau kirim `abilities` di
  `show()` berdasar `canBeAssessed()`/`canNeedControls()`/etc + permission. Frontend render tombol.
- **DoD:** UI render tombol benar; test props.

### WS-4: Tests tambahan (G1/G2)  🔴
- **Bug:** test ada tapi mungkin belum cover notif + cross-site scope.
- **Fix:** tambah test notif terkirim (WS-1) + cross-site block (WS-2) ke `RiskRegisterTest`.
- **DoD:** coverage notif + authz; suite PASS.

### WS-5: Frontend  🟡
- **Debug:** Show render `availableActions`/abilities; Index scope filter (site dropdown default
  ke site user kalau `core.scope.site`).
- **DoD:** `npm run build` green; UI benar.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3:** notif silent + tidak audit transition — Risk SAMA
  (G1) tapi Risk SUDAH audit per transition (lebih baik dari Inspection).
- **DEBUG-MODULE7-TRAINING-PLAN.md G1 + DEBUG-MODULE10-SECURITY-PLAN.md G7:** hardcode role — Risk
  Policy hardcode role + **TODO site scope** (lebih parah: celah authz nyata).
- **DEBUG-MODULE5-AUDIT-PLAN.md G5:** transition TIDAK scope-guard — Risk Policy TODO = celah
  serupa (cross-site exec). Risk LEBIH parah karena Policy-nya explicit TODO.
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission/site, bukan hardcode role.
- **Decision Log:** "Policy DILARANG TODO site scope; semua Policy wajib cek `core.scope.*`;
  BANNED hardcode role sebagai satu-satunya gate; semua transition notif via `NotificationService`."

---

## 4. Urutan Eksekusi

1. **WS-2** (scope + Policy — CRITICAL authz) — tutup celah cross-site.
2. **WS-1** (notif) — silent gap.
3. **WS-4** (tests notif + cross-site) — coverage.
4. **WS-3/5** (show actions, frontend).

---

## 5. Commands Verifikasi

```bash
# Cek notif di Risk
grep -rn "NotificationService\|notifyMany" app/Http/Controllers/Modules/RiskManagement/
# (empty = SILENT confirmed)

# Cek Policy TODO
grep -n "TODO" app/Policies/Modules/RiskManagement/RiskRegisterPolicy.php
# L35, L77 = site scope TODO

# Repro G2: cross-site list bocor
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('QHSSE Officer');
\$emp=App\Models\Core\Users\Employee::factory()->forSite(App\Models\Core\MasterData\Site::factory()->create())->create();
\$u->employee_id=\$emp->id; \$u->save();
\$otherSite=App\Models\Core\MasterData\Site::factory()->create();
\$r=App\Models\Modules\RiskManagement\RiskRegister::factory()->create(['site_id'=>\$otherSite->id]);
\$c=app(App\Http\Controllers\Modules\RiskManagement\RiskRegisterController::class);
// index() tanpa filter site → user lihat record site lain (SCOPE BOCOR)
"

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 13 Total)

- [x] WS-2: `index`/`export` scope `core.scope.*`; `RiskRegisterPolicy` cek site (TODO resolved); test cross-site block. (✅ 2026-07-15)
- [ ] WS-1: `NotificationService` inject + notif di `assess`/`needsControls`/`obsolete`; test cover.
- [ ] WS-4: `RiskRegisterTest` tambah notif + authz; suite PASS.
- [ ] WS-3: model `getAvailableActions()` + `show()` kirim abilities; test props.
- [ ] WS-5: `npm run build` green; UI aksi + scope filter benar.
- [ ] Cross-link Modul 4/5/7/10, Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 13 adalah **modul paling matang secara business logic** (5 transitions, risk-matrix
  validation, test 535 lines, docs lengkap). TAPI punya **2 lubang kritis**:
  1. **Notif silent** (G1) — sama antipattern dgn Modul 4/9/11.
  2. **Scope bocor + Policy TODO** (G2) — ini LEBIH PARAH dari Modul 7: Policy explicit
     `// TODO: implement site scope check` → user lintas site bisa lihat/edit risk register orang
     lain. Ini celah authz NYATA (bukan cuma fragility seperti hardcode role).
- G3 minor: model tidak punya `getAvailableActions` (WORKFLOW.md §9 ada) — UI mungkin tidak render
  tombol dgn benar.
- Berbeda dgn Modul 9/12 (no transitions), Modul 13 transitions JALAN — fokus debug adalah
  **notif + authz**, bukan lifecycle.
- **Controller ada di folder `RiskManagement`, bukan `Risk`** — ini mengkoreksi asumsi awal saya
  (sama pola dgn Modul 10 `Security` vs folder `Security`, Modul 12 `Quality` vs `Quality`).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
