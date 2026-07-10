# Workflow — Environmental Management

## 1. Workflow Overview

Modul Environmental Management menggunakan **simple status workflow** — tidak menggunakan WorkflowService kompleks.
Status diatur langsung pada kolom `status` di tabel `environmental_records` melalui controller methods.

### Workflow Definition (propose to add to WorkflowSeeder)

| Property | Value |
|---|---|
| `module_name` | `environment` |
| `code` | `ENVIRONMENT_WORKFLOW` |
| `name` | `Environmental Record Workflow` |
| `initial_status` | `recorded` |
| `is_active` | `true` |

### Alternative: Simple Status (No WorkflowService)

Jika tidak ingin menggunakan WorkflowService, status transition di-handle langsung di controller:

```php
$record->update(['status' => 'investigated']);
```

Pendekatan ini lebih sederhana dan sesuai untuk modul dengan workflow yang tidak kompleks.

---

## 2. States

| State | Type | Description |
|---|---|---|
| `recorded` | Initial | Record baru dibuat, belum ditindaklanjuti. Bisa diedit. |
| `investigated` | Active | Record sedang dalam investigasi QHSSE. Bisa diedit. |
| `action_open` | Active | CAPA telah dibuka untuk record ini. `capa_action_id` ter-set. |
| `closed` | **Terminal** | Record selesai. Tidak bisa diedit lagi. |

### State Transition Diagram

```
                     ┌──────────────────────────────────────────────────────┐
                     │                                                      │
                     ▼                                                      │
                ┌──────────┐                                                │
        ──(create)──►│ recorded │                                                │
                     └────┬─────┘                                                │
                          │                                                      │
                    (investigate)                                                │
                          │                                                      │
                          ▼                                                      │
                   ┌──────────────┐                                              │
                   │ investigated │──────────────────────────────────────────────┘
                   └──────┬───────┘    (close)
                          │                                                    │
                    (open_action)                                              │
                          │                                                    │
                          ▼                                                    │
                   ┌─────────────┐                                             │
                   │ action_open │─────────────────────────────────────────────┘
                   └──────┬──────┘    (close)
                          │
                    (close)
                          │
                          ▼
                   ┌────────┐
                   │ closed │  ← TERMINAL
                   └────────┘
```

### Simplified Transition Diagram

```
recorded ──(investigate)──► investigated ──(open_action)──► action_open ──(close)──► closed
   │                              │                                                          │
   └──────────(close)─────────────┘                                                          │
                                  │                                                          │
                                  └──────────(close)─────────────────────────────────────────┘
                                                                                             │
                                  ← TERMINAL — no further transitions allowed from `closed` →
```

---

## 3. Transition Table

| From | To | Action Key | Label | Requires Reason | Required Permission | Notes |
|---|---|---|---|---|---|---|
| `recorded` | `investigated` | `investigate` | Mulai Investigasi | ❌ | `environment.records.investigate` | QHSSE Officer/Manager mulai investigasi |
| `recorded` | `closed` | `close` | Tutup Langsung | ✅ | `environment.records.close` | Direct close untuk record tanpa exceedance |
| `investigated` | `action_open` | `open_action` | Buka CAPA | ❌ | `environment.records.investigate` | Membuka CAPA, set `capa_action_id` |
| `investigated` | `closed` | `close` | Tutup | ✅ | `environment.records.close` | Close setelah investigasi tanpa CAPA |
| `action_open` | `closed` | `close` | Tutup | ✅ | `environment.records.close` | Close setelah CAPA selesai |

### Transition Rules

1. **`investigate`** (`recorded` → `investigated`):
   - Tidak memerlukan reason.
   - Memicu notifikasi `environment.investigated` ke reporter.
   - Activity log: `environment.investigated`.

2. **`open_action`** (`investigated` → `action_open`):
   - Membuat atau menautkan record CAPA.
   - Set `capa_action_id` pada environmental record.
   - Activity log: `environment.action_opened`.

