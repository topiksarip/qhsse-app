# Workflow — Legal & Compliance Register

## 1. Workflow Overview

Modul Legal & Compliance Register **tidak menggunakan WorkflowService** untuk status transitions. Tidak ada workflow definition yang perlu di-seed. Modul ini menggunakan **simple status field management** langsung pada model.

- `legal_register.status`: `active` / `inactive` (record lifecycle)
- `legal_register.compliance_status`: `compliant` / `non_compliant` / `in_progress` / `not_applicable` (compliance tracking)
- `legal_obligations.status`: `pending` / `completed` (obligation lifecycle)

### Why No Workflow Definition?

- Legal register tidak memiliki state machine yang kompleks (tidak seperti audit yang punya planned→in_progress→report_ready→closed).
- Compliance status dapat diubah kapan saja tanpa urutan tertentu (compliant→non_compliant→in_progress→compliant, dst).
- Obligation lifecycle sederhana: pending→completed (dengan auto-reset untuk recurring).
- Menggunakan WorkflowService akan menambah overhead tanpa value.

---

## 2. Register Status States

| State | Type | Description |
|---|---|---|
| `active` | Default | Register aktif. Dapat diedit, obligation dapat ditambahkan/diselesaikan. |
| `inactive` | Terminal (archived) | Register diarsipkan. Tidak dapat diedit. Obligation tidak dapat ditambahkan/diselesaikan. Read-only. |

### State Diagram

```
┌──────────────┐                    ┌──────────────┐
│              │    archive         │              │
│   active     ├───────────────────►│   inactive   │
│              │                    │              │
│              │◄───────────────────┤   restore    │
└──────────────┘                    └──────────────┘
       │                                  │
       │ Edit allowed                     │ Read-only
       │ Obligation CRUD allowed           │ No edits
       │ Compliance status change allowed  │ No obligation changes
       │                                   │
       └──────────────────────────────────┘
```

---

## 3. Compliance Status States

Compliance status tidak memiliki state machine — setiap status dapat berubah ke status lain kapan saja selama register `active`.

| Status | Code | Color | Description |
|---|---|---|---|
| Compliant | `compliant` | 🟢 green | Organisasi telah mematuhi regulasi sepenuhnya |
| Non-Compliant | `non_compliant` | 🔴 red | Organisasi tidak mematuhi regulasi |
| In Progress | `in_progress` | 🟡 yellow | Sedang dalam proses pemenuhan kepatuhan |
| Not Applicable | `not_applicable` | ⚪ gray | Regulasi tidak berlaku untuk organisasi |

### State Diagram

```
                    ┌─────────────────┐
                    │                 │
          ┌────────►│   in_progress   │◄────────┐
          │         │   (default)     │         │
          │         └────────┬────────┘         │
          │                  │                  │
          │     ┌────────────┼────────────┐     │
          │     │            │            │     │
          │     ▼            ▼            ▼     │
    ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
    │             │  │             │  │             │
    │  compliant  │  │non_compliant│  │not_applicable│
    │             │  │             │  │             │
    └─────────────┘  └─────────────┘  └─────────────┘
          │                  │                  │
          │                  │                  │
          └──────────────────┴──────────────────┘
                    Any → Any (if active)
```

### Compliance Status Change Rules

| From | To | Allowed? | Condition | Trigger |
|---|---|---|---|---|
| `in_progress` | `compliant` | ✅ | register.status = active | Audit trail + notification |
| `in_progress` | `non_compliant` | ✅ | register.status = active | Audit trail + notification (email) |
| `in_progress` | `not_applicable` | ✅ | register.status = active | Audit trail |
| `compliant` | `non_compliant` | ✅ | register.status = active | Audit trail + notification (email) |
| `compliant` | `in_progress` | ✅ | register.status = active | Audit trail |
| `non_compliant` | `in_progress` | ✅ | register.status = active | Audit trail + notification |
| `non_compliant` | `compliant` | ✅ | register.status = active | Audit trail + notification |
| `not_applicable` | `in_progress` | ✅ | register.status = active | Audit trail |
| Any | Any | ❌ | register.status = inactive | Error: "Register tidak aktif" |

---

## 4. Obligation Status States

| State | Type | Description |
|---|---|---|
| `pending` | Initial | Obligation belum dilaksanakan atau perlu dilaksanakan lagi. |
| `completed` | Completed | Obligation telah dilaksanakan. Evidence terupload. Next_due di-recalculate. |

### State Diagram

