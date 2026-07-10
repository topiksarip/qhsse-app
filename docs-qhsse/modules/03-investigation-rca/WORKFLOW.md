# Workflow — Investigation & RCA

## 1. Workflow Definition (To Be Seeded)

The `investigation` workflow is **not yet seeded** in `WorkflowSeeder`. It must be added as part of Phase 2 implementation.

| Property | Value |
|---|---|
| `module_name` | `investigation` |
| `code` | `INVESTIGATION_WORKFLOW` |
| `name` | `Investigation Workflow` |
| `initial_status` | `draft` |
| `is_active` | `true` |

### Seeder Code

Add to `WorkflowSeeder` (or create `InvestigationWorkflowSeeder`):

```php
<?php

// In WorkflowSeeder or InvestigationWorkflowSeeder

$definition = WorkflowDefinition::updateOrCreate(
    ['module_name' => 'investigation', 'code' => 'INVESTIGATION_WORKFLOW'],
    [
        'name'           => 'Investigation Workflow',
        'initial_status' => 'draft',
        'is_active'       => true,
    ]
);

// State: draft (initial)
// State: in_progress (active)
// State: completed (terminal)
// State: cancelled (terminal)

// Transition: draft → in_progress (start)
$definition->transitions()->updateOrCreate(
    ['from_status' => 'draft', 'action_key' => 'start'],
    [
        'from_status'        => 'draft',
        'to_status'          => 'in_progress',
        'action_key'         => 'start',
        'action_label'       => 'Mulai Investigasi',
        'requires_reason'    => false,
        'required_permission' => 'investigation.reports.submit',
        'is_active'          => true,
    ]
);

// Transition: in_progress → completed (complete)
$definition->transitions()->updateOrCreate(
    ['from_status' => 'in_progress', 'action_key' => 'complete'],
    [
        'from_status'        => 'in_progress',
        'to_status'          => 'completed',
        'action_key'         => 'complete',
        'action_label'       => 'Selesaikan Investigasi',
        'requires_reason'    => true,
        'required_permission' => 'investigation.reports.close',
        'is_active'          => true,
    ]
);

// Transition: draft → cancelled (cancel)
$definition->transitions()->updateOrCreate(
    ['from_status' => 'draft', 'action_key' => 'cancel'],
    [
        'from_status'        => 'draft',
        'to_status'          => 'cancelled',
        'action_key'         => 'cancel',
        'action_label'       => 'Batalkan Investigasi',
        'requires_reason'    => true,
        'required_permission' => 'investigation.reports.update',
        'is_active'          => true,
    ]
);

// Transition: in_progress → cancelled (cancel)
$definition->transitions()->updateOrCreate(
    ['from_status' => 'in_progress', 'action_key' => 'cancel'],
    [
        'from_status'        => 'in_progress',
        'to_status'          => 'cancelled',
        'action_key'         => 'cancel',
        'action_label'       => 'Batalkan Investigasi',
        'requires_reason'    => true,
        'required_permission' => 'investigation.reports.update',
        'is_active'          => true,
    ]
);
```

---

## 2. States

| State | Type | Description |
|---|---|---|
| `draft` | **Initial** | Investigasi dibuat, belum dimulai. Bisa diedit. RCA tools belum wajib diisi. |
| `in_progress` | **Active** | Investigasi sedang berjalan. Investigator melakukan analisis 5-Why, fishbone, timeline. Bisa diedit. |
| `completed` | **Terminal** | Investigasi selesai. Root cause dan rekomendasi sudah final. Tidak bisa diedit. |
| `cancelled` | **Terminal** | Investigasi dibatalkan. Tidak bisa dilanjutkan. Tersimpan untuk historical reference. |

---

## 3. Transition Table

