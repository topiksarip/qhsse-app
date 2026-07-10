# Workflow — Quality Management

> Workflow definitions, transition tables, and controller integration code for NCR and Customer Complaint modules.

---

## 1. NCR Workflow Definition

The `quality` workflow definition needs to be added to `WorkflowSeeder` (or a dedicated `QualityManagementSeeder`).

### Definition Properties

| Property | Value |
|---|---|
| `module_name` | `quality` |
| `code` | `QUALITY_NCR_WORKFLOW` |
| `name` | `Quality NCR Workflow` |
| `initial_status` | `open` |
| `is_active` | `true` |

### Seeder Code

```php
// In QualityManagementSeeder.php (or WorkflowSeeder.php)

$definition = WorkflowDefinition::updateOrCreate(
    ['module_name' => 'quality', 'code' => 'QUALITY_NCR_WORKFLOW'],
    [
        'name' => 'Quality NCR Workflow',
        'initial_status' => 'open',
        'is_active' => true,
    ]
);

// Transitions
$definition->transitions()->updateOrCreate(
    ['from_status' => 'open', 'action_key' => 'submit'],
    [
        'from_status' => 'open',
        'to_status' => 'under_review',
        'action_key' => 'submit',
        'action_label' => 'Submit',
        'requires_reason' => false,
        'required_permission' => 'quality.ncrs.update',
        'is_active' => true,
    ]
);

$definition->transitions()->updateOrCreate(
    ['from_status' => 'under_review', 'action_key' => 'review'],
    [
        'from_status' => 'under_review',
        'to_status' => 'in_progress',
        'action_key' => 'review',
        'action_label' => 'Start Review',
        'requires_reason' => false,
        'required_permission' => 'quality.ncrs.update',
        'is_active' => true,
    ]
);

$definition->transitions()->updateOrCreate(
    ['from_status' => 'in_progress', 'action_key' => 'close'],
    [
        'from_status' => 'in_progress',
        'to_status' => 'closed',
        'action_key' => 'close',
        'action_label' => 'Close NCR',
        'requires_reason' => false,
        'required_permission' => 'quality.ncrs.close',
        'is_active' => true,
    ]
);

$definition->transitions()->updateOrCreate(
    ['from_status' => 'under_review', 'action_key' => 'reject'],
    [
        'from_status' => 'under_review',
        'to_status' => 'rejected',
        'action_key' => 'reject',
        'action_label' => 'Reject',
        'requires_reason' => true,
        'required_permission' => 'quality.ncrs.update',
        'is_active' => true,
    ]
);

$definition->transitions()->updateOrCreate(
    ['from_status' => 'rejected', 'action_key' => 'reopen'],
    [
        'from_status' => 'rejected',
        'to_status' => 'open',
        'action_key' => 'reopen',
        'action_label' => 'Reopen',
        'requires_reason' => true,
        'required_permission' => 'quality.ncrs.update',
        'is_active' => true,
    ]
);
```

### States

| State | Type | Description |
|---|---|---|
| `open` | Initial | NCR dibuat, belum disubmit. Bisa diedit. |
| `under_review` | Active | NCR disubmit, QHSSE team sedang review. |
| `in_progress` | Active | Review selesai, RCA dan corrective/preventive action sedang dikerjakan. |
| `closed` | **Terminal** | NCR selesai. RCA, corrective action, dan preventive action sudah diisi. `closed_at` di-set. |
| `rejected` | **Terminal** | NCR ditolak. Bisa di-reopen dengan alasan. |

### Transition Table (NCR)

| From | To | Action Key | Label | Requires Reason | Required Permission | Business Rule |
|---|---|---|---|---|---|---|
| `open` | `under_review` | `submit` | Submit | ❌ | `quality.ncrs.update` | Title, source, description, site_id, severity_id must be valid |
| `under_review` | `in_progress` | `review` | Start Review | ❌ | `quality.ncrs.update` | — |
| `in_progress` | `closed` | `close` | Close NCR | ❌ | `quality.ncrs.close` | `root_cause`, `corrective_action`, `preventive_action` must be filled |
| `under_review` | `rejected` | `reject` | Reject | ✅ | `quality.ncrs.update` | Reason required |
| `rejected` | `open` | `reopen` | Reopen | ✅ | `quality.ncrs.update` | Reason required |

