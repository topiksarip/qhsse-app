# Workflow — Security Management

## 1. Overview

Modul Security Management tidak menggunakan `WorkflowService` (tidak ada seeded workflow definition). Sebagai gantinya, modul ini menggunakan **status field transitions** yang dikelola langsung di controller/model level dengan audit trail via `AuditService` dan `ActivityService`.

Tiga resource groups memiliki workflow masing-masing:

1. **Security Incidents** — 3-state workflow: `reported` → `under_investigation` → `closed`
2. **Visitor Logs** — 2-state workflow: `checked_in` → `checked_out` (via check_out_at timestamp)
3. **Patrol Checklists** — 3-state workflow: `scheduled` → `in_progress` → `completed`

---

## 2. Security Incident Workflow

### States

| State | Type | Description |
|---|---|---|
| `reported` | Initial | Insiden dilaporkan. Bisa diedit dan diinvestigasi. |
| `under_investigation` | Active | Investigasi sedang berjalan. Bisa diedit dan ditutup. |
| `closed` | **Terminal** | Insiden selesai. Tidak bisa diedit. Resolution dan resolved_at terisi. |

### Transition Table

| From | To | Action Key | Label | Requires Resolution | Required Permission |
|---|---|---|---|---|---|
| `reported` | `under_investigation` | `investigate` | Mulai Investigasi | ❌ | `security.incidents.update` |
| `reported` | `closed` | `close` | Tutup Insiden | ✅ | `security.incidents.close` |
| `under_investigation` | `closed` | `close` | Tutup Insiden | ✅ | `security.incidents.close` |

### Workflow Diagram

```
reported ──(investigate)──→ under_investigation ──(close)──→ closed
    │                                                         ▲
    └──────────────────(close)────────────────────────────────┘
```

### Phase 1 Simplified Path

Phase 1 mendukung dua jalur:

1. **Dengan investigasi:** `reported` → `under_investigation` → `closed`
2. **Tanpa investigasi (insiden sederhana):** `reported` → `closed` (skip investigasi)

Kedua jalur wajib mengisi `resolution` saat close.

### Transition Rules

- **`investigate`**: Hanya bisa dari status `reported`. Set status ke `under_investigation`.
- **`close`**: Bisa dari `reported` atau `under_investigation`. Wajib `resolution` (min 10 karakter). Set `resolved_at` = now().
- **`closed`** adalah terminal status. Tidak bisa di-reopen di Phase 1.
- Edit (update) hanya bisa jika status `reported` atau `under_investigation`.

### Controller Integration

```php
// Investigate
public function investigate(SecurityIncident $securityIncident, Request $request): RedirectResponse
{
    $this->authorize('update', $securityIncident);
    
    if ($securityIncident->status !== 'reported') {
        return back()->withErrors(['status' => 'Insiden tidak dapat diinvestigasi dari status saat ini.']);
    }

    $oldValues = $securityIncident->toArray();
    $securityIncident->update(['status' => 'under_investigation']);
    
    app(AuditService::class)->updated($securityIncident, $oldValues, $request->user(), 'security', $securityIncident->id);
    app(ActivityService::class)->log('security', $securityIncident->id, 'investigation_started', 'Investigasi dimulai', $request->user());

    return back()->with('success', 'Investigasi dimulai.');
}

// Close
public function close(SecurityIncident $securityIncident, Request $request): RedirectResponse
{
    $this->authorize('close', $securityIncident);
    
    if (!in_array($securityIncident->status, ['reported', 'under_investigation'])) {
        return back()->withErrors(['status' => 'Insiden tidak dapat ditutup dari status saat ini.']);
    }

    $validated = $request->validate([
        'resolution' => 'required|string|min:10|max:2000',
    ]);

    $oldValues = $securityIncident->toArray();
    $securityIncident->update([
        'status' => 'closed',
        'resolution' => $validated['resolution'],
        'resolved_at' => now(),
    ]);
    
    app(AuditService::class)->updated($securityIncident, $oldValues, $request->user(), 'security', $securityIncident->id);
    app(ActivityService::class)->log('security', $securityIncident->id, 'security.incident.closed', 'Insiden ditutup: ' . $validated['resolution'], $request->user());
    app(NotificationService::class)->notify(
        $securityIncident->reportedBy,
        'security.incident.closed',
        [...],
        $request->user(),
        'security',
        $securityIncident->id,
        route('security.incidents.show', $securityIncident)
    );

    return back()->with('success', 'Insiden keamanan telah ditutup.');
}
```

### Audit Trail

Setiap transition mencatat:
1. `audit_logs` record dengan event `updated` (old_values → new_values, including status change)
2. `activity_logs` record dengan event spesifik (`investigation_started`, `security.incident.closed`)

