# Workflow — Training & Competency

> **Important:** Modul Training & Competency TIDAK menggunakan WorkflowService (workflow engine).
> Status record dikelola melalui **simple status field** dengan transisi manual.

## 1. Simple Status (No Workflow Engine)

Berbeda dengan modul Incident Reporting yang menggunakan `WorkflowService` untuk transisi status,
modul Training & Competency menggunakan pendekatan **simple status**:

- Status disimpan sebagai field `status` (varchar) di tabel `training_records`.
- Transisi status dilakukan manual melalui update endpoint (`PUT /training-records/{record}`).
- **Tidak ada `workflow_instances`** atau `workflow_histories` untuk modul ini.
- Setiap perubahan status tetap dicatat di `activity_logs` dan `audit_logs` untuk audit trail.

### Alasan Tidak Menggunakan Workflow Engine

1. Training records memiliki lifecycle yang sederhana (5 status, transisi linear).
2. Tidak ada approval/review gate yang memerlukan workflow definition.
3. Status changes bersifat operasional (admin officer mengubah status saat training berjalan/selesai).
4. Expiry auto-detection lebih cocok sebagai scheduled command, bukan workflow transition.

---

## 2. Status States

| State | Type | Description |
|---|---|---|
| `scheduled` | Initial | Training dijadwalkan, belum dimulai. Status default saat record dibuat. |
| `in_progress` | Active | Training sedang berlangsung (antara start_date dan end_date). |
| `completed` | Active | Training selesai. Sertifikat dapat diunggah. expiry_date aktif jika ada. |
| `expired` | Auto | Sertifikat kedaluwarsa. Auto-set ketika `expiry_date < now()` dan status = `completed`. |
| `cancelled` | Terminal | Training dibatalkan. Tidak dapat diubah ke status lain. |

---

## 3. Transition Table (Manual)

Transisi dilakukan melalui update endpoint. Controller memvalidasi transisi yang diizinkan:

| From | To | Trigger | Requires Permission | Notes |
|---|---|---|---|---|
| `scheduled` | `in_progress` | Manual (officer update) | `training.records.update` | Training dimulai |
| `scheduled` | `cancelled` | Manual (officer update) | `training.records.update` | Training dibatalkan sebelum dimulai |
| `in_progress` | `completed` | Manual (officer update) | `training.records.update` | Training selesai. Skor, hasil, sertifikat dapat diisi. |
| `in_progress` | `cancelled` | Manual (officer update) | `training.records.update` | Training dibatalkan saat berlangsung |
| `completed` | `expired` | **Auto** (scheduled command / on-access) | — | `expiry_date < now()` |
| `completed` | `in_progress` | Manual (officer update) | `training.records.update` | Reopen untuk koreksi (jarang) |
| `expired` | `scheduled` | Manual (officer update) | `training.records.update` | Re-schedule ulang training |
| `expired` | `completed` | Manual (officer update) | `training.records.update` | Jika expiry date di-update ke masa depan |

### Transisi yang TIDAK Diizinkan

| From | To | Reason |
|---|---|---|
| `cancelled` | (any) | Cancelled adalah terminal. Buat record baru jika perlu. |
| `scheduled` | `completed` | Tidak boleh skip `in_progress` (kecuali admin override) |
| `scheduled` | `expired` | Tidak logis |

---

## 4. Transition Validation (Controller)

```php
// In TrainingRecordController::update()
private const ALLOWED_TRANSITIONS = [
    'scheduled'   => ['in_progress', 'cancelled'],
    'in_progress' => ['completed', 'cancelled'],
    'completed'   => ['expired', 'in_progress'],
    'expired'     => ['scheduled', 'completed'],
    'cancelled'   => [], // terminal — no transitions
];

// Validate transition
$oldStatus = $record->status;
$newStatus = $request->status;

if ($oldStatus !== $newStatus) {
    $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];

    if (!in_array($newStatus, $allowed)) {
        return back()->withErrors([
            'status' => "Transisi status dari '{$oldStatus}' ke '{$newStatus}' tidak diizinkan.",
        ]);
    }

    // Log status change
    ActivityService::log(
        'training',
        $record->id,
        'record.status_changed',
        "Status berubah: {$oldStatus} → {$newStatus}",
        $actor,
        ['old_status' => $oldStatus, 'new_status' => $newStatus],
    );
}

$record->update($request->validated());

// Auto-expiry check after update
if ($record->status === 'completed'
    && $record->expiry_date
    && $record->expiry_date < now()->toDateString()
) {
    $record->update(['status' => 'expired']);
    ActivityService::log(
        'training',
        $record->id,
        'record.expired',
        "Sertifikat kedaluwarsa secara otomatis",
        null,
    );
}
```