### Phase 1 Simplified Path

```
open ──(submit)──→ under_review ──(review)──→ in_progress ──(close)──→ closed
                        │
                        └──(reject)──→ rejected ──(reopen)──→ open
```

---

## 2. Customer Complaint Workflow Definition

The `quality_complaint` workflow definition needs to be added to `WorkflowSeeder` (or `QualityManagementSeeder`).

### Definition Properties

| Property | Value |
|---|---|
| `module_name` | `quality_complaint` |
| `code` | `QUALITY_COMPLAINT_WORKFLOW` |
| `name` | `Quality Customer Complaint Workflow` |
| `initial_status` | `open` |
| `is_active` | `true` |

### Seeder Code

```php
$complaintDefinition = WorkflowDefinition::updateOrCreate(
    ['module_name' => 'quality_complaint', 'code' => 'QUALITY_COMPLAINT_WORKFLOW'],
    [
        'name' => 'Quality Customer Complaint Workflow',
        'initial_status' => 'open',
        'is_active' => true,
    ]
);

$complaintDefinition->transitions()->updateOrCreate(
    ['from_status' => 'open', 'action_key' => 'start_review'],
    [
        'from_status' => 'open',
        'to_status' => 'in_progress',
        'action_key' => 'start_review',
        'action_label' => 'Start Review',
        'requires_reason' => false,
        'required_permission' => 'quality.complaints.update',
        'is_active' => true,
    ]
);

$complaintDefinition->transitions()->updateOrCreate(
    ['from_status' => 'in_progress', 'action_key' => 'close'],
    [
        'from_status' => 'in_progress',
        'to_status' => 'closed',
        'action_key' => 'close',
        'action_label' => 'Close Complaint',
        'requires_reason' => false,
        'required_permission' => 'quality.complaints.close',
        'is_active' => true,
    ]
);

// Allow direct close from open (for simple complaints)
$complaintDefinition->transitions()->updateOrCreate(
    ['from_status' => 'open', 'action_key' => 'close'],
    [
        'from_status' => 'open',
        'to_status' => 'closed',
        'action_key' => 'close',
        'action_label' => 'Close Complaint',
        'requires_reason' => false,
        'required_permission' => 'quality.complaints.close',
        'is_active' => true,
    ]
);
```

### States

| State | Type | Description |
|---|---|---|
| `open` | Initial | Keluhan dibuat, belum diproses. |
| `in_progress` | Active | Keluhan sedang diproses / diinvestigasi. |
| `closed` | **Terminal** | Keluhan selesai. `resolution` sudah diisi. `resolved_at` di-set. |

### Transition Table (Customer Complaint)

| From | To | Action Key | Label | Requires Reason | Required Permission | Business Rule |
|---|---|---|---|---|---|---|
| `open` | `in_progress` | `start_review` | Start Review | ❌ | `quality.complaints.update` | — |
| `in_progress` | `closed` | `close` | Close Complaint | ❌ | `quality.complaints.close` | `resolution` must be filled |
| `open` | `closed` | `close` | Close Complaint | ❌ | `quality.complaints.close` | `resolution` must be filled (direct close for simple complaints) |

### Phase 1 Simplified Path

```
open ──(start_review)──→ in_progress ──(close)──→ closed
  │
  └──(close)──→ closed  (direct close for simple complaints with resolution)
```

---

## 3. Permission Seeder

### CorePermissions additions

In `CorePermissions::all()`:

```php
// NCR Permissions
'quality.ncrs.view',
'quality.ncrs.create',
'quality.ncrs.update',
'quality.ncrs.close',
'quality.ncrs.export',

// Customer Complaint Permissions
'quality.complaints.view',
'quality.complaints.create',
'quality.complaints.update',
'quality.complaints.close',
'quality.complaints.export',
```

### CorePermissions::roleMap() additions

