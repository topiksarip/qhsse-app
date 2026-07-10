# Workflow — Emergency Preparedness

## 1. Workflow Overview

Modul Emergency Preparedness menggunakan **simple status workflow** untuk drills — tidak menggunakan WorkflowService kompleks.
Status diatur langsung pada kolom `status` di tabel `emergency_drills` melalui controller methods.

Emergency plans dan emergency contacts tidak memiliki workflow — mereka adalah simple CRUD.

### Workflow Definition (propose to add to WorkflowSeeder)

| Property | Value |
|---|---|
| `module_name` | `emergency` |
| `code` | `EMERGENCY_DRILL_WORKFLOW` |
| `name` | `Emergency Drill Workflow` |
| `initial_status` | `scheduled` |
| `is_active` | `true` |

### Alternative: Simple Status (No WorkflowService)

Jika tidak ingin menggunakan WorkflowService, status transition di-handle langsung di controller:

```php
$drill->update(['status' => 'executed']);
```

Pendekatan ini lebih sederhana dan sesuai untuk modul dengan workflow yang hanya memiliki 2 status.

---

## 2. States (Emergency Drills)

| State | Type | Description |
|---|---|---|
| `scheduled` | Initial | Drill baru dijadwalkan. Bisa diedit. Belum dieksekusi. |
| `executed` | **Terminal** | Drill telah dilaksanakan. Hasil, temuan, dan rekomendasi tercatat. Tidak bisa diedit (kecuali Admin). |

### State Transition Diagram

```
                ┌───────────┐
        ──(create)──►│ scheduled │
                     └─────┬─────┘
                           │
                     (execute)
                           │
                           ▼
                     ┌───────────┐
                     │ executed  │  ← TERMINAL
                     └───────────┘
```

### Simplified Transition Diagram

```
scheduled ──(execute)──► executed

← TERMINAL — no further transitions allowed from `executed` →
```

---

## 3. Transition Table

| From | To | Action Key | Label | Requires Reason | Required Permission | Notes |
|---|---|---|---|---|---|---|
| `scheduled` | `executed` | `execute` | Eksekusi Latihan | ❌ | `emergency.drills.execute` | Mengisi executed_date, result, findings, recommendations |

### Transition Rules

1. **`execute`** (`scheduled` → `executed`):
   - Tidak memerlukan reason, tetapi memerlukan field wajib: `executed_date`, `participants_count`, `result`.
   - `findings` dan `recommendations` opsional namun disarankan.
   - Memicu notifikasi `emergency.drill_executed` ke QHSSE Manager dan plan contact_person.
   - Jika `result` = `fail` atau `needs_improvement`: juga mengirim notifikasi `emergency.drill_failed`.
   - Activity log: `emergency.drill_executed`.
   - Setelah executed, drill menjadi **read-only** (kecuali Admin).

---

## 4. Phase 15 Simplified Path

Phase 15 uses the simple status transitions:

```
scheduled ──(execute)──► executed
```

### Typical User Journey

1. **QHSSE Officer/Manager** membuat emergency plan → plan tersimpan
2. **QHSSE Officer** menjadwalkan drill untuk plan tersebut → status: `scheduled`
3. Notifikasi dikirim ke QHSSE team dan observer
4. **QHSSE Officer/Manager** melaksanakan drill pada tanggal terjadwal
5. User dengan permission `emergency.drills.execute` mencatat hasil eksekusi:
   - Isi `executed_date`, `participants_count`, `result`, `findings`, `recommendations`
   - Status → `executed`
6. Notifikasi dikirim ke QHSSE Manager dan contact person
7. Jika result `fail`/`needs_improvement`: notifikasi tambahan `emergency.drill_failed`

### Seeding (add to EmergencyPreparednessSeeder)

