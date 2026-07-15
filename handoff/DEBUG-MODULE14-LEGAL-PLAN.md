# DEBUG-MODULE14-LEGAL-PLAN.md — Debug Mendalam Modul 14 (Legal & Compliance)

**Tanggal:** 2026-07-15
**Modul:** `14-legal-compliance` (Phase 14 — LegalRegister + LegalObligation)
**Controller:** `App\Http\Controllers\Modules\LegalCompliance\{LegalRegisterController, LegalObligationController}`
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴 **Docs + test ada, tapi NOTIF SILENT + command overdue HILANG + hardcode role + tidak audit + tidak cek status terminal.**

---

## 0. Konteks & Bukti Segar

Modul 14 (Legal) **punya docs sangat lengkap** (WORKFLOW.md 722 lines) + **test ada**
(`LegalRegisterTest` Feature + `LegalRegisterControllerTest` Unit). Tapi baca kode nemukan **7 gap**:

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | 🔴 **NOTIF SILENT** | `grep NotificationService` LegalCompliance = **empty**; `store` L113-137 tdk notif; `update` L200-202 `// TODO: Send notification`; `complete` obligation tdk notif | §7/§8: notif `legal.register.created`, `legal.compliance.changed` (non_compliant), obligation complete |
| G2 | 🔴 **Command overdue HILANG** | `find app/Console -iname "*Overdue*"` = **empty**; schedule `legal:check-overdue` tdk ada | §6: `CheckOverdueObligations` + schedule daily 00:01 — fitur alert overdue GAGAL |
| G3 | 🔴 **Hardcode role di scope** | `index` L73-81 / `export` L264-272 `hasAnyRole([5 role])` untuk skip scope | Antipattern (sama Modul 7); fragile ke nama seeder |
| G4 | 🟡 **`store` tidak audit** | L127 hanya `activityService->log`; tdk `auditService->created` | §7: Create = Audit + Activity |
| G5 | 🟡 **Obligation pakai `activity()` bukan `AuditService`** | `LegalObligationController` L36/67/106/130 pakai `activity()` helper | §7: Obligation = Audit + Activity (konsisten dgn modul lain) |
| G6 | 🔴 **`destroy` register tdk cek `inactive`** | L217-235 langsung delete; tdk cek status | §2: `inactive` read-only, tdk bisa delete |
| G7 | 🔴 **`update` register tdk cek `active`** | L177-215 langsung update; tdk cek status (lawan obligation `complete` yg cek status) | §2/§8: update hanya kalau `active` |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `LegalRegisterController`, `LegalObligationController` (`app/Http/Controllers/Modules/LegalCompliance/`) |
| Models | `LegalRegister`, `LegalObligation` (`app/Models/Modules/LegalCompliance/`) — **models ada di sini, bukan `app/Models/Modules/Legal/` (kosong)** |
| Policies | `LegalRegisterPolicy`, `LegalObligationPolicy` (`app/Policies/Modules/LegalCompliance/`) |
| Requests | `StoreLegalRegisterRequest`, `UpdateLegalRegisterRequest`, `StoreLegalObligationRequest`, `UpdateLegalObligationRequest`, `CompleteLegalObligationRequest` |
| Routes | `routes/modules/legal.php` (require di `routes/modules.php` L227) |
| Tests | `tests/Feature/Modules/LegalCompliance/LegalRegisterTest.php`, `tests/Unit/Modules/LegalCompliance/LegalRegisterControllerTest.php` |
| Docs | `docs-qhsse/modules/14-legal-compliance/` (✅ lengkap) |
| Command | `app/Console/Commands/CheckOverdueObligations.php` → **HILANG** |

---

## 2. Workstream

### WS-1: Implementasi notif (G1)  🔴
- **Bug:** store/update/complete tidak notif.
- **Fix:** inject `NotificationService`; notif di `store` (QHSSE team), `update` (jika
  `non_compliant` → manager), `complete` obligation (owner). Recipient via site scope, BUKAN
  hardcode role.
- **Verifikasi:** `core_notifications` terisi; test cover.
- **DoD:** notif jalan; test cover.

### WS-2: Implementasi command overdue (G2)  🔴🔴 CRITICAL (compliance)
- **Bug:** `CheckOverdueObligations` + schedule HILANG → tidak ada alert overdue/due-soon.
- **Fix:** buat `app/Console/Commands/CheckOverdueObligations.php` sesuai WORKFLOW.md §6 (pakai
  `NotificationService`, `whereDoesntHave('overdueNotificationSent')` anti-duplikat); daftarkan
  schedule di `routes/console.php` (`dailyAt('00:01')`). Pastikan model punya relasi
  `overdueNotificationSent` atau flag.
- **Verifikasi:** jalankan `php artisan legal:check-overdue` → notif terkirim untuk obligation overdue.
- **DoD:** command jalan + scheduled; test cover.

### WS-3: Hardcode role → `core.scope.*` (G3)  🔴
- **Bug:** `index`/`export` hardcode 5 role untuk skip scope.
- **Fix:** ganti ke `if ($user->can('core.scope.all')) { /* no scope */ } else { /* site/dept/owner scope */ }`
  (sama pola `VisitorAccess`/`ComplaintAccess`).
- **DoD:** scope tanpa hardcode role; test cover.

### WS-4: Audit trail consistency (G4/G5)  🟡
- **Fix:** `store` register panggil `auditService->created`; `LegalObligationController` ganti
  `activity()` → `auditService->created/updated` + `activityService->log` (sesuai WORKFLOW.md §7).
- **DoD:** audit_logs konsisten; test cover.

