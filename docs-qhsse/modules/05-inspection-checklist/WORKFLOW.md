# Workflow — Inspection Checklist

## 1. Workflow Definition (Proposed — Add to WorkflowSeeder)

The `inspection` workflow is **not yet seeded** in `WorkflowSeeder`. This module proposes a new workflow definition to be added.

| Property | Value |
|---|---|
| `module_name` | `inspection` |
| `code` | `INSPECTION_WORKFLOW` |
| `name` | `Inspection Workflow` |
| `initial_status` | `pending` |
| `is_active` | `true` |

### Seeder Code (to add to WorkflowSeeder)

```php
// In WorkflowSeeder::run()
$inspectionDefinition = WorkflowDefinition::create([
    'module_name' => 'inspection',
    'code' => 'INSPECTION_WORKFLOW',
    'name' => 'Inspection Workflow',
    'initial_status' => 'pending',
    'is_active' => true,
]);

// Transitions
$inspectionDefinition->transitions()->createMany([
    [
        'from_status' => 'pending',
        'to_status' => 'in_progress',
        'action_key' => 'start',
        'action_label' => 'Mulai Inspeksi',
        'requires_reason' => false,
        'required_permission' => 'inspection.checklists.execute',
        'is_active' => true,
    ],
    [
        'from_status' => 'in_progress',
        'to_status' => 'completed',
        'action_key' => 'complete',
        'action_label' => 'Selesaikan Inspeksi',
        'requires_reason' => false,
        'required_permission' => 'inspection.checklists.execute',
        'is_active' => true,
    ],
]);
```

---

## 2. States

| State | Type | Description |
|---|---|---|
| `pending` | **Initial** | Inspeksi dibuat, belum dimulai. Inspector belum menjawab item. |
| `in_progress` | Active | Inspeksi sedang berjalan. Inspector menjawab item-item. Bisa save partial. |
| `completed` | **Terminal** | Inspeksi selesai. Hasil sudah dihitung. Tidak bisa diedit lagi. |

### State Descriptions (Indonesian)

| State | Deskripsi |
|---|---|
| `pending` | Inspeksi telah dibuat dan dijadwalkan, tetapi inspector belum memulai eksekusi. Pada tahap ini, inspector masih bisa mengubah jadwal atau re-assign. |
| `in_progress` | Inspector sedang aktif melakukan inspeksi. Item-item dapat dijawab secara bertahap (partial save). Hasil belum final. |
| `completed` | Inspeksi telah diselesaikan. `overall_result` sudah dihitung (`pass` atau `fail`). Jika ada item `unsafe`, link untuk membuat CAPA muncul. Tidak ada perubahan lebih lanjut. |

---

## 3. Transition Table

| From | To | Action Key | Label | Requires Reason | Required Permission | Notes |
|---|---|---|---|---|---|---|
| `pending` | `in_progress` | `start` | Mulai Inspeksi | ❌ | `inspection.checklists.execute` | Sets `executed_at = now()` |
| `in_progress` | `completed` | `complete` | Selesaikan Inspeksi | ❌ | `inspection.checklists.execute` | Calculates `overall_result`, validates required items |

### Transition Diagram

```
pending ──(start)──→ in_progress ──(complete)──→ completed
```

### Phase 4 Simplified Path

Phase 4 uses only 2 transitions:

```
pending ──(start)──→ in_progress ──(complete)──→ completed
```

No reject, no cancel, no reopen in Phase 4. If an inspection is created by mistake, it can be left in `pending` state (it will show as overdue in dashboard if scheduled date passes).

---

## 4. Transition Details

### 4.1 Start (pending → in_progress)

**Pre-conditions:**
- `inspection.status === 'pending'`
- User has `inspection.checklists.execute` permission
- User is the assigned inspector OR has QHSSE Officer/Manager/Admin role

**Side effects:**
- `executed_at` set to current timestamp
- `workflow_instances.current_status` updated to `in_progress`
- `workflow_histories` record created
- `activity_logs` record: `inspection.started`
- `audit_logs` record: event=`started`, old_values=`{status: pending}`, new_values=`{status: in_progress, executed_at: ...}`

**Controller code:**

```php
public function start(Inspection $inspection): RedirectResponse
{
    $this->authorize('execute', $inspection);

    $actor = Auth::user();

    try {
        $this->workflowService->transition(
            'inspection',
            $inspection->id,
            'start',
            $actor,
        );

        $inspection->update(['executed_at' => now()]);

        $this->activityService->log(
            'inspection',
            $inspection->id,
            'inspection.started',
            "Inspeksi {$inspection->inspection_number} dimulai oleh {$actor->name}",
            $actor,
        );

        $this->auditService->log(
            'started',
            $inspection,
            ['status' => 'pending'],
            ['status' => 'in_progress', 'executed_at' => now()->toIso8601String()],
            $actor,
            'inspection',
            $inspection->id,
        );

    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return redirect()->route('inspection.inspections.execute', $inspection)
        ->with('success', 'Inspeksi dimulai.');
}
```