### Terminal Status Rules

- `closed` adalah terminal — tidak ada transition keluar
- Setelah closed: record read-only, tidak bisa edit, tidak bisa hapus evidence
- Reopen NOT supported in Phase 1

---

## 3. Visitor Log Workflow

### States

| State | Type | Description |
|---|---|---|
| `checked_in` | Initial | Pengunjung terdaftar dan berada di lokasi. `check_out_at` = NULL. |
| `checked_out` | **Terminal** | Pengunjung telah meninggalkan lokasi. `check_out_at` terisi. |

### Transition Table

| From | To | Action Key | Label | Required Permission |
|---|---|---|---|---|
| `checked_in` | `checked_out` | `check_out` | Check-Out | `security.visitors.update` |

### Workflow Diagram

```
checked_in ──(check_out)──→ checked_out
```

### Transition Rules

- **`check_out`**: Hanya bisa jika `check_out_at` IS NULL. Set `check_out_at` = now().
- Tidak bisa check-out dua kali.
- Tidak bisa edit visitor log setelah check-out (kecuali Admin).

### Controller Integration

```php
public function checkOut(VisitorLog $visitorLog, Request $request): RedirectResponse
{
    $this->authorize('update', $visitorLog);
    
    if ($visitorLog->check_out_at !== null) {
        return back()->withErrors(['check_out_at' => 'Pengunjung sudah check-out.']);
    }

    $oldValues = $visitorLog->toArray();
    $visitorLog->update(['check_out_at' => now()]);
    
    app(AuditService::class)->updated($visitorLog, $oldValues, $request->user(), 'security', $visitorLog->id);
    app(ActivityService::class)->log('security', $visitorLog->id, 'security.visitor.checked_out', 'Pengunjung check-out', $request->user());
    app(NotificationService::class)->notify(
        $visitorLog->host,
        'security.visitor.checked_out',
        [...],
        $request->user(),
        'security',
        $visitorLog->id,
        route('security.visitors.show', $visitorLog)
    );

    return back()->with('success', 'Pengunjung telah check-out.');
}
```

---

## 4. Patrol Checklist Workflow

### States

| State | Type | Description |
|---|---|---|
| `scheduled` | Initial | Patroli dijadwalkan. Belum dieksekusi. Bisa diedit. |
| `in_progress` | Active | Patroli sedang dieksekusi. `executed_at` terisi. Checkpoint results sedang diisi. |
| `completed` | **Terminal** | Patroli selesai. Semua checkpoint telah diisi. Tidak bisa diedit. |

### Transition Table

| From | To | Action Key | Label | Requires | Required Permission |
|---|---|---|---|---|---|
| `scheduled` | `in_progress` | `execute` | Mulai Eksekusi | ❌ | `security.patrols.execute` |
| `in_progress` | `completed` | `complete` | Selesaikan | All checkpoints filled | `security.patrols.execute` |

### Workflow Diagram

```
scheduled ──(execute)──→ in_progress ──(complete)──→ completed
```

### Transition Rules

- **`execute`**: Hanya bisa dari status `scheduled`. Set status ke `in_progress`, set `executed_at` = now().
- **`complete`**: Hanya bisa dari status `in_progress`. Semua `PatrolResult` records harus memiliki `status` yang tidak null (ok/issue/na). Set status ke `completed`.
- **`completed`** adalah terminal status.
- Edit (update) hanya bisa jika status `scheduled`.

### Patrol Results Flow

```
Patrol created → PatrolResult records created (status = NULL, remark = NULL)
                         │
                    execute patrol
                         │
                    ┌─────┴─────┐
                    │           │
              fill result   fill result
              status=ok     status=issue
              remark=opt    remark=required
                    │           │
                    └─────┬─────┘
                          │
                    complete patrol
                    (all results filled)
                          │
                          ▼
                     completed
```

### Controller Integration

