# Workflow — Permit to Work

## 1. Workflow Definition (To Be Seeded)

The `permit` workflow must be created in `PermitWorkflowSeeder` (Phase 0 did not seed this — it must be added).

| Property | Value |
|---|---|
| `module_name` | `permit` |
| `code` | `PERMIT_WORKFLOW` |
| `name` | `Permit to Work Workflow` |
| `initial_status` | `draft` |
| `is_active` | `true` |

### Seeder Code

```php
// database/seeders/PermitWorkflowSeeder.php

$definition = WorkflowDefinition::create([
    'module_name' => 'permit',
    'code' => 'PERMIT_WORKFLOW',
    'name' => 'Permit to Work Workflow',
    'initial_status' => 'draft',
    'is_active' => true,
]);

$transitions = [
    ['from_status' => 'draft',        'to_status' => 'submitted',     'action_key' => 'submit',   'action_label' => 'Submit',       'requires_reason' => false, 'required_permission' => 'permit.work.submit'],
    ['from_status' => 'submitted',    'to_status' => 'under_review',  'action_key' => 'review',   'action_label' => 'Start Review', 'requires_reason' => false, 'required_permission' => 'permit.work.review'],
    ['from_status' => 'under_review',  'to_status' => 'approved',     'action_key' => 'approve',  'action_label' => 'Approve',      'requires_reason' => false, 'required_permission' => 'permit.work.approve'],
    ['from_status' => 'approved',     'to_status' => 'active',        'action_key' => 'activate', 'action_label' => 'Activate',     'requires_reason' => false, 'required_permission' => 'permit.work.approve'],
    ['from_status' => 'active',       'to_status' => 'closed',       'action_key' => 'close',    'action_label' => 'Close',         'requires_reason' => true,  'required_permission' => 'permit.work.close'],
    ['from_status' => 'submitted',    'to_status' => 'rejected',     'action_key' => 'reject',   'action_label' => 'Reject',       'requires_reason' => true,  'required_permission' => 'permit.work.review'],
    ['from_status' => 'under_review',  'to_status' => 'rejected',     'action_key' => 'reject',   'action_label' => 'Reject',       'requires_reason' => true,  'required_permission' => 'permit.work.review'],
];

foreach ($transitions as $transition) {
    $definition->transitions()->create($transition);
}
```

---

## 2. States

| State | Type | Description |
|---|---|---|
| `draft` | Initial | Izin dibuat, belum di-submit. Bisa diedit. Checklist items sudah dibuat tetapi belum wajib di-sign. |
| `submitted` | Active | Izin dikirim untuk review QHSSE/Supervisor. |
| `under_review` | Active | QHSSE Officer/Manager/Supervisor sedang mereview izin. |
| `approved` | Active | Izin disetujui oleh approver. Checklist dapat di-sign. Belum aktif — harus di-activate setelah semua checklist di-sign. |
| `active` | Active | Izin aktif dan berlaku. Pekerjaan dapat dilakukan dalam periode start_datetime → end_datetime. |
| `closed` | **Terminal** | Izin ditutup setelah pekerjaan selesai. Tidak bisa diedit lagi. |
| `rejected` | **Terminal** | Izin ditolak. Requester dapat membuat izin baru jika diperlukan. |

### State Diagram

```
                                    ┌──────────┐
                                    │  rejected │ (terminal)
                                    └──────────┘
                                         ▲
                                         │ reject (with reason)
                                         │
┌───────┐  submit  ┌──────────┐  review  ┌──────────────┐  approve  ┌──────────┐  activate  ┌────────┐  close  ┌────────┐
│ draft │────────→│ submitted │────────→│ under_review │─────────→│ approved │──────────→│ active │────────→│ closed │
└───────┘         └──────────┘         └──────────────┘           └──────────┘            └────────┘          └────────┘
                         │                     │                                                                             (terminal)
                         └─── reject ──────────┘
                                   (with reason)
```

### Simplified Flow