| # | From | To | Action Key | Label | Requires Reason | Required Permission | Notes |
|---|---|---|---|---|---|---|---|
| 1 | `draft` | `in_progress` | `start` | Mulai Investigasi | ❌ | `investigation.reports.submit` | Validates: title, incident_id, five_whys (min 1), fishbone (min 1 cause). Sets `started_at`. |
| 2 | `in_progress` | `completed` | `complete` | Selesaikan Investigasi | ✅ | `investigation.reports.close` | Validates: root_cause not empty, recommendations not empty. Sets `completed_at`. |
| 3 | `draft` | `cancelled` | `cancel` | Batalkan Investigasi | ✅ | `investigation.reports.update` | |
| 4 | `in_progress` | `cancelled` | `cancel` | Batalkan Investigasi | ✅ | `investigation.reports.update` | |

---

## 4. Phase 2 Workflow Path

```
                                  ┌──(complete, reason)──→ completed ✅
                                  │
draft ──(start)──→ in_progress ───┤
  │                               │
  │                               └──(cancel, reason)──→ cancelled ❌
  │
  └──(cancel, reason)──→ cancelled ❌
```

### Simplified flow:

```
draft →(start)→ in_progress →(complete, reason)→ completed
draft →(cancel, reason)→ cancelled
in_progress →(cancel, reason)→ cancelled
```

### State diagram (visual):

```
         ┌──────────┐
         │   draft  │ ← Initial
         └────┬─────┘
              │
     ┌────────┼────────┐
     │ start  │        │ cancel (reason)
     │        │        │
     ▼        │        ▼
┌──────────┐  │  ┌───────────┐
│in_progress│  │  │ cancelled  │ ← Terminal
└────┬─────┘  │  └───────────┘
     │        │
     │ cancel │
     │(reason)│
     │        │
     ▼        ▼
┌──────────┐ ┌───────────┐
│completed  │ │ cancelled  │
└──────────┘ └───────────┘
 Terminal     Terminal
```

---

## 5. Transition Validation Rules

### 5.1 `start` (draft → in_progress)

Before the workflow transition is executed, the controller validates:

| Validation | Rule | Error Key |
|---|---|---|
| Status must be `draft` | `if ($investigation->status !== 'draft')` | `workflow` |
| `title` is not empty | Already required at create | — |
| `incident_id` is valid | Already required at create | — |
| `investigator_id` is set | Already required at create | — |
| `five_whys` has min 1 item | `count($investigation->five_whys) >= 1` | `five_whys` |
| `fishbone` has min 1 cause across all categories | Collect causes, check non-empty | `fishbone` |

If validation passes:
1. `WorkflowService::transition('investigation', $id, 'start', $actor)` — changes status to `in_progress`
2. `$investigation->update(['started_at' => now()])`
3. `ActivityService::log(...)` — logs `investigation.started`
4. `NotificationService::notifyMany(...)` — sends `investigation.started` notification

### 5.2 `complete` (in_progress → completed)

Before the workflow transition is executed, the controller validates:

| Validation | Rule | Error Key |
|---|---|---|
| Status must be `in_progress` | `if ($investigation->status !== 'in_progress')` | `workflow` |
| `reason` field is provided | `required|string|min:10|max:1000` | `reason` |
| `root_cause` is not empty | `!empty($investigation->root_cause)` | `root_cause` |
| `recommendations` is not empty | `!empty($investigation->recommendations)` | `recommendations` |

If validation passes:
1. `WorkflowService::transition('investigation', $id, 'complete', $actor, $reason)` — changes status to `completed`
2. `$investigation->update(['completed_at' => now()])`
3. `ActivityService::log(...)` — logs `investigation.completed`
4. `NotificationService::notifyMany(...)` — sends `investigation.completed` notification

### 5.3 `cancel` (draft/in_progress → cancelled)

Before the workflow transition is executed, the controller validates:

| Validation | Rule | Error Key |
|---|---|---|
| Status must be `draft` or `in_progress` | `if (!in_array($investigation->status, ['draft', 'in_progress']))` | `workflow` |
| `reason` field is provided | `required|string|min:10|max:1000` | `reason` |