```php
'Super Admin' => self::all(),
'Admin' => self::all(),

'QHSSE Manager' => [
    ...$viewOnly,
    'quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update',
    'quality.ncrs.close', 'quality.ncrs.export',
    'quality.complaints.view', 'quality.complaints.create', 'quality.complaints.update',
    'quality.complaints.close', 'quality.complaints.export',
],

'QHSSE Officer' => [
    ...$viewOnly,
    'quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update',
    'quality.ncrs.close', 'quality.ncrs.export',
    'quality.complaints.view', 'quality.complaints.create', 'quality.complaints.update',
    'quality.complaints.close', 'quality.complaints.export',
],

'Supervisor' => [
    ...$viewOnly,
    'quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update',
    'quality.complaints.view', 'quality.complaints.create', 'quality.complaints.update',
],

'Department Head' => [
    ...$viewOnly,
    'quality.ncrs.view',
    'quality.complaints.view',
],

'Employee / Reporter' => [
    'core.scope.own',
    'quality.ncrs.view', 'quality.ncrs.create',
    'quality.complaints.view', 'quality.complaints.create',
],

'Contractor' => [
    'core.scope.company',
    'quality.ncrs.view',
    'quality.complaints.view',
],

'Auditor' => [
    ...$viewOnly,
    'core.scope.all',
    'quality.ncrs.view', 'quality.ncrs.export',
    'quality.complaints.view', 'quality.complaints.export',
],

'Top Management' => [
    ...$viewOnly,
    'core.scope.all',
    'quality.ncrs.view', 'quality.ncrs.export',
    'quality.complaints.view', 'quality.complaints.export',
],
```

---

## 4. Audit Trail

All transitions automatically create:

1. `workflow_histories` record (via `WorkflowService::recordHistory()`)
2. `audit_logs` record with event `workflow.transitioned` (via `AuditService::workflow()`)
3. `activity_logs` record with event `workflow.transitioned` (via `ActivityService::log()`)

### NCR Audit Events

| Event | When | Module Name |
|---|---|---|
| `created` | NCR created | `quality` |
| `updated` | NCR fields updated | `quality` |
| `workflow.transitioned` | Status change (submit, review, close, reject, reopen) | `quality` |
| `rca.updated` | Root cause / corrective / preventive action updated | `quality` |
| `capa.linked` | `capa_action_id` set or changed | `quality` |

### Complaint Audit Events

| Event | When | Module Name |
|---|---|---|
| `created` | Complaint created | `quality_complaint` |
| `updated` | Complaint fields updated | `quality_complaint` |
| `workflow.transitioned` | Status change (start_review, close) | `quality_complaint` |
| `ncr.linked` | `ncr_id` set or changed | `quality_complaint` |

---

## 5. Terminal Status Rules

### NCR
- `closed` is terminal (per `WorkflowService::isTerminalStatus()`)
- `rejected` is terminal, but can be reopened via `reopen` transition
- Terminal status sets `workflow_instances.completed_at = now()`
- No further transitions allowed from terminal status (except `rejected → open` via reopen)
- When `closed`: `ncrs.closed_at` is set to `now()`

### Customer Complaint
- `closed` is terminal
- Terminal status sets `workflow_instances.completed_at = now()`
- When `closed`: `customer_complaints.resolved_at` is set to `now()`
- Reopen is NOT supported in Phase 1 for complaints

---

## 6. Controller Integration

### NCR Controller