```
draft ──(submit)──→ submitted ──(review)──→ under_review ──(approve)──→ approved ──(activate)──→ active ──(close)──→ closed
                         │                      │
                         └──(reject)────────────┘──(reject)──→ rejected
```

---

## 3. Transition Table

| # | From | To | Action Key | Label | Requires Reason | Required Permission | Notes |
|---|---|---|---|---|---|---|---|
| 1 | `draft` | `submitted` | `submit` | Submit | ❌ | `permit.work.submit` | Validates mandatory fields |
| 2 | `submitted` | `under_review` | `review` | Start Review | ❌ | `permit.work.review` | |
| 3 | `under_review` | `approved` | `approve` | Approve | ❌ | `permit.work.approve` | Sets `approved_by`, `approved_at`. Conflict of interest check. |
| 4 | `approved` | `active` | `activate` | Activate | ❌ | `permit.work.approve` | **Requires all checklist items signed** |
| 5 | `active` | `closed` | `close` | Close | ✅ | `permit.work.close` | Sets `closed_by`, `closed_at`. Reason required (min 10 chars). |
| 6 | `submitted` | `rejected` | `reject` | Reject | ✅ | `permit.work.review` | Sets `cancellation_reason`. |
| 7 | `under_review` | `rejected` | `reject` | Reject | ✅ | `permit.work.review` | Sets `cancellation_reason`. |

---

## 4. Business Rules Per Transition

### 4.1 Submit (draft → submitted)

**Pre-conditions:**
- `status === 'draft'`
- User has `permit.work.submit`
- All mandatory fields validated:
  - `type`, `title`, `description`, `site_id`, `work_location`, `work_description`
  - `start_datetime` (after_or_equal: now)
  - `end_datetime` (after: start_datetime)

**Post-conditions:**
- `status = 'submitted'`
- Activity log: `permit.submitted`
- Notification: `permit.submitted` → QHSSE Officers + Managers in site scope + Supervisor
- Audit trail: `workflow.transitioned`

### 4.2 Review (submitted → under_review)

**Pre-conditions:**
- `status === 'submitted'`
- User has `permit.work.review`

**Post-conditions:**
- `status = 'under_review'`
- Activity log: `permit.reviewing`
- Notification: `permit.reviewing` → requester

### 4.3 Approve (under_review → approved)

**Pre-conditions:**
- `status === 'under_review'`
- User has `permit.work.approve`
- **Conflict of interest check**: `$actor->id !== $permit->created_by`

**Post-conditions:**
- `status = 'approved'`
- `approved_by = $actor->id`
- `approved_at = now()`
- Activity log: `permit.approved`
- Notification: `permit.approved` → requester
- Message to requester: "Mohon lengkapi checklist dan aktifkan izin sebelum mulai bekerja."

### 4.4 Activate (approved → active)

**Pre-conditions:**
- `status === 'approved'`
- User has `permit.work.approve`
- **All checklist items must be signed**: `PermitChecklist::where('permit_id', $id)->where('is_checked', false)->doesntExist()`

**Post-conditions:**
- `status = 'active'`
- Activity log: `permit.activated`
- Permit is now valid from `start_datetime` to `end_datetime`

**Error if checklist incomplete:**
```
"Semua checklist items harus di-sign sebelum izin dapat diaktifkan. {N} item belum di-sign."
```

### 4.5 Close (active → closed)

**Pre-conditions:**
- `status === 'active'`
- User has `permit.work.close`
- `reason` field provided (min: 10 characters)

**Post-conditions:**
- `status = 'closed'`
- `closed_by = $actor->id`
- `closed_at = now()`
- Activity log: `permit.closed`
- Notification: `permit.closed` → requester, supervisor, contractor (if applicable)
- Record becomes read-only

### 4.6 Reject (submitted/under_review → rejected)

**Pre-conditions:**
- `status IN ('submitted', 'under_review')`
- User has `permit.work.review`
- `reason` field provided (min: 10 characters)