```php
// Option A: Simple status (no WorkflowService) — RECOMMENDED for Phase 15
// Status managed directly on the model column, no workflow_instances needed.

// Option B: If using WorkflowService, add workflow definition:
$definition = WorkflowDefinition::create([
    'module_name' => 'emergency',
    'code' => 'EMERGENCY_DRILL_WORKFLOW',
    'name' => 'Emergency Drill Workflow',
    'initial_status' => 'scheduled',
    'is_active' => true,
]);

$definition->transitions()->create([
    'from' => 'scheduled',
    'to' => 'executed',
    'action_key' => 'execute',
    'label' => 'Eksekusi Latihan',
    'requires_reason' => false,
    'permission' => 'emergency.drills.execute',
]);
```

---

## 5. Audit Trail

All transitions and critical actions automatically create audit and activity log entries:

| Event | Trigger | Audit Log | Activity Log |
|---|---|---|---|
| `emergency.plan_created` | Plan dibuat | `audit_logs` (event=created, new_values=all fields) | `activity_logs` (event=emergency.plan_created) |
| `emergency.plan_updated` | Plan di-update | `audit_logs` (event=updated, old/new changed fields) | `activity_logs` (event=emergency.plan_updated) |
| `emergency.plan_deleted` | Plan dihapus | `audit_logs` (event=deleted) | `activity_logs` (event=emergency.plan_deleted) |
| `emergency.drill_scheduled` | Drill dijadwalkan | `audit_logs` (event=created, new_values=all fields) | `activity_logs` (event=emergency.drill_scheduled) |
| `emergency.drill_executed` | Drill dieksekusi | `audit_logs` (event=updated, old/new status+result+findings) | `activity_logs` (event=emergency.drill_executed) |
| `emergency.drill_updated` | Drill di-update (sebelum eksekusi) | `audit_logs` (event=updated, changed fields) | `activity_logs` (event=emergency.drill_updated) |
| `emergency.contact_created` | Contact dibuat | `audit_logs` (event=created, new_values=all fields) | `activity_logs` (event=emergency.contact_created) |
| `emergency.contact_updated` | Contact di-update | `audit_logs` (event=updated, old/new changed fields) | `activity_logs` (event=emergency.contact_updated) |
| `emergency.file.uploaded` | File diupload | `audit_logs` (event=created, new_values) | `activity_logs` (event=emergency.file.uploaded) |
| `emergency.file.deleted` | File dihapus | `audit_logs` (event=deleted) | `activity_logs` (event=emergency.file.deleted) |

### Audit Trail Implementation

```php
// On plan create
AuditService::created(
    model: $plan,
    actor: $actor,
    moduleName: 'emergency',
    referenceId: $plan->id,
);

ActivityService::log(
    moduleName: 'emergency',
    referenceId: $plan->id,
    event: 'emergency.plan_created',
    description: "Rencana darurat {$plan->plan_number} - {$plan->name} dibuat oleh {$actor->name}",
    actor: $actor,
);

// On drill scheduling
AuditService::created(
    model: $drill,
    actor: $actor,
    moduleName: 'emergency',
    referenceId: $drill->id,
);

ActivityService::log(
    moduleName: 'emergency',
    referenceId: $drill->id,
    event: 'emergency.drill_scheduled',
    description: "Latihan darurat {$drill->drill_number} dijadwalkan untuk {$drill->scheduled_date} oleh {$actor->name}",
    actor: $actor,
);

// On drill execution
$oldValues = ['status' => 'scheduled', 'executed_date' => null, 'result' => null];
$newValues = [
    'status' => 'executed',
    'executed_date' => $request->executed_date,
    'participants_count' => $request->participants_count,
    'result' => $request->result,
    'findings' => $request->findings,
    'recommendations' => $request->recommendations,
];

AuditService::log(
    event: 'emergency.drill_executed',
    model: $drill,
    oldValues: $oldValues,
    newValues: $newValues,
    actor: $actor,
    moduleName: 'emergency',
    referenceId: $drill->id,
);

ActivityService::log(
    moduleName: 'emergency',
    referenceId: $drill->id,
    event: 'emergency.drill_executed',
    description: "Latihan darurat {$drill->drill_number} dieksekusi oleh {$actor->name}. Hasil: {$drill->result}. Peserta: {$drill->participants_count}.",
    actor: $actor,
);

// On contact create
AuditService::created(
    model: $contact,
    actor: $actor,
    moduleName: 'emergency',
    referenceId: $contact->id,
);

ActivityService::log(
    moduleName: 'emergency',
    referenceId: $contact->id,
    event: 'emergency.contact_created',
    description: "Kontak darurat {$contact->name} ({$contact->role}) dibuat oleh {$actor->name}",
    actor: $actor,
);
```

