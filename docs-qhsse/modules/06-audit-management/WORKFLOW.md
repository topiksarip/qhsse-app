# Workflow — Audit Management

## 1. Workflow Definition (To Be Seeded)

The `audit` workflow is **not yet seeded** in Phase 0. It must be added via `AuditManagementSeeder` in Phase 6.

| Property | Value |
|---|---|
| `module_name` | `audit` |
| `code` | `AUDIT_WORKFLOW` |
| `name` | `Audit Workflow` |
| `initial_status` | `planned` |
| `is_active` | `true` |

### Seeder Code

```php
// database/seeders/AuditManagementSeeder.php

$definition = WorkflowDefinition::create([
    'module_name' => 'audit',
    'code' => 'AUDIT_WORKFLOW',
    'name' => 'Audit Workflow',
    'initial_status' => 'planned',
    'is_active' => true,
]);

$transitions = [
    [
        'from_status' => 'planned',
        'to_status' => 'in_progress',
        'action_key' => 'start',
        'action_label' => 'Mulai Audit',
        'requires_reason' => false,
        'required_permission' => 'audit.management.execute',
        'is_active' => true,
    ],
    [
        'from_status' => 'in_progress',
        'to_status' => 'report_ready',
        'action_key' => 'generate_report',
        'action_label' => 'Generate Report',
        'requires_reason' => false,
        'required_permission' => 'audit.management.execute',
        'is_active' => true,
    ],
    [
        'from_status' => 'report_ready',
        'to_status' => 'closed',
        'action_key' => 'close',
        'action_label' => 'Close Audit',
        'requires_reason' => false,
        'required_permission' => 'audit.management.close',
        'is_active' => true,
    ],
];

foreach ($transitions as $transition) {
    $definition->transitions()->create($transition);
}
```

---

## 2. States

| State | Type | Description |
|---|---|---|
| `planned` | Initial | Audit dibuat, belum dimulai. Bisa diedit. Findings belum bisa ditambahkan. |
| `in_progress` | Active | Audit sedang berjalan. Findings dapat ditambahkan/diedit. Tidak bisa edit audit record (kecuali via generate report). |
| `report_ready` | Active | Audit report telah di-generate. Summary terisi. Findings masih dapat ditambahkan/diedit/ditutup. Siap untuk close. |
| `closed` | **Terminal** | Audit selesai. Tidak bisa diedit. Findings tidak dapat ditambahkan/diedit. Read-only. |

---

## 3. Transition Table

| From | To | Action Key | Label | Requires Reason | Requires Summary | Required Permission | Additional Checks |
|---|---|---|---|---|---|---|---|
| `planned` | `in_progress` | `start` | Mulai Audit | ❌ | ❌ | `audit.management.execute` | — |
| `in_progress` | `report_ready` | `generate_report` | Generate Report | ❌ | ✅ (min:20 chars) | `audit.management.execute` | `summary` field required |
| `report_ready` | `closed` | `close` | Close Audit | ❌ | ❌ | `audit.management.close` | All findings must be `closed`; all Major findings must have `capa_action_id` |

---

## 4. State Diagram

```
┌──────────────┐         ┌───────────────┐         ┌───────────────┐         ┌─────────┐
│              │  start  │               │ generate│               │  close  │         │
│   planned    ├────────►│  in_progress  ├────────►│  report_ready ├────────►│ closed  │
│              │         │               │ _report │               │         │         │
└──────────────┘         └───────────────┘         └───────────────┘         └─────────┘
       │                        │                         │                       │
       │ Edit allowed           │ Findings can be        │ Findings can be       │ Terminal
       │ (full record)          │ added/edited           │ added/edited/closed   │ No edits
       │                        │ Audit record locked    │ Audit record locked   │
       │                        │ (except via generate   │                       │
       │                        │  report → summary)     │                       │
       └────────────────────────┴─────────────────────────┴───────────────────────┘
```

---

## 5. Business Rules per Transition

### 5.1 Start (`planned` → `in_progress`)

**Preconditions:**
- Audit status must be `planned`
- User must have `audit.management.execute` permission