---

## 5. Expiry Auto-Detection

### 5.1 Scheduled Command

File: `app/Console/Commands/CheckTrainingExpiry.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Modules\Training\TrainingRecord;
use App\Core\Services\ActivityService;
use Illuminate\Console\Command;

class CheckTrainingExpiry extends Command
{
    protected $signature = 'training:check-expiry';
    protected $description = 'Check and update expired training records';

    public function handle(ActivityService $activityService): int
    {
        $expiredRecords = TrainingRecord::where('status', 'completed')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($expiredRecords as $record) {
            $record->update(['status' => 'expired']);

            $activityService->log(
                'training',
                $record->id,
                'record.expired',
                "Sertifikat kedaluwarsa pada {$record->expiry_date}",
                null,
                ['old_status' => 'completed', 'new_status' => 'expired'],
            );

            $count++;
        }

        $this->info("Updated {$count} expired training records.");
        return self::SUCCESS;
    }
}
```

### 5.2 Schedule Registration

File: `routes/console.php` or `app/Console/Kernel.php`

```php
use App\Console\Commands\CheckTrainingExpiry;

Schedule::command(CheckTrainingExpiry::class)->dailyAt('00:01');
```

### 5.3 On-Access Expiry Check

In `TrainingRecordController::show()`:

```php
public function show(TrainingRecord $record): Response
{
    // On-access expiry check
    if ($record->expiry_date
        && $record->expiry_date < now()->toDateString()
        && $record->status === 'completed'
    ) {
        $record->update(['status' => 'expired']);
        $record->refresh();

        app(ActivityService::class)->log(
            'training',
            $record->id,
            'record.expired',
            "Sertifikat kedaluwarsa (deteksi on-access)",
            auth()->user(),
        );
    }

    return Inertia::render('Modules/Training/Record/Show', [
        'record' => $record->load(['employee.department', 'employee.site', 'program', 'certificateFile']),
        'activities' => $record->activities()->latest()->limit(20)->get(),
        'isExpired' => $record->status === 'expired',
        'daysUntilExpiry' => $record->expiry_date
            ? now()->diffInDays($record->expiry_date, false)
            : null,
        'can' => [
            'update' => auth()->user()->can('update', $record),
        ],
    ]);
}
```

### 5.4 Expiry Date Auto-Calculation

When `end_date` is set/updated and the program has `validity_months`:

```php
// In TrainingRecordController::update() or model boot:
if ($record->end_date && $record->program->validity_months && !$request->has('expiry_date')) {
    $record->expiry_date = \Carbon\Carbon::parse($record->end_date)
        ->addMonths($record->program->validity_months)
        ->toDateString();
}
```

---

## 6. Audit Trail

All status changes are logged via `AuditService` and `ActivityService`:

| Event | Trigger | Where Logged |
|---|---|---|
| `training.record.created` | Record created | `audit_logs` + `activity_logs` |
| `training.record.updated` | Record fields updated | `audit_logs` (changed fields) + `activity_logs` |
| `training.record.status_changed` | Status transition | `activity_logs` (with old/new status) |
| `training.record.expired` | Auto-expiry detection | `activity_logs` |
| `training.program.created` | Program created | `audit_logs` + `activity_logs` |
| `training.program.updated` | Program updated | `audit_logs` + `activity_logs` |
| `training.file.uploaded` | Certificate uploaded | `audit_logs` |
| `training.file.downloaded` | Certificate downloaded | `audit_logs` |

