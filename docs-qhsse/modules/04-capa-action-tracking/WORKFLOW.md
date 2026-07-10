# Workflow — CAPA / Corrective & Preventive Action Tracking

## 1. Workflow Definition (Already Seeded)

The `capa` workflow is pre-seeded in `WorkflowSeeder` (Phase 0).

| Property | Value |
|---|---|
| `module_name` | `capa` |
| `code` | `CAPA_WORKFLOW` |
| `name` | `CAPA Workflow` |
| `initial_status` | `open` |
| `is_active` | `true` |

## 2. States

| State | Type | Description |
|---|---|---|
| `open` | Initial | Tindakan dibuat, belum dimulai. PIC belum mulai pengerjaan. |
| `in_progress` | Active | PIC sedang mengerjakan tindakan. Bisa edit, upload evidence. |
| `waiting_verification` | Active | PIC sudah submit untuk verifikasi QHSSE. Tidak bisa edit. |
| `closed` | **Terminal** | Tindakan diverifikasi & ditutup oleh QHSSE. Read-only. |
| `rejected` | **Terminal (restartable)** | Tindakan ditolak QHSSE. PIC dapat restart ke in_progress. |

## 3. Transition Table (Seeded)

| From | To | Action Key | Label | Requires Reason | Required Permission |
|---|---|---|---|---|---|
| `open` | `in_progress` | `start` | Mulai Pengerjaan | ❌ | `capa.actions.update` |
| `in_progress` | `waiting_verification` | `submit_verification` | Submit untuk Verifikasi | ❌ | `capa.actions.submit` |
| `waiting_verification` | `closed` | `verify_close` | Verifikasi & Tutup | ✅ (verification_note) | `capa.actions.verify` + `capa.actions.close` |
| `waiting_verification` | `rejected` | `reject` | Tolak | ✅ (reason) | `capa.actions.reject` |
| `rejected` | `in_progress` | `restart` | Mulai Ulang | ❌ | `capa.actions.update` |

## 4. Full Workflow Path

```
                          ┌──(start)──→ in_progress ──(submit_verification)──→ waiting_verification ──(verify_close)──→ closed
                          │                                                                    │
open ──────────────────────┘                                                                    │
                                                                                               │
                                                                                          (reject)
                                                                                               │
                                                                                               ▼
                                                                                          rejected
                                                                                               │
                                                                                          (restart)
                                                                                               │
                                                                                               ▼
                                                                                          in_progress ──→ (cycle repeats)
```

### Simplified Linear View

```
open ──(start)──→ in_progress ──(submit_verification)──→ waiting_verification ──(verify_close)──→ closed
                                                                │
                                                                └──(reject)──→ rejected ──(restart)──→ in_progress
```

### State Machine Rules

1. **`open`** — Initial state. Action created but not started. Can be edited. Can transition to `in_progress` via `start`.
2. **`in_progress`** — PIC is working on the action. Can be edited. Evidence can be uploaded. Can transition to `waiting_verification` via `submit_verification` (requires ≥1 evidence file).
3. **`waiting_verification`** — PIC submitted for QHSSE verification. Cannot be edited. QHSSE can either `verify_close` (→ closed) or `reject` (→ rejected).
4. **`closed`** — Terminal. Action verified and closed by QHSSE. Read-only. No further transitions. `verified_by`, `verified_at`, `closed_at` are set.
5. **`rejected`** — Terminal but restartable. QHSSE rejected the action result. PIC can `restart` back to `in_progress`. Reason for rejection is stored in workflow history.

## 5. Transition Details

### 5.1 Start (open → in_progress)

| Property | Value |
|---|---|
| Action Key | `start` |
| Label | `Mulai Pengerjaan` |
| Permission | `capa.actions.update` |
| Requires Reason | No |
| Side Effects | Set `assigned_at = now()` if null. Send `capa.assigned` notification to PIC (if not already sent). |
| Controller | `CapaActionController::start()` |

```php
public function start(CapaAction $capaAction, WorkflowService $workflowService): RedirectResponse
{
    $this->authorize('update', $capaAction);

    try {
        $workflowService->transition('capa', $capaAction->id, 'start', auth()->user());

        if (!$capaAction->assigned_at) {
            $capaAction->update(['assigned_at' => now()]);
        }

        ActivityService::log('capa', $capaAction->id, 'capa.started', 'Tindakan dimulai', auth()->user());
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Tindakan dimulai.');
}
```

### 5.2 Submit Verification (in_progress → waiting_verification)

| Property | Value |
|---|---|
| Action Key | `submit_verification` |
| Label | `Submit untuk Verifikasi` |
| Permission | `capa.actions.submit` |
| Requires Reason | No |
| Pre-condition | At least 1 evidence file must be attached |
| Side Effects | Send `capa.submitted_verification` notification to QHSSE team |
| Controller | `CapaActionController::submit()` |

