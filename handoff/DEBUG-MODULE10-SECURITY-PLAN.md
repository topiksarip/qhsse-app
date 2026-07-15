# DEBUG-MODULE10-SECURITY-PLAN.md — Debug Mendalam Modul 10 (Security Management)

**Tanggal:** 2026-07-15
**Modul:** `11-security-management` (Phase 11 — 3 resource: SecurityIncident / VisitorLog / PatrolChecklist)
**Metode:** systematic-debugging (Iron Law: TIDAK ada fix tanpa root-cause evidence)
**Status:** 🟡 **Campuran: VisitorLog & PatrolChecklist BENAR; SecurityIncident robek (no transisi, no notif, scope manipulatif).**

---

## 0. Konteks & Bukti Segar

Modul 10 punya **3 resource** dgn kualitas tidak merata:

| Resource | Kualitas | Bukti |
|----------|---------|------|
| **VisitorLog** | ✅ BENAR | `VisitorAccess` scope `core.scope.*` (L16-23); `checkOut` `lockForUpdate` + audit + activity; test `VisitorLogTest` |
| **PatrolChecklist** | ✅ BENAR | `applyScope` `core.scope.*` (L269-277); notif + `audit->workflow` + lock; checkpoint validation; test `PatrolWorkflowTest` |
| **SecurityIncident** | 🔴 LEMAH | **TIDAK ADA method `investigate`/`close`**; tidak inject `NotificationService`; scope `?scope=` manipulatif; `update()` bisa set `closed` tanpa `resolution` |

**SecurityIncident adalahModule 9-level gap**: records dibuat tapi tidak bisa diproses via transition
yang benar (hanya via `update()` biasa yang tidak validasi `resolution`).

| # | Gap | Bukti | WORKFLOW.md mensyaratkan |
|---|-----|------|--------------------------|
| G1 | **SecurityIncident TIDAK ADA method `investigate`/`close`** | Controller L1-213 + route hanya CRUD/export; `grep investigate/close` empty | §2: transisi `reported→under_investigation→closed` wajib |
| G2 | SecurityIncident tidak inject `NotificationService` | ctor L24-28 tidak inject; tidak ada `notify` | §2: notif `security.incident.closed` ke reporter |
| G3 | SecurityIncident scope `?scope=all` manipulatif | `index` L39-46 / `export` L182-189 pakai `$request->input('scope','all')` TANPA cek `core.scope.all` | User tanpa `core.scope.all` bisa lihat lintas site |
| G4 | SecurityIncident `update()` bisa set `closed` tanpa `resolution` | L157-161: `if status==='closed' set resolved_at` lalu `$incident->update($validated)` — TIDAK validasi `resolution` required min:10 | §2: `close` wajib `resolution` min:10 |
| G5 | SecurityIncident `update()` tidak cek status terminal | L161 langsung update; closed bisa di-reopen via PUT | §2: `closed` terminal, tidak bisa edit |
| G6 | SecurityIncident `show()` tidak `availableTransitions` | L131-137 tidak kirim transitions/abilities | UI tidak bisa render tombol aksi |
| G7 | `getQhsseTeam`/`notifySiteTeam` hardcode role | `PatrolChecklist::notifySiteTeam` L281 `User::role(['QHSSE Officer','QHSSE Manager'])` | Antipattern (sama Modul 7 G1) — tapi sudah di-scope site |

---

## 1. Inventori Target

| Komponen | Path |
|----------|------|
| Controllers | `SecurityIncidentController`, `VisitorLogController` (✅), `PatrolChecklistController` (✅), `PatrolResultController` |
| Models | `SecurityIncident`, `VisitorLog`, `PatrolChecklist`, `PatrolResult` |
| Access | `app/Modules/Security/VisitorAccess.php` (✅ pattern BENAR) |
| Policies | `SecurityIncidentPolicy`, `VisitorLogPolicy`?, `PatrolChecklistPolicy`? |
| Requests | `StoreSecurityIncidentRequest`, `UpdateSecurityIncidentRequest`, dll |
| Routes | `routes/modules/security.php` (require di `routes/modules.php` L220) |
| Tests | `tests/Feature/Modules/Security/PatrolWorkflowTest.php` (✅), `VisitorLogTest.php` (✅) |
| Spec | `docs-qhsse/modules/11-security-management/{MODULE_SPEC,WORKFLOW,DATA_MODEL,TEST_CASES,API_CONTRACT,UI_PAGES}.md` |

