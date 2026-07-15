# DEBUG-MODULE15-EMERGENCY-PLAN.md — Debug Mendalam Modul 15 (Emergency Preparedness)

**Tanggal:** 2026-07-15
**Modul:** `15-emergency-preparedness` (Phase 15 — EmergencyPlan + EmergencyDrill + EmergencyContact)
**Controller:** `App\Http\Controllers\Modules\EmergencyPreparedness\{EmergencyPlanController, EmergencyDrillController, EmergencyContactController}`
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🔴 **3 controllers + test ada, tapi NOTIF SILENT + hardcode role + tidak audit + drill executed tidak di-guard.**

---

## 0. Konteks & Bukti Segar

Modul 15 (Emergency) **punya 3 controllers + 3 test files** (`EmergencyPlanTest`, `EmergencyDrillTest`,
`EmergencyContactTest`) + **docs lengkap** (WORKFLOW.md 464 lines). Tapi baca kode nemukan **6 gap**
(utama di `EmergencyDrillController`):

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | 🔴 **NOTIF SILENT** | `grep NotificationService` EmergencyPreparedness = **empty**; `store` L109-131 tdk notif; `execute` L204-207 `// TODO: Send notification`; tdk `drill_scheduled`/`drill_executed`/`drill_failed` | §3/§7/§9: notif `emergency.drill_scheduled`, `drill_executed`, `drill_failed` (fail) |
| G2 | 🔴 **Hardcode role di scope** | `EmergencyDrillController` L72-78/L259-265; `EmergencyPlanController` L61-64/L217-220; `EmergencyContactController` L50-53 `hasAnyRole([5 role])` skip scope | Antipattern (sama Modul 7/14) |
| G3 | 🟡 **`store` drill tidak audit** | L121 hanya `activityService->log`; tdk `auditService->created` | §5: Create = Audit + Activity |
| G4 | 🔴 **`execute` drill tidak audit** | L196 hanya `activityService->log`; tdk `auditService->log(event=emergency.drill_executed, old/new)` | §5/§7: Execute = Audit (old/new) + Activity |
| G5 | 🔴 **`update` drill tdk cek `scheduled`** | L164-180 langsung update; `executed` bisa diedit | §6: `executed` read-only (kecuali Admin) |
| G6 | 🔴 **`destroy` drill tdk cek `executed`** | L213-231 langsung delete | §6: `executed` tdk bisa diubah; reopen TIDAK didukung |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `EmergencyPlanController`, `EmergencyDrillController`, `EmergencyContactController` (`app/Http/Controllers/Modules/EmergencyPreparedness/`) |
| Models | `EmergencyPlan`, `EmergencyDrill`, `EmergencyContact` (`app/Models/Modules/EmergencyPreparedness/`) |
| Policies | `EmergencyPlanPolicy`, `EmergencyDrillPolicy`, `EmergencyContactPolicy` (`app/Policies/Modules/EmergencyPreparedness/`) |
| Requests | `StoreEmergencyDrillRequest`, `UpdateEmergencyDrillRequest`, `ExecuteEmergencyDrillRequest`, dll |
| Routes | `routes/modules/emergency.php` (require di `routes/modules.php` L228) |
| Tests | `tests/Feature/Modules/EmergencyPreparedness/{EmergencyPlanTest, EmergencyDrillTest, EmergencyContactTest}.php` |
| Docs | `docs-qhsse/modules/15-emergency-preparedness/` (✅ lengkap) |

---

## 2. Workstream

### WS-1: Implementasi notif (G1)  🔴
- **Bug:** store/execute drill tidak notif; `execute` TODO.
- **Fix:** inject `NotificationService`; notif `drill_scheduled` (store), `drill_executed` (execute),
  `drill_failed` (jika result fail/needs_improvement). Recipient via site scope (QHSSE Officer/
  Manager di site + observer + contact person), BUKAN hardcode role.
- **Verifikasi:** `core_notifications` terisi; test cover.
- **DoD:** notif jalan; test cover.

### WS-2: Hardcode role → `core.scope.*` (G2)  🔴
- **Bug:** 3 controllers hardcode 5 role untuk skip scope.
- **Fix:** ganti ke `if ($user->can('core.scope.all')) { /* no scope */ } else { /* site/dept/owner scope */ }`
  (sama pola `VisitorAccess`/`ComplaintAccess`).
- **DoD:** scope tanpa hardcode role; test cover (cross-site block).

### WS-3: Audit trail consistency (G3/G4)  🟡🔴
- **Fix:** `store` drill panggil `auditService->created`; `execute` drill panggil
  `auditService->log(event=emergency.drill_executed, old/new)`. (Sesuai WORKFLOW.md §5/§7.)
- **DoD:** audit_logs konsisten; test cover.