**Actions:**
1. `WorkflowService::transition('audit', $audit->id, 'start', $actor)`
2. `ActivityService::log('audit', $audit->id, 'audit.started', 'Audit started by {actor}', $actor)`
3. `NotificationService::notifyMany($auditeeUsers, 'audit.started', [...])`
   - Recipients: Department Head + Supervisor of audited department, lead auditor

**Postconditions:**
- `audits.status = 'in_progress'`
- Audit record is now locked (cannot edit title, type, scope, dates, etc.)
- Findings can now be created

### 5.2 Generate Report (`in_progress` → `report_ready`)

**Preconditions:**
- Audit status must be `in_progress`
- User must have `audit.management.execute` permission
- `summary` field must be provided (min:20 characters)

**Actions:**
1. Validate `summary` field
2. Update `audits.summary` with provided summary
3. `WorkflowService::transition('audit', $audit->id, 'generate_report', $actor)`
4. `ActivityService::log('audit', $audit->id, 'audit.report_generated', 'Audit report generated by {actor}', $actor)`
5. `NotificationService::notifyMany($qhsseManagers, 'audit.report_ready', [...])`
   - Recipients: QHSSE Manager(s), lead auditor, department head

**Postconditions:**
- `audits.status = 'report_ready'`
- `audits.summary` is filled
- Findings can still be added/edited/closed
- Audit is ready for closure

### 5.3 Close (`report_ready` → `closed`)

**Preconditions:**
- Audit status must be `report_ready`
- User must have `audit.management.close` permission
- **All findings must have `status = 'closed'`**
- **All Major findings must have `capa_action_id` NOT NULL** (linked to CAPA)

**Validation:**
```php
$openFindings = $audit->findings()->where('status', 'open')->count();
if ($openFindings > 0) {
    throw new RuntimeException(
        "Tidak dapat menutup audit. Masih ada {$openFindings} temuan yang berstatus Open."
    );
}

$majorWithoutCapa = $audit->findings()
    ->where('classification', 'major')
    ->whereNull('capa_action_id')
    ->count();

if ($majorWithoutCapa > 0) {
    throw new RuntimeException(
        "Tidak dapat menutup audit. Masih ada {$majorWithoutCapa} temuan Major yang belum terhubung ke CAPA."
    );
}
```

**Actions:**
1. Validate all findings closed + Major findings have CAPA
2. `WorkflowService::transition('audit', $audit->id, 'close', $actor)`
3. `ActivityService::log('audit', $audit->id, 'audit.closed', 'Audit closed by {actor}', $actor)`
4. `NotificationService::notifyMany($stakeholders, 'audit.closed', [...])`
   - Recipients: Lead auditor, department head, QHSSE Manager, all users linked to findings with CAPA

**Postconditions:**
- `audits.status = 'closed'`
- Audit record is fully locked (read-only)
- Findings cannot be added/edited/closed
- Workflow instance `completed_at` is set

---

## 6. Finding Lifecycle (Sub-Workflow)

Findings have their own simple lifecycle, independent of the audit workflow but constrained by audit status.

### Finding States

| State | Type | Description |
|---|---|---|
| `open` | Initial | Finding baru dibuat. Belum ditindaklanjuti. |
| `closed` | **Terminal** | Finding telah ditindaklanjuti. Jika Major, wajib terhubung ke CAPA. |

### Finding Transitions

| From | To | Action | Required Permission | Additional Checks | Allowed When Audit Status |
|---|---|---|---|---|---|
| `open` | `closed` | Close Finding | `audit.findings.close` | If classification=major, `capa_action_id` must not be null | `in_progress`, `report_ready` |

### Finding CRUD Rules

| Operation | Required Permission | Allowed When Audit Status |
|---|---|---|
| Create Finding | `audit.findings.create` | `in_progress`, `report_ready` |
| Update Finding | `audit.findings.update` | `in_progress`, `report_ready` (finding must be `open`) |
| Close Finding | `audit.findings.close` | `in_progress`, `report_ready` (finding must be `open`) |
| Link/Unlink CAPA | `audit.findings.update` | `in_progress`, `report_ready` (finding must be `open`) |

### Finding State Diagram

```
┌──────────┐   close    ┌──────────┐
│          ├────────────►│          │
│   open   │             │  closed  │
│          │◄────────────│          │
└──────────┘             └──────────┘
     │                        │
     │ Can edit               │ Terminal
     │ Can link/unlink CAPA   │ Read-only
     │                        │
     └────────────────────────┘
     Allowed only when audit
     status is in_progress
     or report_ready
```