### WS-5: Status terminal guards (G6/G7)  🔴
- **Fix:** `destroy` register `abort_if(status==='inactive')`; `update` register
  `abort_if(status!=='active')`. (Sesuai WORKFLOW.md §2/§8.)
- **DoD:** inactive tidak bisa delete/edit; test cover.

### WS-6: Tests (G1-G7)  🔴
- **Bug:** test ada tapi mungkin belum cover notif/command/status guards.
- **Fix:** tambah test notif (WS-1), command overdue (WS-2), hardcode role removal (WS-3),
  status guards (WS-5) ke suite Legal.
- **DoD:** coverage lengkap; suite PASS.

### WS-7: Frontend  🟡
- **Debug:** Show render compliance badge + obligation visual states (overdue/due_soon); Index
  scope filter. `npm run build` green.
- **DoD:** UI benar; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3 + DEBUG-MODULE9-ENVIRONMENT-PLAN.md G2 + DEBUG-MODULE11-COMMUNICATION-PLAN.md G1 + DEBUG-MODULE13-RISK-PLAN.md G1:** notif silent — Legal SAMA (G1).
- **DEBUG-MODULE7-TRAINING-PLAN.md G1:** hardcode role di scope — Legal G3 sama (5 role vs 7 role).
- **DEBUG-MODULE9-ENVIRONMENT-PLAN.md G7 / DEBUG-MODULE10-SECURITY-PLAN.md:** status terminal guard — Legal G6/G7 sama (destroy/update tidak cek status).
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission/site, bukan hardcode role.
- **Decision Log:** "BANNED hardcode role di scope; semua transition/notif via `NotificationService`;
  command scheduled wajib ada kalau WORKFLOW sebutkan; semua status terminal di-guard di controller."

---

## 4. Urutan Eksekusi

1. **WS-2** (command overdue — CRITICAL compliance) — fitur alert hilang.
2. **WS-1** (notif) — silent gap.
3. **WS-3** (hardcode role → scope) — authz.
4. **WS-5** (status guards) — integrity.
5. **WS-6** (tests) — coverage.
6. **WS-4/7** (audit, frontend).

---

## 5. Commands Verifikasi

```bash
# Cek notif + command
grep -rn "NotificationService" app/Http/Controllers/Modules/LegalCompliance/   # empty = SILENT
find app/Console -iname "*Overdue*"   # empty = COMMAND MISSING

# Repro G6: delete inactive (saat ini lolos)
php artisan tinker --execute="
\$r=App\Models\Modules\LegalCompliance\LegalRegister::factory()->create(['status'=>'inactive']);
\$c=app(App\Http\Controllers\Modules\LegalCompliance\LegalRegisterController::class);
\$req=request(); \$req->setMethod('DELETE');
\$c->destroy(\$r); // saat ini DELETE berhasil (BUG: inactive harus read-only)
echo App\Models\Modules\LegalCompliance\LegalRegister::find(\$r->id) ? 'MASIH ADA' : 'TERHAPUS (BUG)';
"

# Repro G3: hardcode role
grep -n "hasAnyRole(\['Super Admin'" app/Http/Controllers/Modules/LegalCompliance/LegalRegisterController.php

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 14 Total)

- [x] WS-2: `CheckOverdueObligations` command + schedule `legal:check-overdue`; test cover (anti-duplicate). (✅ 2026-07-15)
- [x] WS-1: `NotificationService` inject + notif store(`legal.register.created`)/update non_compliant(`legal.compliance.changed`)/obligation create+complete; test cover. (✅ 2026-07-15)
- [x] WS-3: `LegalAccess` scope `core.scope.*` (no hardcode role) + index/export; test cover. (✅ 2026-07-15)
- [x] WS-4: `AuditService` di store/update/destroy register + obligation store/update/complete/destroy; test cover. (✅ 2026-07-15)
- [x] WS-5: `destroy`/`update` guard `status==='inactive'`/`!=='active'` (403); test cover. (✅ 2026-07-15)
- [x] WS-6: tests notif + command + scope + guards (LegalComplianceActionTest + LegalOverdueCommandTest); suite PASS. (✅ 2026-07-15)
- [x] WS-7: `npm run build` green; UI badge + scope filter benar. (✅ 2026-07-15)
- [x] Cross-link Modul 4/7/9/11/13, Core/Master WS-6 tertutup; Decision Log + Handoff. (✅ 2026-07-15)
- **MODUL 14 FULLY COMPLETE (WS-1..7).**

---

## 7. Catatan Jujur

- Modul 14 punya **docs + test terbaik** (WORKFLOW.md 722 lines, 2 test files), tapi implementasi
  **tidak mengikuti docs-nya sendiri**:
  - Notif SILENT (G1) — padahal WORKFLOW.md §7/§8 explicit sebut `NotificationService`.
  - Command overdue HILANG (G2) — padahal WORKFLOW.md §6 explicit sebut class + schedule. Ini
    **kegagalan fitur compliance kritis**: tidak ada alert overdue legal obligation.
  - Hardcode role (G3) — sama antipattern Modul 7.
  - Tidak audit (G4/G5) — `store` hanya activity; obligation pakai `activity()` helper, bukan
    `AuditService` seperti modul lain.
  - Tidak cek status terminal (G6/G7) — `destroy`/`update` register lolos saat `inactive`.
- G2 = paling kritis: legal compliance tanpa overdue alert = risiko regulasi nyata.
- **Model ada di `app/Models/Modules/LegalCompliance/`, bukan `app/Models/Modules/Legal/`** (kosong)
  — sama pola folder `RiskManagement` vs `Risk`.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