### WS-4: Terminal status guards (G5/G6)  🔴
- **Fix:** `update` drill `abort_if(status==='executed')` (kecuali Admin); `destroy` drill
  `abort_if(status==='executed')`. (Sesuai WORKFLOW.md §6.)
- **DoD:** executed drill tidak bisa edit/delete; test cover.

### WS-5: Tests (G1-G6)  🔴
- **Bug:** 3 test files ada tapi mungkin belum cover notif/audit/status guards.
- **Fix:** tambah test notif (WS-1), hardcode role removal (WS-2), audit (WS-3), status guards (WS-4).
- **DoD:** coverage lengkap; suite PASS.

### WS-6: Frontend  🟡
- **Debug:** Show drill render `availableTransitions` (WORKFLOW.md §8) sesuai status; Index scope
  filter. `npm run build` green.
- **DoD:** UI benar; `npm run build` green.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE4-INSPECTION-PLAN.md G2/G3 + DEBUG-MODULE9-ENVIRONMENT-PLAN.md G2 + DEBUG-MODULE11-COMMUNICATION-PLAN.md G1 + DEBUG-MODULE13-RISK-PLAN.md G1 + DEBUG-MODULE14-LEGAL-PLAN.md G1:** notif silent — Emergency SAMA (G1).
- **DEBUG-MODULE7-TRAINING-PLAN.md G1 + DEBUG-MODULE14-LEGAL-PLAN.md G3:** hardcode role di scope — Emergency G2 sama (5 role).
- **DEBUG-MODULE14-LEGAL-PLAN.md G4/G5:** tidak audit — Emergency G3/G4 sama.
- **DEBUG-MODULE14-LEGAL-PLAN.md G6/G7:** status terminal guard — Emergency G5/G6 sama.
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission/site, bukan hardcode role.
- **Decision Log:** "BANNED hardcode role di scope; semua transition/notif via `NotificationService`;
  semua status terminal di-guard; semua create/transition audit via `AuditService`."

---

## 4. Urutan Eksekusi

1. **WS-1** (notif) — silent gap.
2. **WS-2** (hardcode role → scope) — authz.
3. **WS-4** (status guards) — integrity.
4. **WS-3** (audit) — consistency.
5. **WS-5** (tests) — coverage.
6. **WS-6** (frontend).

---

## 5. Commands Verifikasi

```bash
# Cek notif
grep -rn "NotificationService" app/Http/Controllers/Modules/EmergencyPreparedness/   # empty = SILENT

# Cek hardcode role
grep -rn "hasAnyRole(\['Super Admin'" app/Http/Controllers/Modules/EmergencyPreparedness/   # 3 controllers

# Repro G5: edit executed drill (saat ini lolos)
php artisan tinker --execute="
\$d=App\Models\Modules\EmergencyPreparedness\EmergencyDrill::factory()->create(['status'=>'executed']);
\$c=app(App\Http\Controllers\Modules\EmergencyPreparedness\EmergencyDrillController::class);
\$req=request(); \$req->setMethod('PUT'); \$req->merge(['result'=>'pass']);
\$c->update(\$req, \$d); // saat ini UPDATE berhasil (BUG: executed read-only)
echo \$d->fresh()->result;
"

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 15 Total)

- [ ] WS-1: `NotificationService` inject + notif drill_scheduled/executed/failed; test cover.
- [ ] WS-2: scope `core.scope.*` (no hardcode role) di 3 controllers; test cross-site block.
- [ ] WS-4: `update`/`destroy` drill guard `executed`; test cover.
- [ ] WS-3: `store`/`execute` drill audit via `AuditService`; test cover.
- [ ] WS-5: tests notif + scope + guards + audit; suite PASS.
- [ ] WS-6: `npm run build` green; UI transitions + scope filter benar.
- [ ] Cross-link Modul 4/7/9/11/13/14, Core/Master WS-6 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 15 **paling lengkap struktur** (3 controllers, 3 models, 3 policies, 3 test files, docs
  lengkap). TAPI sama antipattern global:
  - Notif SILENT (G1) — padahal WORKFLOW.md §3/§7/§9 explicit sebut 3 notif event.
  - Hardcode role (G2) — sama Modul 7/14.
  - Tidak audit (G3/G4) — `store`/`execute` hanya `activityService`, bukan `AuditService` (langgar
    WORKFLOW.md §5).
  - Drill `executed` TIDAK di-guard di `update`/`destroy` (G5/G6) — langgar WORKFLOW.md §6. Ini
    **integrity bug**: hasil drill (evidence kepatuhan audit) bisa diubah/hapus setelah executed.
- **Controller ada di folder `EmergencyPreparedness`, bukan `Emergency`** (folder `Emergency` kosong).
  Sama pola `RiskManagement`/`Risk`, `LegalCompliance`/`Legal`.
- Tests ada — harus diverifikasi sudah cover G1-G6 (kemungkinan belum, karena notif/audit TODO).
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