```php
<?php

namespace App\Http\Controllers\Modules\Quality;

use App\Core\Services\WorkflowService;
use App\Core\Services\AuditService;
use App\Core\Services\ActivityService;
use App\Core\Services\NotificationService;
use App\Core\Services\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Quality\StoreNcrRequest;
use App\Http\Requests\Modules\Quality\UpdateNcrRequest;
use App\Models\Modules\Quality\Ncr;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class NcrController extends Controller
{
    public function __construct(
        private WorkflowService $workflowService,
        private AuditService $auditService,
        private ActivityService $activityService,
        private NotificationService $notificationService,
        private NumberingService $numberingService,
    ) {}

    public function store(StoreNcrRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        // Create NCR
        $ncr = Ncr::create(array_merge($data, ['status' => 'open']));

        // Generate number
        $generatedNumber = $this->numberingService->generate(
            moduleName: 'quality',
            actor: $actor,
            referenceType: Ncr::class,
            referenceId: $ncr->id,
        );
        $ncr->update(['ncr_number' => $generatedNumber->number]);

        // Start workflow
        $this->workflowService->start('quality', $ncr->id, $actor);

        // Audit + Activity
        $this->auditService->created($ncr, $actor, 'quality', $ncr->id);
        $this->activityService->log('quality', $ncr->id, 'ncr.created', 'NCR dibuat', $actor);

        // If action = submit, transition
        if (($data['action'] ?? null) === 'submit') {
            $this->workflowService->transition('quality', $ncr->id, 'submit', $actor);
            $this->notifyQhsseTeam('quality.ncr.submitted', $ncr);
        }

        return redirect()->route('quality.ncrs.show', $ncr);
    }

    public function update(UpdateNcrRequest $request, Ncr $ncr): RedirectResponse
    {
        // Business rule: only editable if open or under_review
        if (!in_array($ncr->status, ['open', 'under_review'])) {
            return back()->withErrors(['workflow' => 'NCR tidak dapat diedit pada status ini.']);
        }

        $actor = $request->user();
        $oldValues = $ncr->toArray();
        $data = $request->validated();

        // Detect CAPA link change
        if (isset($data['capa_action_id']) && $data['capa_action_id'] !== $ncr->capa_action_id) {
            $this->activityService->log('quality', $ncr->id, 'capa.linked', 'CAPA ditautkan', $actor);
        }

        $ncr->update($data);

        $this->auditService->updated($ncr, $oldValues, $actor, 'quality', $ncr->id);

        return redirect()->route('quality.ncrs.show', $ncr);
    }

    public function submit(Ncr $ncr): RedirectResponse
    {
        $actor = request()->user();

        try {
            $this->workflowService->transition('quality', $ncr->id, 'submit', $actor);
            $this->activityService->log('quality', $ncr->id, 'ncr.submitted', 'NCR disubmit', $actor);
            $this->notifyQhsseTeam('quality.ncr.submitted', $ncr);
        } catch (RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back();
    }

    public function review(Ncr $ncr): RedirectResponse
    {
        $actor = request()->user();

        try {
            $this->workflowService->transition('quality', $ncr->id, 'review', $actor);
            $this->activityService->log('quality', $ncr->id, 'ncr.reviewed', 'NCR direview', $actor);
        } catch (RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back();
    }

    public function close(Ncr $ncr): RedirectResponse
    {
        $actor = request()->user();

        // Business rule: RCA must be filled
        if (empty($ncr->root_cause) || empty($ncr->corrective_action) || empty($ncr->preventive_action)) {
            return back()->withErrors([
                'rca' => 'Root Cause, Corrective Action, dan Preventive Action wajib diisi sebelum menutup NCR.'
            ]);
        }

        try {
            $this->workflowService->transition('quality', $ncr->id, 'close', $actor);
            $ncr->update(['closed_at' => now()]);
            $this->activityService->log('quality', $ncr->id, 'ncr.closed', 'NCR ditutup', $actor);
            $this->notificationService->notify($ncr->reporter, 'quality.ncr.closed', [...], $actor, 'quality', $ncr->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back();
    }

    private function notifyQhsseTeam(string $type, Ncr $ncr): void
    {
        $qhsseUsers = User::whereHas('roles', fn ($q) =>
            $q->whereIn('name', ['QHSSE Officer', 'QHSSE Manager'])
        )->get();

        $this->notificationService->notifyMany($qhsseUsers, $type, [...], request()->user(), 'quality', $ncr->id);
    }
}
```

### Customer Complaint Controller