```php
public function submit(CapaAction $capaAction, WorkflowService $workflowService): RedirectResponse
{
    $this->authorize('submit', $capaAction);

    // Check evidence exists
    $evidenceCount = ManagedFile::where('module_name', 'capa')
        ->where('reference_id', $capaAction->id)
        ->where('collection', 'evidence')
        ->whereNull('deleted_at')
        ->count();

    if ($evidenceCount === 0) {
        return back()->withErrors([
            'evidence' => 'Wajib melampirkan minimal 1 bukti sebelum submit verifikasi.'
        ]);
    }

    try {
        $workflowService->transition('capa', $capaAction->id, 'submit_verification', auth()->user());

        ActivityService::log('capa', $capaAction->id, 'capa.submitted_verification',
            'Tindakan di-submit untuk verifikasi', auth()->user());

        // Notify QHSSE team
        $qhsseUsers = User::role(['QHSSE Officer', 'QHSSE Manager'])
            ->where('site_id', $capaAction->site_id)
            ->get();
        NotificationService::notifyMany($qhsseUsers, 'capa.submitted_verification', [...]);
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Tindakan di-submit untuk verifikasi.');
}
```

### 5.3 Verify & Close (waiting_verification → closed)

| Property | Value |
|---|---|
| Action Key | `verify_close` |
| Label | `Verifikasi & Tutup` |
| Permission | `capa.actions.verify` + `capa.actions.close` |
| Requires Reason | Yes (`verification_note`, min 10 chars) |
| Side Effects | Set `verification_note`, `verified_by`, `verified_at`, `closed_at`. Send `capa.verified_closed` notification to PIC and assigned_by. |
| Controller | `CapaActionController::verifyClose()` |

```php
public function verifyClose(
    CapaAction $capaAction,
    WorkflowService $workflowService,
    VerifyCloseCapaActionRequest $request
): RedirectResponse {
    $this->authorize('verify', $capaAction);
    $this->authorize('close', $capaAction);

    try {
        $workflowService->transition(
            'capa',
            $capaAction->id,
            'verify_close',
            auth()->user(),
            $request->verification_note
        );

        $capaAction->update([
            'verification_note' => $request->verification_note,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'closed_at' => now(),
        ]);

        ActivityService::log('capa', $capaAction->id, 'capa.verified_closed',
            'Tindakan diverifikasi & ditutup', auth()->user());

        // Notify PIC and assigner
        NotificationService::notify($capaAction->assignedTo, 'capa.verified_closed', [...]);
        NotificationService::notify($capaAction->assignedBy, 'capa.verified_closed', [...]);
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Tindakan diverifikasi & ditutup.');
}
```

### 5.4 Reject (waiting_verification → rejected)

| Property | Value |
|---|---|
| Action Key | `reject` |
| Label | `Tolak` |
| Permission | `capa.actions.reject` |
| Requires Reason | Yes (`reason`, min 10 chars) |
| Side Effects | Send `capa.rejected` notification to PIC. Reason stored in workflow_histories. |
| Controller | `CapaActionController::reject()` |

```php
public function reject(
    CapaAction $capaAction,
    WorkflowService $workflowService,
    RejectCapaActionRequest $request
): RedirectResponse {
    $this->authorize('reject', $capaAction);

    try {
        $workflowService->transition(
            'capa',
            $capaAction->id,
            'reject',
            auth()->user(),
            $request->reason
        );

        ActivityService::log('capa', $capaAction->id, 'capa.rejected',
            'Tindakan ditolak', auth()->user(), ['reason' => $request->reason]);

        // Notify PIC
        NotificationService::notify($capaAction->assignedTo, 'capa.rejected', [...]);
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Tindakan ditolak.');
}
```

### 5.5 Restart (rejected → in_progress)

| Property | Value |
|---|---|
| Action Key | `restart` |
| Label | `Mulai Ulang` |
| Permission | `capa.actions.update` |
| Requires Reason | No |
| Side Effects | Send notification to QHSSE team that action is restarted. |
| Controller | `CapaActionController::restart()` |

```php
public function restart(CapaAction $capaAction, WorkflowService $workflowService): RedirectResponse
{
    $this->authorize('update', $capaAction);

    try {
        $workflowService->transition('capa', $capaAction->id, 'restart', auth()->user());

        ActivityService::log('capa', $capaAction->id, 'capa.restarted',
            'Tindakan di-restart', auth()->user());

        // Notify QHSSE team
        $qhsseUsers = User::role(['QHSSE Officer', 'QHSSE Manager'])
            ->where('site_id', $capaAction->site_id)
            ->get();
        NotificationService::notifyMany($qhsseUsers, 'capa.restarted', [...]);
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Tindakan di-restart.');
}
```

## 6. Overdue Calculation

Overdue is calculated dynamically, not stored as a column:

```sql
-- SQL
SELECT *,
    CASE
        WHEN due_date IS NOT NULL
         AND due_date < CURRENT_DATE
         AND status NOT IN ('closed', 'rejected')
        THEN true
        ELSE false
    END AS is_overdue
FROM capa_actions;
```

```php
// Laravel — Model scope
public function scopeOverdue(Builder $query): Builder
{
    return $query->whereNotNull('due_date')
        ->where('due_date', '<', now())
        ->whereNotIn('status', ['closed', 'rejected']);
}

// Model helper
public function isOverdue(): bool
{
    return $this->due_date !== null
        && $this->due_date < now()
        && !in_array($this->status, ['closed', 'rejected']);
}

// Days overdue
public function daysOverdue(): ?int
{
    if (!$this->isOverdue()) {
        return null;
    }
    return now()->diffInDays($this->due_date);
}
```

### Overdue Reminder Job

A scheduled daily job checks for overdue actions and sends notifications:

```php
// app/Console/Commands/SendOverdueReminders.php

class SendOverdueReminders implements ShouldQueue
{
    public function handle(): void
    {
        $overdueActions = CapaAction::overdue()->get();

        foreach ($overdueActions as $action) {
            // Avoid spamming — only send if no reminder sent in last 24 hours
            $lastReminder = CoreNotification::where('type', 'capa.overdue_reminder')
                ->where('module_name', 'capa')
                ->where('reference_id', $action->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$lastReminder) {
                NotificationService::notify(
                    $action->assignedTo,
                    'capa.overdue_reminder',
                    [...],
                    null,
                    'capa',
                    $action->id,
                    route('capa.actions.show', $action->id),
                );

                // Also notify supervisor of department and QHSSE Officer
                if ($action->department) {
                    $supervisors = User::role('Supervisor')
                        ->where('department_id', $action->department_id)
                        ->get();
                    NotificationService::notifyMany($supervisors, 'capa.overdue_reminder', [...]);
                }
            }
        }
    }
}
```

### Schedule Registration

```php
// app/Console/Kernel.php or routes/console.php

Schedule::command('capa:send-overdue-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();
```

## 7. Audit Trail

All transitions automatically create:

1. `workflow_histories` record (via `WorkflowService::recordHistory()`)
2. `audit_logs` record with event `workflow.transitioned` (via `AuditService::workflow()`)
3. `activity_logs` record with event `workflow.transitioned` (via `ActivityService::log()`)

### Workflow History Record Example

```json
{
  "workflow_instance_id": 42,
  "module_name": "capa",
  "reference_id": 15,
  "from_status": "in_progress",
  "to_status": "waiting_verification",
  "action_key": "submit_verification",
  "action_label": "Submit untuk Verifikasi",
  "reason": null,
  "actor_id": 7,
  "actor_name": "Budi Santoso",
  "metadata": {},
  "created_at": "2026-07-10T10:30:00Z"
}
```

## 8. Terminal Status Rules

- `closed` is terminal (per `WorkflowService::isTerminalStatus()`). No further transitions allowed.
- `rejected` is terminal but **restartable** — the `restart` transition moves it back to `in_progress`.
- Terminal status sets `workflow_instances.completed_at = now()`.
- For `rejected`, `completed_at` is set when rejected, but cleared when restarted (workflow instance re-opened).
- Once `closed`, the record becomes read-only. No edits, no file deletions, no further transitions.

## 9. Edit Lock by Status

| Status | Editable? | Can Upload Evidence? | Can Delete Evidence? |
|---|---|---|---|
| `open` | ✅ Yes | ✅ Yes | ✅ Yes |
| `in_progress` | ✅ Yes | ✅ Yes | ✅ Yes |
| `waiting_verification` | ❌ No | ❌ No | ❌ No |
| `closed` | ❌ No | ❌ No | ❌ No (except Admin) |
| `rejected` | ✅ Yes | ✅ Yes | ✅ Yes |

```php
// CapaAction model
public function isEditable(): bool
{
    return in_array($this->status, ['open', 'in_progress', 'rejected']);
}

public function canUploadEvidence(): bool
{
    return in_array($this->status, ['open', 'in_progress', 'rejected']);
}
```

## 10. Controller Integration Summary

```php
// Start (open → in_progress)
$this->workflowService->transition('capa', $action->id, 'start', $actor);

// Submit verification (in_progress → waiting_verification)
$this->workflowService->transition('capa', $action->id, 'submit_verification', $actor);

// Verify & close (waiting_verification → closed) — with verification_note
$this->workflowService->transition('capa', $action->id, 'verify_close', $actor, $verificationNote);

// Reject (waiting_verification → rejected) — with reason
$this->workflowService->transition('capa', $action->id, 'reject', $actor, $reason);

// Restart (rejected → in_progress)
$this->workflowService->transition('capa', $action->id, 'restart', $actor);
```

Invalid transition throws `RuntimeException` → caught in controller → flash error via `back()->withErrors(['workflow' => $e->getMessage()])`.