**Post-conditions:**
- `status = 'rejected'`
- `cancellation_reason = $reason`
- Activity log: `permit.rejected`
- Notification: `permit.rejected` → requester
- Record becomes read-only (terminal)

---

## 5. Checklist Signing (Non-Workflow Action)

Checklist signing is NOT a workflow transition — it's a direct record update. It can be performed when the permit is in `submitted`, `under_review`, or `approved` status.

**Rules:**
- User must have `permit.work.update` or `permit.work.approve`
- Sets `is_checked = true`, `checked_by = $actor->id`, `checked_at = now()`
- Audit trail: `checklist.signed` event
- Activity log: `checklist.signed` with item text description
- All items must be signed before activation is allowed

---

## 6. Terminal Status Rules

- `closed` and `rejected` are terminal statuses.
- `workflow_instances.completed_at = now()` is set when a terminal status is reached.
- No further transitions allowed from terminal status.
- Reopen is NOT supported in Phase 9 (can add `closed → draft` transition in future if needed).
- Closed/rejected permits become read-only: no edits, no file deletion, no checklist changes.

---

## 7. Audit Trail

All transitions automatically create:
1. `workflow_histories` record (via `WorkflowService::recordHistory()`)
2. `audit_logs` record with event `workflow.transitioned` (via `AuditService::workflow()`)
3. `activity_logs` record with event `permit.{action}` (via `ActivityService::log()`)

### Audit Events Summary

| Event | Trigger | Module | Records |
|---|---|---|---|
| `permit.created` | Store permit | `permit` | new_values: all fields |
| `permit.updated` | Update permit (draft only) | `permit` | changed fields only |
| `permit.submitted` | Transition: submit | `permit` | status change |
| `permit.reviewing` | Transition: review | `permit` | status change |
| `permit.approved` | Transition: approve | `permit` | status + approved_by + approved_at |
| `permit.activated` | Transition: activate | `permit` | status change |
| `permit.closed` | Transition: close | `permit` | status + reason + closed_by + closed_at |
| `permit.rejected` | Transition: reject | `permit` | status + cancellation_reason |
| `permit.checklist.signed` | Sign checklist item | `permit` | is_checked, checked_by, checked_at |

---

## 8. Controller Integration