If validation passes:
1. `WorkflowService::transition('investigation', $id, 'cancel', $actor, $reason)` — changes status to `cancelled`
2. `ActivityService::log(...)` — logs `investigation.cancelled`
3. `NotificationService::notifyMany(...)` — sends `investigation.cancelled` notification

---

## 6. Terminal Status Rules

- `completed` and `cancelled` are terminal (per `WorkflowService::isTerminalStatus()`).
- Terminal status sets `workflow_instances.completed_at = now()`.
- **No further transitions allowed** from terminal status.
- Once `completed`:
  - Record becomes read-only (no edit, no file delete).
  - Root cause and recommendations are final.
  - Investigation report can be exported.
- Once `cancelled`:
  - Record becomes read-only.
  - Tersimpan untuk historical reference.
  - Tidak dapat di-reopen.
- **Reopen is NOT supported** in Phase 2 (can add `completed → in_progress` transition in future if needed).

---

## 7. Audit Trail

All transitions automatically create three records:

### 7.1 `workflow_histories` record

Created by `WorkflowService::recordHistory()`:

```php
[
    'workflow_instance_id' => $instance->id,
    'module_name'          => 'investigation',
    'reference_id'         => $investigation->id,
    'from_status'          => 'draft',
    'to_status'            => 'in_progress',
    'action_key'           => 'start',
    'action_label'         => 'Mulai Investigasi',
    'reason'               => null, // or reason for complete/cancel
    'actor_id'             => $actor->id,
    'metadata'             => json_encode([...]),
]
```

### 7.2 `audit_logs` record

Created by `AuditService::workflow()`:

```php
[
    'event'           => 'workflow.transitioned',
    'auditable_type'   => 'Investigation',
    'auditable_id'     => $investigation->id,
    'module_name'      => 'investigation',
    'reference_id'     => $investigation->id,
    'actor_id'         => $actor->id,
    'actor_name'       => $actor->name,
    'old_values'       => json_encode(['status' => 'draft']),
    'new_values'       => json_encode(['status' => 'in_progress']),
    'metadata'         => json_encode(['action_key' => 'start', 'reason' => null]),
]
```

### 7.3 `activity_logs` record

Created by `ActivityService::log()`:

```php
[
    'module_name'  => 'investigation',
    'reference_id' => $investigation->id,
    'event'        => 'investigation.started',
    'description'  => 'Investigasi dimulai oleh ' . $actor->name,
    'actor_id'     => $actor->id,
    'actor_name'   => $actor->name,
    'properties'   => json_encode(['from_status' => 'draft', 'to_status' => 'in_progress']),
]
```

---

## 8. Controller Integration