### 4.2 Complete (in_progress → completed)

**Pre-conditions:**
- `inspection.status === 'in_progress'`
- User has `inspection.checklists.execute` permission
- All items with `is_required=true` have non-null `answer` (validation check)
- User is the assigned inspector OR has QHSSE Officer/Manager/Admin role

**Side effects:**
- `overall_result` calculated: `fail` if any `is_unsafe=true`, else `pass`
- `notes` updated if provided
- `workflow_instances.current_status` updated to `completed`
- `workflow_instances.completed_at` set
- `workflow_histories` record created
- `activity_logs` record: `inspection.completed`
- `audit_logs` record: event=`completed`
- If `overall_result='fail'`: `NotificationService::notifyMany($qhsseManagers, 'inspection.unsafe_found', [...])`
- `NotificationService::notifyMany($qhsseManagers, 'inspection.completed', [...])`

**Controller code:**

```php
public function complete(CompleteInspectionRequest $request, Inspection $inspection): RedirectResponse
{
    $this->authorize('execute', $inspection);

    $actor = Auth::user();

    // Validate all required items have answers
    $unansweredRequired = $inspection->results()
        ->whereHas('item', fn ($q) => $q->where('is_required', true))
        ->whereNull('answer')
        ->count();

    if ($unansweredRequired > 0) {
        return back()->withErrors([
            'complete' => "Masih ada {$unansweredRequired} item wajib yang belum dijawab."
        ]);
    }

    // Calculate overall result
    $hasUnsafe = $inspection->results()->where('is_unsafe', true)->exists();
    $overallResult = $hasUnsafe ? 'fail' : 'pass';

    try {
        $this->workflowService->transition(
            'inspection',
            $inspection->id,
            'complete',
            $actor,
        );

        $inspection->update([
            'overall_result' => $overallResult,
            'notes' => $request->input('notes', $inspection->notes),
        ]);

        $this->activityService->log(
            'inspection',
            $inspection->id,
            'inspection.completed',
            "Inspeksi {$inspection->inspection_number} diselesaikan oleh {$actor->name}. Hasil: {$overallResult}.",
            $actor,
        );

        $this->auditService->log(
            'completed',
            $inspection,
            ['status' => 'in_progress', 'overall_result' => 'pending'],
            ['status' => 'completed', 'overall_result' => $overallResult],
            $actor,
            'inspection',
            $inspection->id,
        );

        // Notifications
        $qhsseManagers = $this->getQhsseManagersForSite($inspection->site_id);

        $this->notificationService->notifyMany(
            $qhsseManagers,
            'inspection.completed',
            [
                'inspection_number' => $inspection->inspection_number,
                'template_name' => $inspection->template->name,
                'site_name' => $inspection->site->name,
                'inspector_name' => $actor->name,
                'overall_result' => $overallResult,
            ],
            $actor,
            'inspection',
            $inspection->id,
            route('inspection.inspections.show', $inspection),
        );

        if ($hasUnsafe) {
            $unsafeCount = $inspection->results()->where('is_unsafe', true)->count();

            $this->notificationService->notifyMany(
                $qhsseManagers,
                'inspection.unsafe_found',
                [
                    'inspection_number' => $inspection->inspection_number,
                    'template_name' => $inspection->template->name,
                    'site_name' => $inspection->site->name,
                    'count' => $unsafeCount,
                ],
                $actor,
                'inspection',
                $inspection->id,
                route('inspection.inspections.show', $inspection),
            );
        }

    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return redirect()->route('inspection.inspections.show', $inspection)
        ->with('success', 'Inspeksi diselesaikan.');
}
```

---

## 5. Audit Trail

All transitions automatically create:

1. **`workflow_histories`** record (via `WorkflowService::recordHistory()`)
   - `from_status`, `to_status`, `action_key`, `action_label`, `actor_id`

2. **`audit_logs`** record (via `AuditService::log()`)
   - event: `started` or `completed`
   - old_values: previous status + fields
   - new_values: new status + changed fields

3. **`activity_logs`** record (via `ActivityService::log()`)
   - event: `inspection.started` or `inspection.completed`
   - description: human-readable message

### Audit Trail Events Summary

