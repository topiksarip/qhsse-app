# Workflow — Incident Reporting

## 1. Workflow Definition (Already Seeded)

The `incident` workflow is pre-seeded in `WorkflowSeeder` (Phase 0).

| Property | Value |
|---|---|
| `module_name` | `incident` |
| `code` | `INCIDENT_WORKFLOW` |
| `name` | `Incident Workflow` |
| `initial_status` | `draft` |
| `is_active` | `true` |

## 2. States

| State | Type | Description |
|---|---|---|
| `draft` | Initial | Laporan dibuat, belum disubmit. Bisa diedit. |
| `submitted` | Active | Laporan dikirim untuk review QHSSE. |
| `under_review` | Active | QHSSE team sedang review laporan. |
| `investigation` | Active (Phase 2) | Investigasi sedang berjalan (Phase 2 — Investigation & RCA). |
| `action_open` | Active (Phase 2) | Corrective action dibuka (Phase 2 — CAPA). |
| `closed` | **Terminal** | Laporan selesai. Tidak bisa diedit lagi. |
| `rejected` | **Terminal** | Laporan ditolak. |

## 3. Transition Table (Seeded)

| From | To | Action Key | Label | Requires Reason | Required Permission |
|---|---|---|---|---|---|
| `draft` | `submitted` | `submit` | Submit | ❌ | `incident.reports.submit` |
| `submitted` | `under_review` | `review` | Start Review | ❌ | `incident.reports.review` |
| `under_review` | `investigation` | `investigate` | Start Investigation | ❌ | `core.workflow.transition` |
| `under_review` | `action_open` | `open_action` | Open Action | ❌ | `core.workflow.transition` |
| `investigation` | `action_open` | `open_action` | Open Action | ❌ | `core.workflow.transition` |
| `action_open` | `closed` | `close` | Close | ✅ | `incident.reports.close` |
| `submitted` | `rejected` | `reject` | Reject | ✅ | `core.workflow.transition` |
| `under_review` | `rejected` | `reject` | Reject | ✅ | `core.workflow.transition` |

## 4. Phase 1 Simplified Path

Phase 1 uses only the first 3 transitions + close:

```
draft ──(submit)──→ submitted ──(review)──→ under_review ──(close)──→ closed
                                                                      ↘(reject)──→ rejected
```

### Phase 1 Issue: Close from under_review

The seeded `close` transition goes from `action_open → closed`, NOT from `under_review → closed`.

**Solution:** Add a direct transition `under_review → closed` with action_key `close` for Phase 1.

Patch in `WorkflowSeeder` or create `IncidentReportingSeeder` that adds:

```php
$definition->transitions()->updateOrCreate(
    ['from_status' => 'under_review', 'action_key' => 'close'],
    [
        'from_status' => 'under_review',
        'to_status' => 'closed',
        'action_key' => 'close',
        'action_label' => 'Close',
        'requires_reason' => true,
        'required_permission' => 'incident.reports.close',
        'is_active' => true,
    ],
);
```

### Future phases unlock:

- **Phase 2 (Investigation):** `under_review → investigation` (investigate)
- **Phase 2 (CAPA):** `under_review → action_open` (open_action), `investigation → action_open`
- **Reject:** available from `submitted` and `under_review`

## 5. Audit Trail

All transitions automatically create:
1. `workflow_histories` record (via `WorkflowService::recordHistory()`)
2. `audit_logs` record with event `workflow.transitioned` (via `AuditService::workflow()`)
3. `activity_logs` record with event `workflow.transitioned` (via `ActivityService::log()`)

## 6. Terminal Status Rules

- `closed` and `rejected` are terminal (per `WorkflowService::isTerminalStatus()`)
- Terminal status sets `workflow_instances.completed_at = now()`
- No further transitions allowed from terminal status
- Reopen is NOT supported in Phase 1 (can add `closed → draft` transition in future if needed)

## 7. Controller Integration

```php
// Submit
$this->workflowService->transition('incident', $incident->id, 'submit', $actor);

// Review
$this->workflowService->transition('incident', $incident->id, 'review', $actor);

// Close (with reason)
$this->workflowService->transition('incident', $incident->id, 'close', $actor, $reason);
```

Invalid transition throws `RuntimeException` → caught in controller → flash error.