3. **`close`** (any → `closed`):
   - **Memerlukan reason** (text, min:10 karakter).
   - Memicu notifikasi `environment.closed` ke reporter dan stakeholder.
   - Activity log: `environment.closed`.
   - Setelah closed, record menjadi **read-only**.

---

## 4. Phase 10 Simplified Path

Phase 10 uses the simple status transitions:

```
recorded ──(investigate)──► investigated ──(open_action)──► action_open ──(close)──► closed
   │                              │
   └──────(close)─────────────────┘
```

### Typical User Journey

1. **Employee/Supervisor** creates record → status: `recorded`
2. Jika exceedance detected → notifikasi ke QHSSE Officer/Manager
3. **QHSSE Officer/Manager** investigates → status: `investigated`
4. Jika perlu CAPA → **QHSSE Officer/Manager** opens CAPA → status: `action_open`, `capa_action_id` set
5. Setelah CAPA selesai → **QHSSE Officer/Manager** closes → status: `closed`
6. Atau: close langsung dari `recorded`/`investigated` jika tidak perlu CAPA

### Seeding (add to WorkflowSeeder or EnvironmentalSeeder)

```php
// Option A: Simple status (no WorkflowService) — RECOMMENDED for Phase 10
// Status managed directly on the model column, no workflow_instances needed.

// Option B: If using WorkflowService, add workflow definition:
$definition = WorkflowDefinition::create([
    'module_name' => 'environment',
    'code' => 'ENVIRONMENT_WORKFLOW',
    'name' => 'Environmental Record Workflow',
    'initial_status' => 'recorded',
    'is_active' => true,
]);

$transitions = [
    ['from' => 'recorded', 'to' => 'investigated', 'action_key' => 'investigate', 'label' => 'Mulai Investigasi', 'requires_reason' => false, 'permission' => 'environment.records.investigate'],
    ['from' => 'recorded', 'to' => 'closed', 'action_key' => 'close', 'label' => 'Tutup Langsung', 'requires_reason' => true, 'permission' => 'environment.records.close'],
    ['from' => 'investigated', 'to' => 'action_open', 'action_key' => 'open_action', 'label' => 'Buka CAPA', 'requires_reason' => false, 'permission' => 'environment.records.investigate'],
    ['from' => 'investigated', 'to' => 'closed', 'action_key' => 'close', 'label' => 'Tutup', 'requires_reason' => true, 'permission' => 'environment.records.close'],
    ['from' => 'action_open', 'to' => 'closed', 'action_key' => 'close', 'label' => 'Tutup', 'requires_reason' => true, 'permission' => 'environment.records.close'],
];

foreach ($transitions as $t) {
    $definition->transitions()->create($t);
}
```

---

## 5. Audit Trail

All transitions automatically create audit and activity log entries:

| Event | Trigger | Audit Log | Activity Log |
|---|---|---|---|
| `environment.created` | Record dibuat | `audit_logs` (event=created, new_values=all fields) | `activity_logs` (event=created) |
| `environment.updated` | Record di-update | `audit_logs` (event=updated, old/new changed fields) | `activity_logs` (event=updated) |
| `environment.investigated` | Status → investigated | `audit_logs` (event=investigated, status change) | `activity_logs` (event=investigated) |
| `environment.action_opened` | Status → action_open | `audit_logs` (event=action_opened, status+capa_action_id) | `activity_logs` (event=action_opened) |
| `environment.closed` | Status → closed | `audit_logs` (event=closed, status+reason) | `activity_logs` (event=closed) |
| `environment.exceedance_detected` | is_exceedance: false → true | `audit_logs` (event=exceedance_detected) | `activity_logs` (event=exceedance_detected) |
| `environment.deleted` | Soft delete | `audit_logs` (event=deleted) | `activity_logs` (event=deleted) |

### Audit Trail Implementation