```php
// Execute
public function execute(PatrolChecklist $patrolChecklist, Request $request): RedirectResponse
{
    $this->authorize('execute', $patrolChecklist);
    
    if ($patrolChecklist->status !== 'scheduled') {
        return back()->withErrors(['status' => 'Patroli tidak dapat dieksekusi dari status saat ini.']);
    }

    $oldValues = $patrolChecklist->toArray();
    $patrolChecklist->update([
        'status' => 'in_progress',
        'executed_at' => now(),
    ]);
    
    app(AuditService::class)->updated($patrolChecklist, $oldValues, $request->user(), 'security', $patrolChecklist->id);
    app(ActivityService::class)->log('security', $patrolChecklist->id, 'security.patrol.executed', 'Patroli dieksekusi', $request->user());
    app(NotificationService::class)->notifyMany(
        $this->getQhsseTeam($patrolChecklist->site_id),
        'security.patrol.executed',
        [...],
        $request->user(),
        'security',
        $patrolChecklist->id,
        route('security.patrols.show', $patrolChecklist)
    );

    return back()->with('success', 'Patroli dimulai.');
}

// Store Result
public function storeResult(PatrolChecklist $patrolChecklist, Request $request): JsonResponse
{
    $this->authorize('execute', $patrolChecklist);
    
    $validated = $request->validate([
        'patrol_result_id' => 'required|exists:patrol_results,id',
        'status' => 'required|in:ok,issue,na',
        'remark' => 'required_if:status,issue|nullable|string|min:5',
    ]);

    $result = PatrolResult::findOrFail($validated['patrol_result_id']);
    
    if ($result->patrol_checklist_id !== $patrolChecklist->id) {
        abort(404);
    }

    $oldValues = $result->toArray();
    $result->update([
        'status' => $validated['status'],
        'remark' => $validated['remark'],
    ]);
    
    app(AuditService::class)->updated($result, $oldValues, $request->user(), 'security', $patrolChecklist->id);
    app(ActivityService::class)->log('security', $patrolChecklist->id, 'security.patrol.result_recorded', "Checkpoint '{$result->checkpoint}': {$validated['status']}", $request->user());

    if ($validated['status'] === 'issue') {
        app(NotificationService::class)->notifyMany(
            $this->getQhsseTeam($patrolChecklist->site_id),
            'security.patrol.issue_found',
            [
                'patrol_number' => $patrolChecklist->patrol_number,
                'checkpoint' => $result->checkpoint,
                'remark' => $validated['remark'],
            ],
            $request->user(),
            'security',
            $patrolChecklist->id,
            route('security.patrols.show', $patrolChecklist)
        );
    }

    return response()->json(['success' => true, 'result' => $result->fresh()]);
}

// Complete
public function complete(PatrolChecklist $patrolChecklist, Request $request): RedirectResponse
{
    $this->authorize('execute', $patrolChecklist);
    
    if ($patrolChecklist->status !== 'in_progress') {
        return back()->withErrors(['status' => 'Patroli tidak dapat diselesaikan dari status saat ini.']);
    }

    // Check all results have status
    $unfilled = $patrolChecklist->results()->whereNull('status')->count();
    if ($unfilled > 0) {
        return back()->withErrors(['checkpoints' => "Masih ada {$unfilled} checkpoint yang belum diisi."]);
    }

    $oldValues = $patrolChecklist->toArray();
    $patrolChecklist->update(['status' => 'completed']);
    
    app(AuditService::class)->updated($patrolChecklist, $oldValues, $request->user(), 'security', $patrolChecklist->id);
    app(ActivityService::class)->log('security', $patrolChecklist->id, 'security.patrol.completed', 'Patroli diselesaikan', $request->user());

    return back()->with('success', 'Patroli telah diselesaikan.');
}
```

### Audit Trail

Setiap transition dan result recording mencatat:
1. `audit_logs` record (old_values → new_values)
2. `activity_logs` record dengan event spesifik

---

## 5. Workflow Summary (All 3 Resources)

```
SECURITY INCIDENTS              VISITOR LOGS              PATROL CHECKLISTS
───────────────────              ─────────────              ─────────────────
                                                          
reported                         checked_in                  scheduled
   │                                 │                         │
   ├─(investigate)→                  │                         ├─(execute)→
   │  under_investigation            │                         │  in_progress
   │      │                          │                         │      │
   │      ├─(close)→ closed          └─(check_out)→            │      ├─(fill results)→
   │      │              ✓              checked_out ✓         │      │   (per checkpoint)
   │      │                                                    │      │
   └─(close)→ closed ✓                                        └─(complete)→
              ✓                                                   completed ✓

Legend: ✓ = terminal status
```

---

## 6. Future Enhancements (Phase 2+)

| Feature | Description |
|---|---|
| **Reopen security incident** | `closed → under_investigation` with permission `security.incidents.reopen` |
| **Patrol templates** | Pre-defined patrol routes with fixed checkpoints |
| **QR code checkpoints** | Scan QR code at each checkpoint for verification |
| **Visitor pre-registration** | Host pre-registers visitor before arrival |
| **Visitor approval workflow** | Host must approve visitor check-in |
| **Escalation rules** | Auto-escalate unclosed incidents after X days |
| **Linked incidents** | Link security incident to general incident report |
| **Patrol evidence photos** | Upload photos per checkpoint result |