```
┌──────────────┐     complete (with evidence)      ┌──────────────┐
│              │ ──────────────────────────────────► │              │
│   pending    │                                     │   completed  │
│ (initial)    │                                     │              │
│              │ ◄────────────────────────────────── │              │
└──────────────┘         reset (recurring)           └──────────────┘
       │                                                    │
       │ next_due in past = OVERDUE                        │ next_due recalculated
       │ (badge: 🔴 overdue)                                │ next cycle starts
       │                                                    │
       │ next_due in 7 days = DUE SOON                     │
       │ (badge: 🟠 due_soon)                              │
       │                                                    │
       │ next_due in future = ON TRACK                     │
       │ (badge: 🟡 pending)                                │
       └────────────────────────────────────────────────────┘
```

### Obligation Status Transition Rules

| From | To | Action | Required Permission | Additional Checks |
|---|---|---|---|---|
| `pending` | `completed` | `complete` | `legal.obligations.update` | `evidence_file_id` required, `last_completed` required |
| `completed` | `pending` | `reset` (automatic) | System (scheduled job) | When new cycle begins (next_due approaches) |

### Obligation Visual States (Derived)

| Visual State | Condition | Badge Color |
|---|---|---|
| `overdue` | `status = 'pending'` AND `next_due < today` | 🔴 red |
| `due_soon` | `status = 'pending'` AND `today <= next_due <= today+7` | 🟠 orange |
| `pending` | `status = 'pending'` AND `next_due > today+7` (or null) | 🟡 yellow |
| `completed` | `status = 'completed'` | 🟢 green |

---

## 5. Due Date Calculation Logic

### Auto-Calculation on Create

```php
// When obligation is created with last_completed set:
if ($lastCompleted && !$nextDue) {
    $nextDue = match ($frequency) {
        'monthly' => Carbon::parse($lastCompleted)->addMonth()->toDateString(),
        'quarterly' => Carbon::parse($lastCompleted)->addMonths(3)->toDateString(),
        'annual' => Carbon::parse($lastCompleted)->addYear()->toDateString(),
    };
}
```

### Auto-Recalculation on Complete

```php
// When obligation is completed:
$nextDue = match ($obligation->frequency) {
    'monthly' => Carbon::parse($request->last_completed)->addMonth()->toDateString(),
    'quarterly' => Carbon::parse($request->last_completed)->addMonths(3)->toDateString(),
    'annual' => Carbon::parse($request->last_completed)->addYear()->toDateString(),
};

$obligation->update([
    'status' => 'completed',
    'last_completed' => $request->last_completed,
    'next_due' => $nextDue,
    'evidence_file_id' => $request->evidence_file_id,
]);
```

### Frequency Calculation Table

| Frequency | Add | Example (last_completed = 2026-01-15) |
|---|---|---|
| `monthly` | +1 month | next_due = 2026-02-15 |
| `quarterly` | +3 months | next_due = 2026-04-15 |
| `annual` | +1 year | next_due = 2027-01-15 |

---

## 6. Overdue Detection Logic

### Real-time Detection (Model Level)

```php
// In LegalObligation model:

public function isOverdue(): bool
{
    return $this->status === 'pending'
        && $this->next_due !== null
        && $this->next_due < now()->toDateString();
}

public function isDueSoon(int $days = 7): bool
{
    return $this->status === 'pending'
        && $this->next_due !== null
        && $this->next_due <= now()->addDays($days)->toDateString()
        && $this->next_due >= now()->toDateString();
}

public function getDaysOverdue(): ?int
{
    if (!$this->isOverdue()) {
        return null;
    }
    return now()->diffInDays($this->next_due);
}
```

### Query Scopes

```php
// In LegalObligation model:

public function scopeOverdue(Builder $query): Builder
{
    return $query->where('status', 'pending')
        ->whereNotNull('next_due')
        ->where('next_due', '<', now()->toDateString());
}

public function scopeDueSoon(Builder $query, int $days = 7): Builder
{
    return $query->where('status', 'pending')
        ->whereNotNull('next_due')
        ->where('next_due', '<=', now()->addDays($days)->toDateString())
        ->where('next_due', '>=', now()->toDateString());
}
```

### Scheduled Job (Daily Overdue Check)

File: `app/Console/Commands/CheckOverdueObligations.php`