```php
// ── Start Investigation ──────────────────────────────────────

public function start(Investigation $investigation, Request $request): RedirectResponse
{
    $actor = $request->user();

    // Validate status
    if ($investigation->status !== 'draft') {
        return back()->withErrors(['workflow' => 'Hanya investigasi berstatus draft yang dapat dimulai.']);
    }

    // Validate mandatory RCA data
    if (empty($investigation->five_whys) || count($investigation->five_whys) < 1) {
        return back()->withErrors(['five_whys' => 'Analisis 5-Why wajib diisi minimal 1 level.']);
    }

    $hasFishboneCause = collect($investigation->fishbone ?? [])
        ->pluck('causes')
        ->flatten()
        ->isNotEmpty();

    if (!$hasFishboneCause) {
        return back()->withErrors(['fishbone' => 'Fishbone wajib diisi minimal 1 penyebab.']);
    }

    try {
        // Workflow transition
        $this->workflowService->transition('investigation', $investigation->id, 'start', $actor);

        // Set started_at
        $investigation->update(['started_at' => now()]);

        // Activity log
        $this->activityService->log(
            'investigation',
            $investigation->id,
            'investigation.started',
            'Investigasi dimulai oleh ' . $actor->name,
            $actor
        );

        // Notification
        $this->notifyStakeholders($investigation, 'investigation.started', $actor);

    } catch (\RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Investigasi berhasil dimulai.');
}

// ── Complete Investigation ────────────────────────────────────

public function complete(Investigation $investigation, Request $request): RedirectResponse
{
    $actor = $request->user();
    $validated = $request->validate([
        'reason' => 'required|string|min:10|max:1000',
    ]);

    // Validate status
    if ($investigation->status !== 'in_progress') {
        return back()->withErrors(['workflow' => 'Hanya investigasi berstatus in_progress yang dapat diselesaikan.']);
    }

    // Validate root_cause and recommendations
    if (empty($investigation->root_cause)) {
        return back()->withErrors(['root_cause' => 'Root cause wajib diisi sebelum menyelesaikan investigasi.']);
    }

    if (empty($investigation->recommendations)) {
        return back()->withErrors(['recommendations' => 'Rekomendasi wajib diisi sebelum menyelesaikan investigasi.']);
    }

    try {
        // Workflow transition (with reason)
        $this->workflowService->transition(
            'investigation',
            $investigation->id,
            'complete',
            $actor,
            $validated['reason']
        );

        // Set completed_at
        $investigation->update(['completed_at' => now()]);

        // Activity log
        $this->activityService->log(
            'investigation',
            $investigation->id,
            'investigation.completed',
            'Investigasi diselesaikan oleh ' . $actor->name . '. Alasan: ' . $validated['reason'],
            $actor,
            ['reason' => $validated['reason']]
        );

        // Notification
        $this->notifyStakeholders($investigation, 'investigation.completed', $actor, [
            'reason' => $validated['reason'],
        ]);

    } catch (\RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Investigasi berhasil diselesaikan.');
}

// ── Cancel Investigation ──────────────────────────────────────

public function cancel(Investigation $investigation, Request $request): RedirectResponse
{
    $actor = $request->user();
    $validated = $request->validate([
        'reason' => 'required|string|min:10|max:1000',
    ]);

    // Validate status
    if (!in_array($investigation->status, ['draft', 'in_progress'])) {
        return back()->withErrors(['workflow' => 'Investigasi tidak dapat dibatalkan pada status ini.']);
    }

    try {
        // Workflow transition (with reason)
        $this->workflowService->transition(
            'investigation',
            $investigation->id,
            'cancel',
            $actor,
            $validated['reason']
        );

        // Activity log
        $this->activityService->log(
            'investigation',
            $investigation->id,
            'investigation.cancelled',
            'Investigasi dibatalkan oleh ' . $actor->name . '. Alasan: ' . $validated['reason'],
            $actor,
            ['reason' => $validated['reason']]
        );

        // Notification
        $this->notifyStakeholders($investigation, 'investigation.cancelled', $actor, [
            'reason' => $validated['reason'],
        ]);

    } catch (\RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Investigasi telah dibatalkan.');
}
```

Invalid transition throws `RuntimeException` → caught in controller → flash error via `back()->withErrors(['workflow' => $e->getMessage()])`.

---

## 9. Available Transitions (Show Page)

The Show page displays available workflow transitions based on current status and user permissions. These are fetched via `WorkflowService::getAvailableTransitions()`:

```php
$availableTransitions = $this->workflowService->getAvailableTransitions(
    'investigation',
    $investigation->id
);
```

### Returns:

```php
// When status = draft:
[
    ['action_key' => 'start', 'action_label' => 'Mulai Investigasi', 'requires_reason' => false],
    ['action_key' => 'cancel', 'action_label' => 'Batalkan Investigasi', 'requires_reason' => true],
]

// When status = in_progress:
[
    ['action_key' => 'complete', 'action_label' => 'Selesaikan Investigasi', 'requires_reason' => true],
    ['action_key' => 'cancel', 'action_label' => 'Batalkan Investigasi', 'requires_reason' => true],
]

// When status = completed or cancelled:
[] // No transitions available (terminal)
```