---

## 7. Audit Trail

All transitions automatically create:

1. **`workflow_histories`** record (via `WorkflowService::recordHistory()`)
   - `from_status`, `to_status`, `action_key`, `action_label`, `actor_id`, `metadata`

2. **`audit_logs`** record (via `AuditService`)
   - Event: `audit.started`, `audit.report_generated`, `audit.closed`
   - Records old/new status values

3. **`activity_logs`** record (via `ActivityService`)
   - Event: `audit.started`, `audit.report_generated`, `audit.closed`
   - Human-readable description

### Finding Operations Audit Trail

| Operation | Event | Auditable |
|---|---|---|
| Create finding | `audit.finding.created` | AuditFinding |
| Update finding | `audit.finding.updated` | AuditFinding |
| Close finding | `audit.finding.closed` | AuditFinding |
| Link CAPA | `audit.finding.capa_linked` | AuditFinding |
| Unlink CAPA | `audit.finding.capa_unlinked` | AuditFinding |

---

## 8. Terminal Status Rules

- `closed` is the only terminal status for audits.
- Terminal status sets `workflow_instances.completed_at = now()`.
- No further transitions allowed from terminal status.
- Reopen is **NOT** supported in Phase 6.
- Findings cannot be modified after audit is closed.
- Evidence files cannot be uploaded or deleted after audit is closed.

---

## 9. Controller Integration

### Start Audit

```php
public function start(Audit $audit, Request $request): RedirectResponse
{
    $this->authorize('execute', $audit);

    try {
        $this->workflowService->transition('audit', $audit->id, 'start', $request->user());

        $this->activityService->log(
            moduleName: 'audit',
            referenceId: $audit->id,
            event: 'audit.started',
            description: "Audit {$audit->audit_number} started by {$request->user()->name}",
            actor: $request->user(),
        );

        $this->notificationService->notifyMany(
            recipients: $this->getAuditeeUsers($audit),
            type: 'audit.started',
            context: [
                'audit' => $audit->toArray(),
                'actor' => $request->user()->toArray(),
                'lead_auditor' => $audit->leadAuditor->toArray(),
            ],
            actor: $request->user(),
            moduleName: 'audit',
            referenceId: $audit->id,
            actionUrl: "/audits/{$audit->id}",
        );

    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Audit berhasil dimulai.');
}
```

### Generate Report

```php
public function generateReport(Audit $audit, GenerateAuditReportRequest $request): RedirectResponse
{
    $this->authorize('execute', $audit);

    try {
        $audit->update(['summary' => $request->input('summary')]);

        $this->workflowService->transition('audit', $audit->id, 'generate_report', $request->user());

        $findingsCount = $audit->findings()->count();
        $majorCount = $audit->findings()->where('classification', 'major')->count();

        $this->activityService->log(
            moduleName: 'audit',
            referenceId: $audit->id,
            event: 'audit.report_generated',
            description: "Audit report generated by {$request->user()->name}",
            actor: $request->user(),
        );

        $this->notificationService->notifyMany(
            recipients: $this->getQhsseManagers(),
            type: 'audit.report_ready',
            context: [
                'audit' => $audit->fresh()->toArray(),
                'actor' => $request->user()->toArray(),
                'findings_count' => $findingsCount,
                'major_count' => $majorCount,
            ],
            actor: $request->user(),
            moduleName: 'audit',
            referenceId: $audit->id,
            actionUrl: "/audits/{$audit->id}",
        );

    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Laporan audit berhasil dibuat.');
}
```

### Close Audit