```php
class CheckOverdueObligations extends Command
{
    protected $signature = 'legal:check-overdue';
    protected $description = 'Check for overdue and due-soon obligations, send notifications';

    public function handle(NotificationService $notificationService): int
    {
        // 1. Check newly overdue obligations
        $newlyOverdue = LegalObligation::with(['legalRegister', 'legalRegister.owner'])
            ->where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<', now()->toDateString())
            ->whereDoesntHave('overdueNotificationSent') // Prevent duplicate notifications
            ->get();

        foreach ($newlyOverdue as $obligation) {
            $recipients = $this->getRecipients($obligation);

            $notificationService->notifyMany(
                recipients: $recipients,
                type: 'legal.obligation.overdue',
                context: [
                    'obligation' => $obligation->toArray(),
                    'register' => $obligation->legalRegister->toArray(),
                    'days_overdue' => now()->diffInDays($obligation->next_due),
                ],
                actor: null,
                moduleName: 'legal',
                referenceId: $obligation->legalRegister->id,
                actionUrl: "/legal-register/{$obligation->legalRegister->id}?tab=obligations",
            );
        }

        // 2. Check due-soon obligations (7 days)
        $dueSoon = LegalObligation::with(['legalRegister', 'legalRegister.owner'])
            ->where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<=', now()->addDays(7)->toDateString())
            ->where('next_due', '>=', now()->toDateString())
            ->get();

        foreach ($dueSoon as $obligation) {
            $notificationService->notifyMany(
                recipients: $this->getRecipients($obligation),
                type: 'legal.obligation.due_soon',
                context: [
                    'obligation' => $obligation->toArray(),
                    'register' => $obligation->legalRegister->toArray(),
                    'days_until_due' => now()->diffInDays($obligation->next_due, false),
                ],
                actor: null,
                moduleName: 'legal',
                referenceId: $obligation->legalRegister->id,
                actionUrl: "/legal-register/{$obligation->legalRegister->id}?tab=obligations",
            );
        }

        $this->info("Processed {$newlyOverdue->count()} overdue and {$dueSoon->count()} due-soon obligations.");
        return self::SUCCESS;
    }

    private function getRecipients(LegalObligation $obligation): array
    {
        $recipients = collect();

        // Register owner
        if ($obligation->legalRegister->owner) {
            $recipients->push($obligation->legalRegister->owner);
        }

        // QHSSE Officers assigned to the site
        if ($obligation->legalRegister->site_id) {
            $officers = User::role('QHSSE Officer')
                ->where('is_active', true)
                ->get();
            $recipients = $recipients->merge($officers);
        }

        // QHSSE Managers
        $managers = User::role('QHSSE Manager')
            ->where('is_active', true)
            ->get();
        $recipients = $recipients->merge($managers);

        return $recipients->unique('id')->all();
    }
}
```

### Schedule Registration

File: `app/Console/Kernel.php` or `routes/console.php`

```php
// Daily at 00:01
Schedule::command('legal:check-overdue')->dailyAt('00:01');
```

---

## 7. Audit Trail

All critical changes are logged via `AuditService` and `ActivityService`.

### Register Operations Audit Trail

| Operation | Event | Auditable | Log Level |
|---|---|---|---|
| Create register | `legal.register.created` | LegalRegister | Audit + Activity |
| Update register | `legal.register.updated` | LegalRegister | Audit + Activity |
| Compliance status change | `legal.compliance.changed` | LegalRegister | Audit + Activity + Notification |
| Archive register | `legal.register.archived` | LegalRegister | Audit + Activity |
| Restore register | `legal.register.restored` | LegalRegister | Audit + Activity |
| Delete register | `legal.register.deleted` | LegalRegister | Audit + Activity |
| Export registers | `legal.exported` | LegalRegister | Audit |

### Obligation Operations Audit Trail

| Operation | Event | Auditable | Log Level |
|---|---|---|---|
| Create obligation | `legal.obligation.created` | LegalObligation | Audit + Activity |
| Update obligation | `legal.obligation.updated` | LegalObligation | Audit + Activity |
| Complete obligation | `legal.obligation.completed` | LegalObligation | Audit + Activity |

### Audit Trail Record Example

```php
// Register creation
AuditService::created($register, $actor, 'legal', $register->id);

// Register update
AuditService::updated($register, $oldValues, $actor, 'legal', $register->id);

// Compliance status change
AuditService::log(
    event: 'legal.compliance.changed',
    model: $register,
    oldValues: ['compliance_status' => $oldStatus],
    newValues: ['compliance_status' => $register->compliance_status],
    actor: $actor,
    moduleName: 'legal',
    referenceId: $register->id,
);

// Obligation creation
AuditService::created($obligation, $actor, 'legal', $obligation->id);

// Obligation completion
AuditService::updated($obligation, $oldValues, $actor, 'legal', $obligation->id);
ActivityService::log(
    moduleName: 'legal',
    referenceId: $register->id,
    event: 'legal.obligation.completed',
    description: "Obligation completed by {$actor->name}",
    actor: $actor,
);
```