| Event | Trigger | Auditable | Recorded Data |
|---|---|---|---|
| `template.created` | Template created | InspectionTemplate | new_values: all fields + items |
| `template.updated` | Template updated | InspectionTemplate | changed fields only |
| `template.deleted` | Template deleted | InspectionTemplate | soft delete |
| `inspection.created` | Inspection created | Inspection | new_values: all fields |
| `inspection.started` | Start transition | Inspection | status: pending → in_progress, executed_at |
| `inspection.completed` | Complete transition | Inspection | status: in_progress → completed, overall_result |
| `inspection.result.saved` | Result saved | InspectionResult | answer, remark, is_unsafe |
| `inspection.file.uploaded` | File uploaded | ManagedFile | new_values |
| `inspection.file.deleted` | File deleted | ManagedFile | soft delete |

---

## 6. Terminal Status Rules

- `completed` is terminal (per `WorkflowService::isTerminalStatus()`)
- Terminal status sets `workflow_instances.completed_at = now()`
- No further transitions allowed from terminal status
- Reopen is NOT supported in Phase 4 (can add `completed → in_progress` transition in future if needed)
- Once completed, inspection results cannot be edited
- Once completed, evidence files cannot be deleted (except by Super Admin / Admin)

---

## 7. Numbering Integration

The `inspection` numbering format is already seeded in `numbering_formats`:

| Property | Value |
|---|---|
| `module_name` | `inspection` |
| `prefix` | `INS` |
| `padding` | `4` |
| `separator` | `-` |
| `reset_frequency` | `yearly` |
| `include_year` | `true` |
| `include_site_code` | `false` |
| `sample` | `INS-2026-0001` |

Number is generated at inspection creation (not at start or complete):

```php
$generatedNumber = app(NumberingService::class)->generate(
    moduleName: 'inspection',
    actor: $actor,
    referenceType: Inspection::class,
    referenceId: $inspection->id,
);

$inspection->update(['inspection_number' => $generatedNumber->number]);
```

---

## 8. Inspection Execution Flow

### Full Lifecycle

```
1. QHSSE Officer creates inspection
   ├── Selects active template
   ├── Selects site, area (optional), inspector, scheduled date
   ├── NumberingService generates INS-YYYY-NNNN
   ├── WorkflowService.start() creates workflow instance (status=pending)
   ├── Empty InspectionResult records created for each template item
   ├── AuditService.created()
   └── ActivityService.log('inspection.created')

2. Inspector starts inspection (transition: pending → in_progress)
   ├── WorkflowService.transition('start')
   ├── executed_at = now()
   ├── ActivityService.log('inspection.started')
   └── AuditService.log('started')

3. Inspector answers items (partial save, no workflow transition)
   ├── PUT /inspections/{id} with results array
   ├── For each result:
   │   ├── Update InspectionResult.answer and .remark
   │   ├── Auto-calculate is_unsafe based on item type + answer
   │   └── AuditService.log('result.saved')
   └── No notification (silent save)

4. Inspector completes inspection (transition: in_progress → completed)
   ├── Validate all required items have answers
   ├── Calculate overall_result (fail if any is_unsafe, else pass)
   ├── WorkflowService.transition('complete')
   ├── Update inspection.overall_result and notes
   ├── WorkflowInstance.completed_at = now()
   ├── ActivityService.log('inspection.completed')
   ├── AuditService.log('completed')
   ├── If overall_result='fail':
   │   └── NotificationService.notifyMany('inspection.unsafe_found')
   └── NotificationService.notifyMany('inspection.completed')

5. QHSSE Manager reviews results on Show page
   ├── Views all item results
   ├── Unsafe items highlighted with red border
   ├── If unsafe items exist:
   │   └── "Buat CAPA" button links to CAPA create form
   └── CAPA created with source_module='inspection'
```

---

## 9. Controller Integration Summary

```php
// Start inspection (pending → in_progress)
$this->workflowService->transition('inspection', $inspection->id, 'start', $actor);
$inspection->update(['executed_at' => now()]);

// Complete inspection (in_progress → completed)
$this->workflowService->transition('inspection', $inspection->id, 'complete', $actor);
$inspection->update(['overall_result' => $hasUnsafe ? 'fail' : 'pass']);
```

Invalid transition throws `RuntimeException` → caught in controller → flash error:

```php
try {
    $this->workflowService->transition('inspection', $inspection->id, $actionKey, $actor);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

---

## 10. Future Enhancements (Beyond Phase 4)

- **Re-inspection**: Add transition `completed → pending` (action_key: `reinspect`) to create a new inspection cycle.
- **Cancel**: Add transition `pending → cancelled` for inspections that are no longer needed.
- **Approval**: Add an `in_review` state between `in_progress` and `completed` where QHSSE Manager must approve results before final completion.
- **Recurring inspections**: Scheduled job that auto-creates inspections based on template frequency (daily, weekly, monthly).
- **Overdue detection**: Scheduled job that checks `scheduled_at < today` and `status IN (pending, in_progress)` → sends `inspection.overdue` notification.