```php
// In PermitController.php

public function submit(Permit $permit): RedirectResponse
{
    $this->authorize('submit', $permit);
    $actor = auth()->user();

    try {
        $this->workflowService->transition('permit', $permit->id, 'submit', $actor);
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Izin kerja berhasil dikirim untuk review.');
}

public function review(Permit $permit): RedirectResponse
{
    $this->authorize('review', $permit);
    $actor = auth()->user();

    try {
        $this->workflowService->transition('permit', $permit->id, 'review', $actor);
        $this->notificationService->notify($permit->creator, 'permit.reviewing', [...], $actor, 'permit', $permit->id, route('permits.show', $permit));
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Izin kerja mulai direview.');
}

public function approve(Permit $permit): RedirectResponse
{
    $this->authorize('approve', $permit);
    $actor = auth()->user();

    // Conflict of interest check
    if ($permit->created_by === $actor->id) {
        return back()->withErrors(['approve' => 'Anda tidak dapat menyetujui izin yang Anda ajukan sendiri.']);
    }

    try {
        $this->workflowService->transition('permit', $permit->id, 'approve', $actor);
        $permit->update(['approved_by' => $actor->id, 'approved_at' => now()]);
        $this->notificationService->notify($permit->creator, 'permit.approved', [...], $actor, 'permit', $permit->id, route('permits.show', $permit));
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Izin kerja disetujui. Mohon lengkapi checklist dan aktifkan izin.');
}

public function activate(Permit $permit): RedirectResponse
{
    $this->authorize('approve', $permit);
    $actor = auth()->user();

    // Check all checklist items are signed
    $unsignedCount = PermitChecklist::where('permit_id', $permit->id)
        ->where('is_checked', false)
        ->count();

    if ($unsignedCount > 0) {
        return back()->withErrors([
            'checklist' => "Semua checklist items harus di-sign sebelum izin dapat diaktifkan. {$unsignedCount} item belum di-sign."
        ]);
    }

    try {
        $this->workflowService->transition('permit', $permit->id, 'activate', $actor);
        $this->activityService->log('permit', $permit->id, 'permit.activated', 'Permit activated', $actor);
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Izin kerja telah diaktifkan.');
}

public function reject(Request $request, Permit $permit): RedirectResponse
{
    $this->authorize('review', $permit);
    $actor = auth()->user();

    $validated = $request->validate([
        'reason' => 'required|string|min:10|max:1000',
    ]);

    try {
        $this->workflowService->transition('permit', $permit->id, 'reject', $actor, $validated['reason']);
        $permit->update(['cancellation_reason' => $validated['reason']]);
        $this->notificationService->notify($permit->creator, 'permit.rejected', [...], $actor, 'permit', $permit->id, route('permits.show', $permit));
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Izin kerja telah ditolak.');
}

public function close(Request $request, Permit $permit): RedirectResponse
{
    $this->authorize('close', $permit);
    $actor = auth()->user();

    $validated = $request->validate([
        'reason' => 'required|string|min:10|max:1000',
    ]);

    try {
        $this->workflowService->transition('permit', $permit->id, 'close', $actor, $validated['reason']);
        $permit->update(['closed_by' => $actor->id, 'closed_at' => now()]);
        $this->notificationService->notify($permit->creator, 'permit.closed', [...], $actor, 'permit', $permit->id, route('permits.show', $permit));
    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Izin kerja telah ditutup.');
}

public function signChecklist(Permit $permit, PermitChecklist $checklist): RedirectResponse
{
    $this->authorize('update', $permit);
    $actor = auth()->user();

    abort_if($checklist->permit_id !== $permit->id, 404);
    abort_if(!in_array($permit->status, ['submitted', 'under_review', 'approved']), 400);

    $checklist->update([
        'is_checked' => true,
        'checked_by' => $actor->id,
        'checked_at' => now(),
    ]);

    $this->auditService->log('checklist.signed', $checklist, ['is_checked' => false], ['is_checked' => true, 'checked_by' => $actor->id, 'checked_at' => now()], $actor, 'permit', $permit->id);
    $this->activityService->log('permit', $permit->id, 'checklist.signed', "Checklist item signed: {$checklist->item_text}", $actor);

    return back()->with('success', 'Checklist item telah ditandatangani.');
}
```

---

## 9. Validity Period & Expiry Management

### Validity Status Computation

```php
// Computed at query time or via accessor
public function getValidityStatusAttribute(): string
{
    if ($this->status !== 'active') {
        return 'not_started';
    }

    $now = now();

    if ($now > $this->end_datetime) {
        return 'expired';
    }

    if ($now >= $this->start_datetime && $now <= $this->end_datetime) {
        if ($this->end_datetime->diffInHours($now) <= 24) {
            return 'expiring_soon';
        }
        return 'active';
    }

    return 'not_started';
}
```

### Expiry Check Scheduled Command

```php
// app/Console/Commands/CheckPermitExpiry.php
// Runs hourly via Laravel Scheduler

Schedule::command('permit:check-expiry')->hourly();
```

The command:
1. Finds all permits where `status = 'active'` and `end_datetime` is within the next 24 hours.
2. Sends `permit.expiring_soon` notification to: requester, QHSSE Officers in site scope, Supervisor.
3. Does NOT auto-close expired permits — closure must be manual by QHSSE Officer/Manager.

### Expired Permit Handling

- If `now() > end_datetime` and `status = 'active'`, the permit is marked as **Expired** in the UI (red badge).
- The permit remains `active` in the database until manually closed by an authorized user.
- The system does NOT auto-close or auto-transition expired permits.