### Audit Log Entry Format

```php
// Status change audit
AuditService::log(
    event: 'status_changed',
    model: $record,
    oldValues: ['status' => $oldStatus],
    newValues: ['status' => $newStatus],
    actor: $actor,
    moduleName: 'training',
    referenceId: $record->id,
);

// Activity log entry
ActivityService::log(
    moduleName: 'training',
    referenceId: $record->id,
    event: 'record.status_changed',
    description: "Status berubah: {$oldStatus} → {$newStatus}",
    actor: $actor,
    properties: ['old_status' => $oldStatus, 'new_status' => $newStatus],
);
```

---

## 7. Terminal Status Rules

- `cancelled` is the only terminal status for manual transitions.
- Once `cancelled`, a record cannot be transitioned to any other status.
- If a cancelled training needs to be re-scheduled, create a **new** training record.
- `expired` is NOT terminal — it can transition to `scheduled` (re-train) or back to `completed` (if expiry date is corrected).

---

## 8. Controller Integration Code

### Full Update Method (Reference)

```php
// TrainingRecordController.php

private const ALLOWED_TRANSITIONS = [
    'scheduled'   => ['in_progress', 'cancelled'],
    'in_progress' => ['completed', 'cancelled'],
    'completed'   => ['expired', 'in_progress'],
    'expired'     => ['scheduled', 'completed'],
    'cancelled'   => [],
];

public function update(
    UpdateTrainingRecordRequest $request,
    TrainingRecord $record,
    AuditService $auditService,
    ActivityService $activityService,
): RedirectResponse {
    $this->authorize('update', $record);

    $oldValues = $record->toArray();
    $oldStatus = $record->status;
    $newStatus = $request->input('status', $oldStatus);

    // Validate status transition
    if ($oldStatus !== $newStatus) {
        $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];

        if (!in_array($newStatus, $allowed)) {
            return back()->withErrors([
                'status' => "Transisi status dari '{$oldStatus}' ke '{$newStatus}' tidak diizinkan.",
            ]);
        }
    }

    // Handle certificate file upload
    if ($request->hasFile('certificate_file')) {
        if ($record->certificate_file_id) {
            $oldFile = ManagedFile::find($record->certificate_file_id);
            $oldFile?->delete();
        }

        $file = $request->file('certificate_file');
        $managedFile = app(ManagedFileService::class)->store(
            $file,
            new FileReference('training', $record->id, 'certificate'),
            auth()->user(),
        );
        $record->certificate_file_id = $managedFile->id;
    }

    // Auto-calculate expiry_date if end_date changed
    $validated = $request->validated();
    if (isset($validated['end_date'])
        && $record->program->validity_months
        && !isset($validated['expiry_date'])
    ) {
        $validated['expiry_date'] = Carbon::parse($validated['end_date'])
            ->addMonths($record->program->validity_months)
            ->toDateString();
    }

    $record->fill($validated);
    $record->save();

    // Auto-expiry check after save
    if ($record->status === 'completed'
        && $record->expiry_date
        && $record->expiry_date < now()->toDateString()
    ) {
        $record->update(['status' => 'expired']);
        $activityService->log(
            'training',
            $record->id,
            'record.expired',
            'Sertifikat kedaluwarsa secara otomatis',
            auth()->user(),
        );
    }

    // Log status change
    if ($oldStatus !== $record->status) {
        $activityService->log(
            'training',
            $record->id,
            'record.status_changed',
            "Status berubah: {$oldStatus} → {$record->status}",
            auth()->user(),
            ['old_status' => $oldStatus, 'new_status' => $record->status],
        );
    }

    // Audit trail
    $auditService->updated(
        $record->refresh(),
        $oldValues,
        auth()->user(),
        'training',
        $record->id,
    );

    return redirect()
        ->route('training.records.show', $record)
        ->with('success', 'Record pelatihan berhasil diperbarui.');
}
```

---

## 9. Visual Status Flow