### Frontend rendering:

Action buttons on the Show page are rendered based on `available_transitions` and user permissions:

```tsx
{availableTransitions.map(transition => {
    if (!can[transition.action_key]) return null;

    return (
        <button
            key={transition.action_key}
            onClick={() => openModal(transition)}
            className={transition.action_key === 'cancel'
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            }
        >
            {transition.action_label}
        </button>
    );
})}
```

When `requires_reason` is true, clicking the button opens a modal dialog for the user to input a reason before confirming the transition.

---

## 10. Cross-Module Workflow Interaction

### 10.1 Incident → Investigation

When an investigation is created from an incident, the incident's workflow may also transition:

```php
// In InvestigationController::store()
$incident = Incident::findOrFail($request->incident_id);

if ($incident->status === 'under_review') {
    // Transition incident to 'investigation' status
    $this->workflowService->transition('incident', $incident->id, 'investigate', $actor);
}
```

This uses the seeded `investigate` transition on the incident workflow (`under_review → investigation`).

### 10.2 Investigation → CAPA (Phase 3, future)

When a CAPA record is created from an investigation recommendation (Phase 3):

```php
// Phase 3: POST /investigations/{id}/create-capa
$capa = CapaAction::create([
    'source_module'      => 'investigation',
    'source_reference_id' => $investigation->id,
    // ... other CAPA fields
]);

// Investigation status does NOT change — it remains 'completed'
// CAPA has its own workflow (open → in_progress → waiting_verification → closed)
```

The investigation does NOT transition when a CAPA is created. The investigation's workflow is independent of the CAPA workflow. The link is via `source_module` and `source_reference_id` on the CAPA record.

---

## 11. Numbering Integration

The investigation number is generated on **create** (not on start/submit):

```php
// In InvestigationController::store()

$investigation = Investigation::create([
    ...$data,
    'status' => 'draft',
]);

// Generate number immediately
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'investigation',
    actor: $actor,
    referenceType: Investigation::class,
    referenceId: $investigation->id,
);

$investigation->update(['investigation_number' => $generatedNumber->number]);
```

Numbering format (already seeded in `numbering_formats`):

| Property | Value |
|---|---|
| `module_name` | `investigation` |
| `prefix` | `INV` |
| `padding` | `4` |
| `separator` | `-` |
| `reset_frequency` | `yearly` |
| `include_year` | `true` |
| `include_site_code` | `false` |
| `sample` | `INV-2026-0001` |

---

## 12. Workflow Instance Lifecycle

```
┌─────────────────────────────────────────────────────────┐
│                  Workflow Instance                        │
│                                                          │
│  Created: when Investigation is created (store)          │
│  Started by: auth user                                   │
│  Current status: mirrors investigations.status           │
│  Completed at: set when status = completed/cancelled      │
│                                                          │
│  ┌──────────────────────────────────────────────────┐    │
│  │            workflow_histories (1:N)               │    │
│  │                                                   │    │
│  │  1. draft → in_progress (start)                   │    │
│  │     actor: QHSSE Officer                          │    │
│  │     reason: null                                   │    │
│  │                                                   │    │
│  │  2. in_progress → completed (complete)            │    │
│  │     actor: QHSSE Officer/Manager                  │    │
│  │     reason: "Root cause teridentifikasi..."       │    │
│  │                                                   │    │
│  │  -- OR --                                         │    │
│  │                                                   │    │
│  │  2. in_progress → cancelled (cancel)             │    │
│  │     actor: QHSSE Officer/Manager                  │    │
│  │     reason: "Keterbatasan data dan saksi..."      │    │
│  └──────────────────────────────────────────────────┘    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```