---

## 6. Terminal Status Rules

- `executed` adalah **terminal status** untuk drills.
- Setelah `executed`:
  - Drill tidak bisa diedit (kecuali Super Admin / Admin).
  - Tidak bisa transition ke status lain.
  - Field `result`, `findings`, `recommendations` tidak bisa diubah.
- Reopen **TIDAK didukung** di Phase 15. Jika perlu koreksi, hubungi Admin untuk manual update atau buat drill baru dan referensikan drill lama di findings.
- Emergency plans dan contacts tidak memiliki terminal status — selalu bisa diedit/dinonaktifkan.

---

## 7. Controller Integration

### Simple Status Approach (Recommended for Phase 15)

```php
// Execute drill
public function execute(EmergencyDrill $drill, ExecuteEmergencyDrillRequest $request): RedirectResponse
{
    $this->authorize('execute', $drill);
    abort_if($drill->status !== 'scheduled', 400, 'Latihan darurat sudah dieksekusi dan tidak dapat diubah.');

    $actor = $request->user();
    $oldValues = [
        'status' => $drill->status,
        'executed_date' => $drill->executed_date,
        'result' => $drill->result,
    ];

    $drill->update([
        'executed_date' => $request->executed_date,
        'participants_count' => $request->participants_count,
        'result' => $request->result,
        'findings' => $request->findings,
        'recommendations' => $request->recommendations,
        'status' => 'executed',
    ]);

    // Audit & Activity
    AuditService::log(
        event: 'emergency.drill_executed',
        model: $drill,
        oldValues: $oldValues,
        newValues: $drill->only(['status', 'executed_date', 'participants_count', 'result', 'findings', 'recommendations']),
        actor: $actor,
        moduleName: 'emergency',
        referenceId: $drill->id,
    );

    ActivityService::log(
        moduleName: 'emergency',
        referenceId: $drill->id,
        event: 'emergency.drill_executed',
        description: "Latihan darurat {$drill->drill_number} dieksekusi oleh {$actor->name}. Hasil: {$drill->result}.",
        actor: $actor,
    );

    // Notifications
    $recipients = User::role(['QHSSE Manager'])
        ->where('site_id', $drill->site_id)
        ->get();

    if ($drill->emergencyPlan->contactPerson) {
        $recipients->push($drill->emergencyPlan->contactPerson);
    }

    NotificationService::notifyMany(
        $recipients,
        'emergency.drill_executed',
        [
            'drill_number' => $drill->drill_number,
            'plan_name' => $drill->emergencyPlan->name,
            'executed_date' => $drill->executed_date,
            'result' => $drill->result,
            'participants_count' => $drill->participants_count,
        ],
        $actor,
        'emergency',
        $drill->id,
        route('emergency-drills.show', $drill),
    );

    // Additional notification for fail/needs_improvement
    if (in_array($drill->result, ['fail', 'needs_improvement'])) {
        $qhsseTeam = User::role(['QHSSE Officer', 'QHSSE Manager'])
            ->where('site_id', $drill->site_id)
            ->get();

        NotificationService::notifyMany(
            $qhsseTeam,
            'emergency.drill_failed',
            [
                'drill_number' => $drill->drill_number,
                'plan_name' => $drill->emergencyPlan->name,
                'result' => $drill->result,
                'findings' => $drill->findings,
                'recommendations' => $drill->recommendations,
            ],
            $actor,
            'emergency',
            $drill->id,
            route('emergency-drills.show', $drill),
        );
    }

    return redirect()->route('emergency-drills.show', $drill)
        ->with('success', 'Latihan darurat berhasil dieksekusi.');
}
```