---

## 2. Workstream

### WS-1: Implementasi transisi SecurityIncident (G1/G4)  🔴
- **Bug:** SecurityIncident tidak bisa diproses via transition benar.
- **Fix:** tambah method `investigate`/`close` di controller (sesuai WORKFLOW.md §2):
  - `investigate`: `abort_if(status!=='reported')`; set `under_investigation`; audit + activity.
  - `close`: `validate(['resolution'=>'required|min:10'])`; `abort_if(!in_array(status,['reported','under_investigation']))`;
    set `closed`, `resolution`, `resolved_at`; audit + activity + notif reporter.
  - Tambah route `POST /security/incidents/{incident}/investigate` & `/close` di `routes/modules/security.php`.
- **Verifikasi:** `reported`→investigate→under_investigation→close (dgn resolution).
- **DoD:** lifecycle jalan; test cover.

### WS-2: Inject + kirim notif SecurityIncident (G2)  🔴
- **Fix:** inject `NotificationService`; notif di `close` (reporter) + `investigate`.
- **Verifikasi:** `core_notifications` terisi.
- **DoD:** notif jalan; test cover.

### WS-3: Scope SecurityIncident `core.scope.*` (G3)  🔴
- **Bug:** `index`/`export` pakai `?scope=all` tanpa cek permission.
- **Fix:** ganti ke `visibleQuery` berbasis `core.scope.*` (seperti VisitorLog/PatrolChecklist).
- **Verifikasi:** `core.scope.site` + `?scope=all` → tetap site sendiri.
- **DoD:** scope aman; test cover.

### WS-4: `update()` cek status + resolution (G4/G5)  🔴
- **Bug:** `update()` bisa set `closed` tanpa `resolution`, dan bisa reopen closed.
- **Fix:** `abort_if(status==='closed', 422)` (defense-in-depth); HAPUS logika set `status='closed'`
  dari `update()` — gunakan method `close` (WS-1) yang validasi `resolution`.
- **DoD:** update blocked saat closed; close wajib resolution; test cover.

### WS-5: `show()` kirim `availableTransitions` (G6)  🟡
- **Fix:** di `show()` tambah `availableTransitions` (gated permission) + `abilities` untuk UI.
- **DoD:** UI render tombol aksi; test props.

### WS-6: Hardcode role di Patrol (G7)  🟡
- **Bug:** `notifySiteTeam` L281 hardcode `User::role(['QHSSE Officer','QHSSE Manager'])`.
- **Fix:** gunakan permission-based resolver (`User::whereHas('roles.permissions', 'security.patrols.*')`)
  atau constant `CorePermissions`. Sudah di-scope site, jadi impact rendah.
- **DoD:** tidak hardcode role; test cover.

### WS-7: Tests SecurityIncident (G1-G6)  🔴
- **Bug:** SecurityIncident tidak ada dedicated test (hanya Patrol/Visitor ada).
- **Fix:** `tests/Feature/Modules/Security/SecurityIncidentTest.php`:
  - CRUD + permission + audit
  - lifecycle transisi (WS-1)
  - close wajib resolution
  - scope enforcement (WS-3)
  - closed terminal (no edit/reopen)
- **DoD:** minimal 10 test; suite PASS.

### WS-8: Frontend  🟡
- **Sudah:** TS `sla_days` fix (build green); Patrol/Visitor UI benar.
- **Debug:** SecurityIncident Show render `availableTransitions` + action buttons (investigate/close
  w/ resolution modal); Index scope filter.
- **DoD:** `npm run build` green; UI aksi benar.

---

## 3. Cross-Link ke Plan Lain

- **DEBUG-MODULE9-ENVIRONMENT-PLAN.md G1:** SecurityIncident SAMA — tidak ada transisi. Tapi di
  Modul 10, VisitorLog & PatrolChecklist SUDAH BENAR (pattern `VisitorAccess`/`applyScope`), jadi
  SecurityIncident tinggal ikuti pola yang SUDAH ADA di modul yang sama.