---

## 8. Controller Integration

### Store Register

```php
public function store(StoreLegalRegisterRequest $request): RedirectResponse
{
    $this->authorize('create', LegalRegister::class);

    $data = $request->validated();
    $data['status'] = 'active';
    $data['compliance_status'] = $data['compliance_status'] ?? 'in_progress';

    $register = LegalRegister::create($data);

    // Generate register number
    $generatedNumber = app(NumberingService::class)->generate(
        moduleName: 'legal',
        actor: $request->user(),
        referenceType: LegalRegister::class,
        referenceId: $register->id,
    );
    $register->update(['register_number' => $generatedNumber->number]);

    // Audit trail
    app(AuditService::class)->created($register, $request->user(), 'legal', $register->id);
    app(ActivityService::class)->log(
        moduleName: 'legal',
        referenceId: $register->id,
        event: 'legal.register.created',
        description: "Register {$register->register_number} created by {$request->user()->name}",
        actor: $request->user(),
    );

    // Notification
    app(NotificationService::class)->notifyMany(
        recipients: $this->getQhsseManagers(),
        type: 'legal.register.created',
        context: [
            'register' => $register->fresh()->toArray(),
            'actor' => $request->user()->toArray(),
        ],
        actor: $request->user(),
        moduleName: 'legal',
        referenceId: $register->id,
        actionUrl: "/legal-register/{$register->id}",
    );

    return redirect()->route('legal-register.show', $register)
        ->with('success', 'Register berhasil dibuat.');
}
```

### Update Register

```php
public function update(UpdateLegalRegisterRequest $request, LegalRegister $register): RedirectResponse
{
    $this->authorize('update', $register);

    if ($register->status !== 'active') {
        return back()->withErrors(['register' => 'Register tidak aktif. Tidak dapat diubah.']);
    }

    $oldValues = $register->toArray();
    $oldComplianceStatus = $register->compliance_status;

    $register->update($request->validated());

    // Audit trail
    app(AuditService::class)->updated($register, $oldValues, $request->user(), 'legal', $register->id);
    app(ActivityService::class)->log(
        moduleName: 'legal',
        referenceId: $register->id,
        event: 'legal.register.updated',
        description: "Register {$register->register_number} updated by {$request->user()->name}",
        actor: $request->user(),
    );

    // Compliance status change detection
    if ($oldComplianceStatus !== $register->compliance_status) {
        app(AuditService::class)->log(
            event: 'legal.compliance.changed',
            model: $register,
            oldValues: ['compliance_status' => $oldComplianceStatus],
            newValues: ['compliance_status' => $register->compliance_status],
            actor: $request->user(),
            moduleName: 'legal',
            referenceId: $register->id,
        );

        app(ActivityService::class)->log(
            moduleName: 'legal',
            referenceId: $register->id,
            event: 'legal.compliance.changed',
            description: "Compliance status changed from {$oldComplianceStatus} to {$register->compliance_status} by {$request->user()->name}",
            actor: $request->user(),
        );

        // Notification
        app(NotificationService::class)->notifyMany(
            recipients: $this->getComplianceChangeRecipients($register),
            type: 'legal.compliance.changed',
            context: [
                'register' => $register->fresh()->toArray(),
                'actor' => $request->user()->toArray(),
                'old_status' => $oldComplianceStatus,
                'new_status' => $register->compliance_status,
            ],
            actor: $request->user(),
            moduleName: 'legal',
            referenceId: $register->id,
            actionUrl: "/legal-register/{$register->id}",
        );
    }

    return redirect()->route('legal-register.show', $register)
        ->with('success', 'Register berhasil diperbarui.');
}
```

### Store Obligation

```php
public function store(StoreLegalObligationRequest $request, LegalRegister $register): RedirectResponse
{
    $this->authorize('create', [LegalObligation::class, $register]);

    if ($register->status !== 'active') {
        return back()->withErrors(['register' => 'Register tidak aktif. Tidak dapat menambah kewajiban.']);
    }

    $data = $request->validated();
    $data['legal_register_id'] = $register->id;
    $data['status'] = 'pending';

    // Auto-calculate next_due if last_completed is set
    if (!empty($data['last_completed']) && empty($data['next_due'])) {
        $data['next_due'] = $this->calculateNextDue($data['frequency'], $data['last_completed']);
    }

    $obligation = LegalObligation::create($data);

    // Audit trail
    app(AuditService::class)->created($obligation, $request->user(), 'legal', $obligation->id);
    app(ActivityService::class)->log(
        moduleName: 'legal',
        referenceId: $register->id,
        event: 'legal.obligation.created',
        description: "Obligation created for register {$register->register_number} by {$request->user()->name}",
        actor: $request->user(),
    );

    return back()->with('success', 'Kewajiban berhasil ditambahkan.');
}

private function calculateNextDue(string $frequency, string $lastCompleted): string
{
    $date = Carbon::parse($lastCompleted);

    return match ($frequency) {
        'monthly' => $date->addMonth()->toDateString(),
        'quarterly' => $date->addMonths(3)->toDateString(),
        'annual' => $date->addYear()->toDateString(),
        default => $date->addMonth()->toDateString(),
    };
}
```