### Store Drill (with numbering + notification)

```php
public function store(StoreEmergencyDrillRequest $request): RedirectResponse
{
    $actor = $request->user();
    $data = $request->validated();
    $data['status'] = 'scheduled';

    $drill = EmergencyDrill::create($data);

    // Generate drill number (shares 'emergency' numbering sequence with plans)
    $generatedNumber = app(NumberingService::class)->generate(
        moduleName: 'emergency',
        actor: $actor,
        referenceType: EmergencyDrill::class,
        referenceId: $drill->id,
    );
    $drill->update(['drill_number' => $generatedNumber->formatted]);

    // Audit & Activity
    AuditService::created($drill, $actor, 'emergency', $drill->id);

    ActivityService::log(
        moduleName: 'emergency',
        referenceId: $drill->id,
        event: 'emergency.drill_scheduled',
        description: "Latihan darurat {$drill->drill_number} dijadwalkan untuk {$drill->scheduled_date} oleh {$actor->name}",
        actor: $actor,
    );

    // Notification
    $recipients = User::role(['QHSSE Officer', 'QHSSE Manager'])
        ->where('site_id', $drill->site_id)
        ->get();
    $recipients->push($drill->observer);

    NotificationService::notifyMany(
        $recipients,
        'emergency.drill_scheduled',
        [
            'drill_number' => $drill->drill_number,
            'plan_name' => $drill->emergencyPlan->name,
            'scheduled_date' => $drill->scheduled_date,
            'observer_name' => $drill->observer->name,
        ],
        $actor,
        'emergency',
        $drill->id,
        route('emergency-drills.show', $drill),
    );

    return redirect()->route('emergency-drills.show', $drill)
        ->with('success', 'Latihan darurat berhasil dijadwalkan.');
}
```

---

## 8. Available Transitions Helper

Untuk UI — menentukan tombol mana yang tampil berdasarkan status drill saat ini:

```php
public static function getAvailableTransitions(string $status): array
{
    return match ($status) {
        'scheduled' => [
            ['action' => 'execute', 'label' => 'Eksekusi Latihan', 'permission' => 'emergency.drills.execute', 'requires_reason' => false],
        ],
        'executed' => [],
        default => [],
    };
}
```

### Inertia prop for Drill Show page:

```typescript
availableTransitions: {
    action: string;
    label: string;
    permission: string;
    requires_reason: boolean;
}[];
```

---

## 9. Notification Flow Summary

```
                        ┌─────────────────┐
                        │  Drill Created   │
                        │  (status:        │
                        │   scheduled)     │
                        └────────┬─────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
                    ▼            ▼            ▼
             ┌──────────┐ ┌──────────┐ ┌──────────┐
             │ QHSSE    │ │ Observer │ │ QHSSE    │
             │ Officer  │ │          │ │ Manager  │
             └──────────┘ └──────────┘ └──────────┘
             Notification: emergency.drill_scheduled

                        ┌─────────────────┐
                        │  Drill Executed  │
                        │  (status:        │
                        │   executed)      │
                        └────────┬─────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
                    ▼            ▼            ▼
             ┌──────────┐ ┌──────────┐ ┌──────────┐
             │ QHSSE    │ │ Contact  │ │ (if fail │
             │ Manager  │ │ Person   │ │ /needs   │
             │          │ │          │ │ improv)  │
             └──────────┘ └──────────┘ └──────────┘
             Notification:          Notification:
             emergency.             emergency.
             drill_executed          drill_failed
```