```php
public function close(Audit $audit, Request $request): RedirectResponse
{
    $this->authorize('close', $audit);

    // Check all findings are closed
    $openFindings = $audit->findings()->where('status', 'open')->count();
    if ($openFindings > 0) {
        return back()->withErrors([
            'close' => "Tidak dapat menutup audit. Masih ada {$openFindings} temuan yang berstatus Open."
        ]);
    }

    // Check all Major findings have CAPA linked
    $majorWithoutCapa = $audit->findings()
        ->where('classification', 'major')
        ->whereNull('capa_action_id')
        ->count();

    if ($majorWithoutCapa > 0) {
        return back()->withErrors([
            'close' => "Tidak dapat menutup audit. Masih ada {$majorWithoutCapa} temuan Major yang belum terhubung ke CAPA."
        ]);
    }

    try {
        $this->workflowService->transition('audit', $audit->id, 'close', $request->user());

        $this->activityService->log(
            moduleName: 'audit',
            referenceId: $audit->id,
            event: 'audit.closed',
            description: "Audit {$audit->audit_number} closed by {$request->user()->name}",
            actor: $request->user(),
        );

        $this->notificationService->notifyMany(
            recipients: $this->getAuditStakeholders($audit),
            type: 'audit.closed',
            context: [
                'audit' => $audit->fresh()->toArray(),
                'actor' => $request->user()->toArray(),
            ],
            actor: $request->user(),
            moduleName: 'audit',
            referenceId: $audit->id,
            actionUrl: "/audits/{$audit->id}",
        );

    } catch (RuntimeException $e) {
        return back()->withErrors(['workflow' => $e->getMessage()]);
    }

    return back()->with('success', 'Audit berhasil ditutup.');
}
```

### Close Finding

```php
public function close(Audit $audit, AuditFinding $finding, Request $request): RedirectResponse
{
    $this->authorize('close', $finding);

    if ($finding->audit_id !== $audit->id) {
        abort(404);
    }

    if ($finding->status !== 'open') {
        return back()->withErrors(['finding' => 'Temuan sudah ditutup.']);
    }

    // Major findings require CAPA link
    if ($finding->classification === 'major' && $finding->capa_action_id === null) {
        return back()->withErrors([
            'finding' => 'Temuan Major wajib terhubung ke CAPA sebelum dapat ditutup.'
        ]);
    }

    $oldValues = $finding->toArray();
    $finding->update(['status' => 'closed']);

    $this->auditService->updated($finding, $oldValues, $request->user(), 'audit', $finding->id);

    $this->activityService->log(
        moduleName: 'audit',
        referenceId: $audit->id,
        event: 'audit.finding.closed',
        description: "Finding {$finding->finding_number} closed by {$request->user()->name}",
        actor: $request->user(),
    );

    return back()->with('success', "Temuan {$finding->finding_number} berhasil ditutup.");
}
```

---

## 10. Permission Registration

### Permissions to Register in `CorePermissions::all()`

```php
// Audit Management
'audit.management.view',
'audit.management.create',
'audit.management.update',
'audit.management.execute',
'audit.management.close',
'audit.management.export',

// Audit Findings
'audit.findings.view',
'audit.findings.create',
'audit.findings.update',
'audit.findings.close',
```

### Role-Permission Mapping in `CorePermissions::roleMap()`

```php
'Super Admin' => self::all(),
'Admin' => self::all(),

'QHSSE Manager' => [
    ...$viewOnly,
    'audit.management.view',
    'audit.management.create',
    'audit.management.update',
    'audit.management.execute',
    'audit.management.close',
    'audit.management.export',
    'audit.findings.view',
    'audit.findings.create',
    'audit.findings.update',
    'audit.findings.close',
],

'QHSSE Officer' => [
    ...$viewOnly,
    'audit.management.view',
    'audit.management.create',
    'audit.management.update',
    'audit.management.execute',
    'audit.management.close',
    'audit.management.export',
    'audit.findings.view',
    'audit.findings.create',
    'audit.findings.update',
    'audit.findings.close',
],

'Supervisor' => [
    ...$viewOnly,
    'audit.management.view',
    'audit.management.export',
    'audit.findings.view',
],

'Department Head' => [
    ...$viewOnly,
    'audit.management.view',
    'audit.management.export',
    'audit.findings.view',
],

'Employee / Reporter' => [
    'core.scope.own',
    'audit.management.view',
],

'Contractor' => [
    'core.scope.company',
    'audit.management.view',
],

'Auditor' => [
    ...$viewOnly,
    'core.scope.all',
    'audit.management.view',
    'audit.management.export',
    'audit.findings.view',
],

'Top Management' => [
    ...$viewOnly,
    'core.scope.all',
    'audit.management.view',
    'audit.management.export',
    'audit.findings.view',
],
```