```php
<?php

namespace App\Http\Controllers\Modules\Quality;

use App\Core\Services\WorkflowService;
use App\Core\Services\AuditService;
use App\Core\Services\ActivityService;
use App\Core\Services\NotificationService;
use App\Core\Services\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Quality\StoreCustomerComplaintRequest;
use App\Http\Requests\Modules\Quality\UpdateCustomerComplaintRequest;
use App\Models\Modules\Quality\CustomerComplaint;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CustomerComplaintController extends Controller
{
    public function __construct(
        private WorkflowService $workflowService,
        private AuditService $auditService,
        private ActivityService $activityService,
        private NotificationService $notificationService,
        private NumberingService $numberingService,
    ) {}

    public function store(StoreCustomerComplaintRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        $complaint = CustomerComplaint::create(array_merge($data, ['status' => 'open']));

        // Generate number
        $generatedNumber = $this->numberingService->generate(
            moduleName: 'quality',
            actor: $actor,
            referenceType: CustomerComplaint::class,
            referenceId: $complaint->id,
        );
        $complaint->update(['complaint_number' => $generatedNumber->number]);

        // Start workflow
        $this->workflowService->start('quality_complaint', $complaint->id, $actor);

        // Audit + Activity
        $this->auditService->created($complaint, $actor, 'quality_complaint', $complaint->id);
        $this->activityService->log('quality_complaint', $complaint->id, 'complaint.created', 'Keluhan dibuat', $actor);

        // Notify QHSSE team
        $this->notifyQhsseTeam('quality.complaint.created', $complaint);

        return redirect()->route('quality.complaints.show', $complaint);
    }

    public function update(UpdateCustomerComplaintRequest $request, CustomerComplaint $complaint): RedirectResponse
    {
        if (!in_array($complaint->status, ['open', 'in_progress'])) {
            return back()->withErrors(['workflow' => 'Keluhan tidak dapat diedit pada status ini.']);
        }

        $actor = $request->user();
        $oldValues = $complaint->toArray();
        $data = $request->validated();

        // Detect NCR link change
        if (isset($data['ncr_id']) && $data['ncr_id'] !== $complaint->ncr_id) {
            $this->activityService->log('quality_complaint', $complaint->id, 'ncr.linked', 'NCR ditautkan', $actor);
        }

        $complaint->update($data);

        $this->auditService->updated($complaint, $oldValues, $actor, 'quality_complaint', $complaint->id);

        return redirect()->route('quality.complaints.show', $complaint);
    }

    public function close(CustomerComplaint $complaint): RedirectResponse
    {
        $actor = request()->user();

        // Business rule: resolution must be filled
        if (empty($complaint->resolution)) {
            return back()->withErrors([
                'resolution' => 'Resolusi wajib diisi sebelum menutup keluhan.'
            ]);
        }

        try {
            $this->workflowService->transition('quality_complaint', $complaint->id, 'close', $actor);
            $complaint->update(['resolved_at' => now()]);
            $this->activityService->log('quality_complaint', $complaint->id, 'complaint.closed', 'Keluhan ditutup', $actor);
            $this->notificationService->notify($complaint->reporter, 'quality.complaint.closed', [...], $actor, 'quality_complaint', $complaint->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back();
    }

    private function notifyQhsseTeam(string $type, CustomerComplaint $complaint): void
    {
        $qhsseUsers = User::whereHas('roles', fn ($q) =>
            $q->whereIn('name', ['QHSSE Officer', 'QHSSE Manager'])
        )->get();

        $this->notificationService->notifyMany($qhsseUsers, $type, [...], request()->user(), 'quality_complaint', $complaint->id);
    }
}
```

---

## 7. Business Rule Validation Summary

### NCR Close Prerequisites

Before `close` transition is allowed:

1. ✅ `status` must be `in_progress`
2. ✅ `root_cause` must not be null or empty
3. ✅ `corrective_action` must not be null or empty
4. ✅ `preventive_action` must not be null or empty
5. ✅ User must have `quality.ncrs.close` permission

### Customer Complaint Close Prerequisites

Before `close` transition is allowed:

1. ✅ `status` must be `in_progress` or `open`
2. ✅ `resolution` must not be null or empty
3. ✅ User must have `quality.complaints.close` permission

### Edit Restrictions

| Resource | Editable Statuses | Non-Editable Statuses |
|---|---|---|
| NCR | `open`, `under_review` | `in_progress` (only RCA fields editable), `closed`, `rejected` |
| Complaint | `open`, `in_progress` | `closed` |

> **Note:** During `in_progress` status, NCR's `root_cause`, `corrective_action`, `preventive_action`, and `capa_action_id` fields remain editable. Other fields (title, source, description, site, severity) are locked.