```php
// On investigate
AuditService::log(
    event: 'environment.investigated',
    model: $record,
    oldValues: ['status' => 'recorded'],
    newValues: ['status' => 'investigated'],
    actor: $actor,
    moduleName: 'environment',
    referenceId: $record->id,
);

ActivityService::log(
    moduleName: 'environment',
    referenceId: $record->id,
    event: 'environment.investigated',
    description: "Record {$record->record_number} mulai diinvestigasi oleh {$actor->name}",
    actor: $actor,
);

// On close (with reason)
AuditService::log(
    event: 'environment.closed',
    model: $record,
    oldValues: ['status' => $oldStatus],
    newValues: ['status' => 'closed', 'reason' => $reason],
    actor: $actor,
    moduleName: 'environment',
    referenceId: $record->id,
);

ActivityService::log(
    moduleName: 'environment',
    referenceId: $record->id,
    event: 'environment.closed',
    description: "Record {$record->record_number} ditutup oleh {$actor->name}. Alasan: {$reason}",
    actor: $actor,
);

// On exceedance detected (auto)
if ($record->wasChanged('is_exceedance') && $record->is_exceedance) {
    AuditService::log(
        event: 'environment.exceedance_detected',
        model: $record,
        oldValues: ['is_exceedance' => false],
        newValues: ['is_exceedance' => true, 'measured_value' => $record->measured_value, 'limit_value' => $record->limit_value],
        actor: $actor,
        moduleName: 'environment',
        referenceId: $record->id,
    );

    ActivityService::log(
        moduleName: 'environment',
        referenceId: $record->id,
        event: 'environment.exceedance_detected',
        description: "Exceedance terdeteksi pada {$record->record_number}: nilai {$record->measured_value} {$record->unit} melebihi batas {$record->limit_value} {$record->unit}",
        actor: $actor,
    );
}
```

---

## 6. Terminal Status Rules

- `closed` adalah **terminal status**.
- Setelah `closed`:
  - Tidak bisa edit record.
  - Tidak bisa hapus evidence files.
  - Tidak bisa add comments (kecuali Super Admin / Admin).
  - Tidak bisa transition ke status lain.
- Reopen **TIDAK didukung** di Phase 10. Jika perlu reopen, buat record baru dan referensikan record lama di deskripsi.

---

## 7. Controller Integration

### Simple Status Approach (Recommended for Phase 10)

```php
// Investigate
public function investigate(EnvironmentalRecord $record, Request $request): RedirectResponse
{
    $this->authorize('investigate', $record);
    abort_if($record->status !== 'recorded', 400, 'Record tidak dapat diinvestigasi dari status saat ini.');

    $actor = $request->user();
    $oldStatus = $record->status;

    $record->update(['status' => 'investigated']);

    AuditService::log('environment.investigated', $record, ['status' => $oldStatus], ['status' => 'investigated'], $actor, 'environment', $record->id);
    ActivityService::log('environment', $record->id, 'environment.investigated', "Record {$record->record_number} mulai diinvestigasi", $actor);

    NotificationService::notify($record->reporter, 'environment.investigated', [...], $actor, 'environment', $record->id);

    return back()->with('success', 'Record mulai diinvestigasi.');
}

// Open CAPA
public function openAction(EnvironmentalRecord $record, Request $request): RedirectResponse
{
    $this->authorize('investigate', $record);
    abort_if($record->status !== 'investigated', 400, 'CAPA hanya dapat dibuka dari status investigated.');

    $actor = $request->user();
    $oldStatus = $record->status;

    // Create or link CAPA
    $capaAction = CapaAction::create([
        // ... CAPA data with source_module='environment', source_reference_id=$record->id
    ]);

    $record->update([
        'status' => 'action_open',
        'capa_action_id' => $capaAction->id,
    ]);

    AuditService::log('environment.action_opened', $record, ['status' => $oldStatus], ['status' => 'action_open', 'capa_action_id' => $capaAction->id], $actor, 'environment', $record->id);
    ActivityService::log('environment', $record->id, 'environment.action_opened', "CAPA {$capaAction->number} dibuka untuk record {$record->record_number}", $actor);

    return back()->with('success', 'CAPA berhasil dibuka.');
}

// Close (with reason)
public function close(EnvironmentalRecord $record, Request $request): RedirectResponse
{
    $this->authorize('close', $record);
    $request->validate(['reason' => 'required|string|min:10|max:1000']);

    abort_if($record->status === 'closed', 400, 'Record sudah ditutup.');

    $actor = $request->user();
    $oldStatus = $record->status;

    $record->update(['status' => 'closed']);

    AuditService::log('environment.closed', $record, ['status' => $oldStatus], ['status' => 'closed', 'reason' => $request->reason], $actor, 'environment', $record->id);
    ActivityService::log('environment', $record->id, 'environment.closed', "Record {$record->record_number} ditutup. Alasan: {$request->reason}", $actor);

    NotificationService::notify($record->reporter, 'environment.closed', [...], $actor, 'environment', $record->id);

    return back()->with('success', 'Record berhasil ditutup.');
}
```