```
                         ┌─────────────┐
                         │  scheduled  │ (initial)
                         └──────┬──────┘
                                │
                     ┌──────────┼──────────┐
                     │                     │
                     ▼                     ▼
              ┌─────────────┐       ┌───────────┐
              │ in_progress  │      │ cancelled  │ (terminal)
              └──────┬──────┘       └───────────┘
                     │
              ┌──────┼──────────┐
              │                 │
              ▼                 ▼
       ┌───────────┐     ┌───────────┐
       │ completed  │     │ cancelled  │ (terminal)
       └─────┬─────┘     └───────────┘
             │
     ┌───────┼──────────┐
     │       │           │
     │ auto  │ manual    │ manual
     ▼       ▼           ▼
┌──────────┐  ┌─────────────┐   ┌─────────────┐
│ expired   │  │ in_progress  │  │ completed    │
└────┬─────┘  │ (reopen)     │  │ (expiry fixed)│
     │        └─────────────┘  └─────────────┘
     │ manual
     ▼
┌─────────────┐
│  scheduled   │ (re-train)
│ (new record  │
│  preferred)  │
└─────────────┘
```

---

## 10. Notification Integration for Expiry

### Expiry Reminder Schedule

```php
// app/Console/Commands/SendExpiryReminders.php

class SendExpiryReminders extends Command
{
    protected $signature = 'training:send-expiry-reminders';
    protected $description = 'Send expiry reminder notifications (30d and 7d)';

    public function handle(NotificationService $notificationService): int
    {
        // 30-day reminders
        $expiring30d = TrainingRecord::where('status', 'completed')
            ->whereBetween('expiry_date', [
                now()->addDays(29)->toDateString(),
                now()->addDays(30)->toDateString(),
            ])
            ->get();

        foreach ($expiring30d as $record) {
            $recipients = $this->getRecipients($record);
            $notificationService->notifyMany(
                $recipients,
                'training.expiry_reminder_30d',
                [
                    'record' => $record->only(['id', 'training_number', 'expiry_date']),
                    'program' => $record->program->only(['name', 'code']),
                    'employee' => $record->employee->only(['name']),
                ],
                null,
                'training',
                $record->id,
                route('training.records.show', $record),
            );
        }

        // 7-day reminders
        $expiring7d = TrainingRecord::where('status', 'completed')
            ->whereBetween('expiry_date', [
                now()->addDays(6)->toDateString(),
                now()->addDays(7)->toDateString(),
            ])
            ->get();

        foreach ($expiring7d as $record) {
            $recipients = $this->getRecipients($record);
            $notificationService->notifyMany(
                $recipients,
                'training.expiry_reminder_7d',
                [
                    'record' => $record->only(['id', 'training_number', 'expiry_date']),
                    'program' => $record->program->only(['name', 'code']),
                    'employee' => $record->employee->only(['name']),
                ],
                null,
                'training',
                $record->id,
                route('training.records.show', $record),
            );
        }

        $this->info("Sent {$expiring30d->count()} 30-day and {$expiring7d->count()} 7-day reminders.");
        return self::SUCCESS;
    }

    private function getRecipients(TrainingRecord $record): Collection
    {
        $recipients = collect();

        // Employee's user account
        if ($record->employee->user) {
            $recipients->push($record->employee->user);
        }

        // Supervisor of the department
        $supervisors = User::whereHas('roles', fn ($q) => $q->where('name', 'Supervisor'))
            ->whereHas('employee', fn ($q) => $q->where('department_id', $record->employee->department_id))
            ->get();
        $recipients = $recipients->merge($supervisors);

        // QHSSE Officers for the site
        $officers = User::role('QHSSE Officer')
            ->whereHas('employee', fn ($q) => $q->where('site_id', $record->employee->site_id))
            ->get();
        $recipients = $recipients->merge($officers);

        return $recipients->unique('id');
    }
}
```

### Schedule

```php
Schedule::command('training:check-expiry')->dailyAt('00:01');
Schedule::command('training:send-expiry-reminders')->dailyAt('00:05');
```