### Complete Obligation

```php
public function complete(CompleteObligationRequest $request, LegalRegister $register, LegalObligation $obligation): RedirectResponse
{
    $this->authorize('update', $obligation);

    if ($register->status !== 'active') {
        return back()->withErrors(['register' => 'Register tidak aktif.']);
    }

    if ($obligation->legal_register_id !== $register->id) {
        abort(404);
    }

    if ($obligation->status !== 'pending') {
        return back()->withErrors(['obligation' => 'Kewajiban sudah diselesaikan.']);
    }

    $oldValues = $obligation->toArray();

    // Calculate new next_due
    $nextDue = $this->calculateNextDue($obligation->frequency, $request->input('last_completed'));

    $obligation->update([
        'status' => 'completed',
        'last_completed' => $request->input('last_completed'),
        'next_due' => $nextDue,
        'evidence_file_id' => $request->input('evidence_file_id'),
    ]);

    // Audit trail
    app(AuditService::class)->updated($obligation, $oldValues, $request->user(), 'legal', $obligation->id);
    app(ActivityService::class)->log(
        moduleName: 'legal',
        referenceId: $register->id,
        event: 'legal.obligation.completed',
        description: "Obligation completed by {$request->user()->name}",
        actor: $request->user(),
    );

    return back()->with('success', 'Kewajiban berhasil diselesaikan.');
}
```

---

## 9. Permission Registration

### Permissions to Register in `CorePermissions::all()`

```php
// Legal Register
'legal.register.view',
'legal.register.create',
'legal.register.update',
'legal.register.export',

// Legal Obligations
'legal.obligations.view',
'legal.obligations.create',
'legal.obligations.update',
```

### Role-Permission Mapping in `CorePermissions::roleMap()`

```php
'Super Admin' => self::all(),
'Admin' => self::all(),

'QHSSE Manager' => [
    ...$viewOnly,
    'legal.register.view',
    'legal.register.create',
    'legal.register.update',
    'legal.register.export',
    'legal.obligations.view',
    'legal.obligations.create',
    'legal.obligations.update',
],

'QHSSE Officer' => [
    ...$viewOnly,
    'legal.register.view',
    'legal.register.create',
    'legal.register.update',
    'legal.register.export',
    'legal.obligations.view',
    'legal.obligations.create',
    'legal.obligations.update',
],

'Supervisor' => [
    ...$viewOnly,
    'legal.register.view',
    'legal.register.export',
    'legal.obligations.view',
],

'Department Head' => [
    ...$viewOnly,
    'legal.register.view',
    'legal.register.export',
    'legal.obligations.view',
],

'Employee / Reporter' => [
    'core.scope.own',
    'legal.register.view',
],

'Contractor' => [
    'core.scope.company',
    'legal.register.view',
],

'Auditor' => [
    ...$viewOnly,
    'core.scope.all',
    'legal.register.view',
    'legal.register.export',
    'legal.obligations.view',
],

'Top Management' => [
    ...$viewOnly,
    'core.scope.all',
    'legal.register.view',
    'legal.register.export',
    'legal.obligations.view',
],
```

---

## 10. Terminal Status Rules

### Register (`legal_register.status`)

- `inactive` is the only terminal status.
- Inactive registers are read-only — no edits, no obligation changes.
- Inactive registers can be restored to `active` (if needed).
- Inactive registers do not appear in default list view (filtered out).
- Inactive registers can still be searched and viewed (read-only).
- Obligations on inactive registers are frozen (no completion, no updates).

### Obligation (`legal_obligations.status`)

- `completed` is NOT terminal — obligations can be reset to `pending` when a new cycle begins.
- Completed obligations can be re-completed (new evidence, new last_completed date).
- Obligations cannot be deleted — they are part of the register record permanently.
- If register is set to `inactive`, obligations are frozen regardless of their status.