### Exceedance Auto-Detection (in Model Observer or Service)

```php
// EnvironmentalRecordObserver.php
public function saving(EnvironmentalRecord $record): void
{
    // Auto-detect exceedance
    if ($record->measured_value !== null && $record->limit_value !== null) {
        $wasExceedance = $record->getOriginal('is_exceedance') ?? false;
        $record->is_exceedance = $record->measured_value > $record->limit_value;

        // If exceedance newly detected, flag for notification
        if (!$wasExceedance && $record->is_exceedance) {
            $record->exceedanceNewlyDetected = true;
        }
    } else {
        $record->is_exceedance = false;
    }
}

public function saved(EnvironmentalRecord $record): void
{
    if (property_exists($record, 'exceedanceNewlyDetected') && $record->exceedanceNewlyDetected) {
        // Log exceedance detection
        ActivityService::log(
            moduleName: 'environment',
            referenceId: $record->id,
            event: 'environment.exceedance_detected',
            description: "Exceedance terdeteksi: nilai {$record->measured_value} {$record->unit} melebihi batas {$record->limit_value} {$record->unit}",
            actor: $record->reporter,
        );

        // Notify QHSSE team
        $qhsseUsers = User::role(['QHSSE Officer', 'QHSSE Manager'])->where('site_id', $record->site_id)->get();
        NotificationService::notifyMany($qhsseUsers, 'environment.exceedance_detected', [
            'record_number' => $record->record_number,
            'title' => $record->title,
            'measured_value' => $record->measured_value,
            'limit_value' => $record->limit_value,
            'unit' => $record->unit,
        ], $record->reporter, 'environment', $record->id, route('environmental-records.show', $record));
    }
}
```

---

## 8. Available Transitions Helper

Untuk UI — menentukan tombol mana yang tampil berdasarkan status saat ini:

```php
public static function getAvailableTransitions(string $status): array
{
    return match ($status) {
        'recorded' => [
            ['action' => 'investigate', 'label' => 'Mulai Investigasi', 'permission' => 'environment.records.investigate', 'requires_reason' => false],
            ['action' => 'close', 'label' => 'Tutup Langsung', 'permission' => 'environment.records.close', 'requires_reason' => true],
        ],
        'investigated' => [
            ['action' => 'open_action', 'label' => 'Buka CAPA', 'permission' => 'environment.records.investigate', 'requires_reason' => false],
            ['action' => 'close', 'label' => 'Tutup', 'permission' => 'environment.records.close', 'requires_reason' => true],
        ],
        'action_open' => [
            ['action' => 'close', 'label' => 'Tutup', 'permission' => 'environment.records.close', 'requires_reason' => true],
        ],
        'closed' => [],
        default => [],
    };
}
```

### Inertia prop for Show page:

```typescript
availableTransitions: {
    action: string;
    label: string;
    permission: string;
    requires_reason: boolean;
}[];
```