- **DEBUG-MODULE7-TRAINING-PLAN.md G1 + DEBUG-MODULE8-PERMIT-PLAN.md G6:** hardcode role / scope
  manual — SecurityIncident G3 sama; Patrol G7 hardcode role (sudah di-scope).
- **DEBUG-CORE-MASTER-PLAN.md WS-6:** notif via permission, bukan hardcode.
- **Decision Log:** "Dalam satu modul, gunakan pattern yang SAMA (VisitorAccess/applyScope sudah ada
  di Security — SecurityIncident harus ikut); BANNED hardcode role; scope via `core.scope.*`."

---

## 4. Urutan Eksekusi

1. **WS-1** (SecurityIncident transisi — CRITICAL) — ikuti pola VisitorLog/Patrol.
2. **WS-2** (notif) — silent gap.
3. **WS-3** (scope) — authz.
4. **WS-4** (update guard) — integrity.
5. **WS-7** (tests) — coverage.
6. **WS-5/6/8** — show props, hardcode role, frontend.

---

## 5. Commands Verifikasi

```bash
# Cek method transisi SecurityIncident ada?
grep -rn "function investigate\|function close" app/Http/Controllers/Modules/Security/SecurityIncidentController.php
# (empty = CRITICAL GAP)

# Repro G4: update set closed tanpa resolution (saat ini lolos = bug)
php artisan tinker --execute="
\$u=App\Models\User::factory()->create(); \$u->assignRole('Admin');
\$s=App\Models\Modules\Security\SecurityIncident::factory()->create(['status'=>'reported','reported_by'=>\$u->id]);
\$c=app(App\Http\Controllers\Modules\Security\SecurityIncidentController::class);
\$req=request(); \$req->merge(['status'=>'closed']); // tanpa resolution
\$c->update(\$req, \$s);
echo \$s->fresh()->status.' resolved_at='.\$s->fresh()->resolved_at; // saat ini 'closed' TANPA resolution (BUG)
"

# Repro G3: scope manipulasi
curl -s 'http://localhost/security/incidents?scope=all'  # tanpa core.scope.all lihat semua?

# Frontend
npm run build
```

---

## 6. Definition of Done (Modul 10 Total)

- [ ] WS-1: `investigate`/`close` + route SecurityIncident; full lifecycle jalan; test cover.
- [ ] WS-2: notif SecurityIncident via `NotificationService`; test cover.
- [ ] WS-3: scope SecurityIncident `core.scope.*`; test cover.
- [ ] WS-4: `update()` blocked saat closed; close wajib resolution; test cover.
- [ ] WS-7: `SecurityIncidentTest` minimal 10; suite PASS.
- [ ] WS-5: `show()` kirim `availableTransitions` + abilities; test props.
- [ ] WS-6: Patrol `notifySiteTeam` tanpa hardcode role; test cover.
- [ ] WS-8: `npm run build` green; UI aksi benar.
- [ ] Cross-link Modul 9, Core/Master WS-6, Modul 7/8 tertutup; Decision Log + Handoff.

---

## 7. Catatan Jujur

- Modul 10 adalah **kasus menarik**: VisitorLog & PatrolChecklist SUDAH production-ready (scope benar,
  notif, audit, lock, test). Tapi SecurityIncident robek — tidak ada transisi, tidak ada notif, scope
  manipulatif, `update()` bisa bypass resolution.
- G1/G4 = SecurityIncident tidak bisa diproses dengan benar (sama level Modul 9, tapi hanya 1 dari 3
  resource).
- **KEUNTUNGAN**: pattern BENAR SUDAH ADA di modul yang sama (`VisitorAccess`, `applyScope`).
  SecurityIncident tinggal ikuti, bukan redesign.
- Berbeda dgn Modul 9 yang fully non-functional, Modul 10 partial — 2/3 resource sudah bagus.
- Plan ini BUKAN eksekusi; tidak ada kode diubah sampai per WS dilakukan dengan root-cause evidence.
