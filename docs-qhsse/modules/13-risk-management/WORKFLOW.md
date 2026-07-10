# Workflow — Risk Management (HIRADC/JSA)

## 1. Overview

Modul Risk Management **tidak menggunakan workflow engine** (`WorkflowService`). Status berubah melalui controller action langsung — setiap action adalah endpoint POST terpisah yang memvalidasi status saat ini, mengubah status, dan mencatat audit trail + activity log.

Tidak ada `workflow_instances` atau `workflow_histories` untuk modul ini.

## 2. States

| State | Type | Description |
|---|---|---|
| `identified` | Initial | Risk register baru dibuat. Hazard teridentifikasi tetapi belum dinilai (severity/probability belum diisi). |
| `assessed` | Active | Risk assessment selesai. Severity, probability, dan risk level telah ditentukan. |
| `controls_needed` | Active | Risk level tinggi (RED/ORANGE) — additional controls diperlukan untuk menurunkan risiko. |
| `controls_in_place` | Active | Additional controls telah diimplementasi. Residual risk dapat dinilai. |
| `monitored` | Active | Risiko dalam pemantauan berkala. Controls berfungsi efektif. |
| `obsolete` | **Terminal** | Risiko tidak lagi relevan (aktivitas dihentikan, hazard tereliminasi, atau dipindahkan ke register lain). |

## 3. Transition Table

| # | From | To | Action Key | Route Name | Required Permission | Requires Data | Description |
|---|---|---|---|---|---|---|---|
| 1 | `identified` | `assessed` | `assess` | `risk.registers.assess` | `risk.registers.assess` | `severity_id`, `probability_id`, `risk_level_id` | Lakukan risk assessment |
| 2 | `assessed` | `controls_needed` | `needs_controls` | `risk.registers.needs_controls` | `risk.registers.assess` | — | Tandai perlu additional controls |
| 3 | `controls_needed` | `controls_in_place` | `implement_controls` | `risk.registers.implement_controls` | `risk.registers.assess` | `additional_controls` (non-empty) | Implementasi additional controls |
| 4 | `controls_in_place` | `monitored` | `monitor` | `risk.registers.monitor` | `risk.registers.assess` | — | Mulai pemantauan berkala |
| 5 | any (non-terminal) | `obsolete` | `obsolete` | `risk.registers.obsolete` | `risk.registers.assess` | — (reason optional) | Tandai sebagai obsolete |

### Transition Diagram

```
                    ┌─────────────┐
                    │  identified │
                    └──────┬──────┘
                           │ assess
                           ▼
                    ┌─────────────┐
                    │  assessed   │
                    └──────┬──────┘
                           │ needs_control
                           ▼
                    ┌─────────────┐
                    │controls_needed│
                    └──────┬──────┘
                           │ implement_controls
                           ▼
                    ┌─────────────┐
                    │controls_in  │
                    │  place      │
                    └──────┬──────┘
                           │ monitor
                           ▼
                    ┌─────────────┐
                    │  monitored │
                    └──────┬──────┘
                           │
                           ▼
                    ┌─────────────┐
                    │  obsolete  │ (terminal)
                    └─────────────┘

    ── obsolete dapat dipanggil dari status non-terminal manapun ──
```

## 4. Phase 1 Simplified Path

Phase 1 menggunakan semua 5 transitions. Tidak ada yang ditangguhkan:

```
identified ──(assess)──→ assessed ──(needs_control)──→ controls_needed
    ──(implement_controls)──→ controls_in_place ──(monitor)──→ monitored

obsolete dapat dipanggil kapan saja (dari status non-terminal manapun)
```

## 5. Validation Rules per Transition

### 5.1 assess (identified → assessed)

| Field | Rule | Required |
|---|---|---|
| `severity_id` | `required\|exists:severities,id` | ✅ |
| `probability_id` | `required\|integer\|min:1\|max:5` | ✅ |
| `risk_level_id` | `required\|exists:risk_matrix_levels,id` | ✅ (auto-calculated, validated) |
| `additional_controls` | `nullable\|string` | ❌ |
| `residual_severity_id` | `nullable\|exists:severities,id` | ❌ |
| `residual_probability_id` | `nullable\|integer\|min:1\|max:5` | ❌ |
| `residual_risk_level_id` | `nullable\|exists:risk_matrix_levels,id` | ❌ |

### 5.2 needs_control (assessed → controls_needed)

No request body needed. Controller hanya mengubah status.

### 5.3 implement_controls (controls_needed → controls_in_place)

| Field | Rule | Required |
|---|---|---|
| `additional_controls` | `required\|string` | ✅ (must not be empty) |

### 5.4 monitor (controls_in_place → monitored)

No request body needed. Controller hanya mengubah status.

### 5.5 obsolete (any non-terminal → obsolete)

| Field | Rule | Required |
|---|---|---|
| `reason` | `nullable\|string\|max:1000` | ❌ (recommended) |

## 6. Audit Trail

Setiap status transition secara otomatis mencatat:

1. `audit_logs` record via `AuditService::updated()` — dengan `old_values` (status lama + field yang berubah) dan `new_values` (status baru + field baru)
2. `activity_logs` record via `ActivityService::log()` — dengan event `risk.status_changed` dan description `Status: {old} → {new}`

### Audit Events

| Event | Trigger | Module | Reference |
|---|---|---|---|
| `risk.created` | Record dibuat | `risk` | `risk_registers.id` |
| `risk.updated` | Field diupdate | `risk` | `risk_registers.id` |
| `risk.assessed` | Transition assess | `risk` | `risk_registers.id` |
| `risk.status_changed` | Semua status transition | `risk` | `risk_registers.id` |
| `risk.obsolete` | Transition obsolete | `risk` | `risk_registers.id` |
| `risk.file.uploaded` | File diupload | `risk` | `risk_registers.id` |
| `risk.file.downloaded` | File didownload | `risk` | `risk_registers.id` |
| `risk.exported` | CSV export | `risk` | `risk_registers.id` |

## 7. Terminal Status Rules

- `obsolete` adalah terminal status.
- Tidak ada transition keluar dari `obsolete`.
- Record dengan status `obsolete` tidak dapat diedit (form edit tidak tersedia).
- Record dengan status `obsolete` tetap muncul di list (dengan filter default mengecualikannya jika diperlukan).
- Tidak ada reopen — jika risiko muncul kembali, buat risk register baru.

## 8. Controller Integration

```php
// Assess
public function assess(Request $request, RiskRegister $riskRegister): RedirectResponse
{
    $this->authorize('assess', $riskRegister);

    if ($riskRegister->status !== 'identified') {
        return back()->withErrors(['status' => 'Hanya risk register berstatus "identified" yang dapat dinilai.']);
    }

    $validated = $request->validate([
        'severity_id' => 'required|exists:severities,id',
        'probability_id' => 'required|integer|min:1|max:5',
        'risk_level_id' => 'required|exists:risk_matrix_levels,id',
        'additional_controls' => 'nullable|string',
        'residual_severity_id' => 'nullable|exists:severities,id',
        'residual_probability_id' => 'nullable|integer|min:1|max:5',
        'residual_risk_level_id' => 'nullable|exists:risk_matrix_levels,id',
    ]);

    $oldValues = $riskRegister->toArray();
    $oldStatus = $riskRegister->status;

    $riskRegister->update($validated);
    $riskRegister->update(['status' => 'assessed']);

    AuditService::updated($riskRegister, $oldValues, $request->user(), 'risk', $riskRegister->id);
    ActivityService::log('risk', $riskRegister->id, 'risk.assessed', 'Risk assessment completed', $request->user());
    ActivityService::log('risk', $riskRegister->id, 'risk.status_changed', "Status: {$oldStatus} → assessed", $request->user());

    NotificationService::notifyMany(
        $this->getQHSSEManagers($riskRegister),
        'risk.assessed',
        ['register_number' => $riskRegister->register_number, 'title' => $riskRegister->title, 'risk_level' => $riskRegister->riskLevel?->name],
        $request->user(),
        'risk',
        $riskRegister->id,
        route('risk.registers.show', $riskRegister),
    );

    return back()->with('success', 'Risk register berhasil dinilai.');
}

// Needs Controls
public function needsControls(Request $request, RiskRegister $riskRegister): RedirectResponse
{
    $this->authorize('assess', $riskRegister);

    if ($riskRegister->status !== 'assessed') {
        return back()->withErrors(['status' => 'Hanya risk register berstatus "assessed" yang dapat ditandai perlu kontrol.']);
    }

    $oldStatus = $riskRegister->status;
    $riskRegister->update(['status' => 'controls_needed']);

    AuditService::updated($riskRegister, ['status' => $oldStatus], $request->user(), 'risk', $riskRegister->id);
    ActivityService::log('risk', $riskRegister->id, 'risk.status_changed', "Status: {$oldStatus} → controls_needed", $request->user());

    NotificationService::notifyMany(
        $this->getRiskRecipients($riskRegister),
        'risk.controls_needed',
        ['register_number' => $riskRegister->register_number, 'title' => $riskRegister->title],
        $request->user(),
        'risk',
        $riskRegister->id,
        route('risk.registers.show', $riskRegister),
    );

    return back()->with('success', 'Risk register ditandai memerlukan kontrol tambahan.');
}

// Implement Controls
public function implementControls(Request $request, RiskRegister $riskRegister): RedirectResponse
{
    $this->authorize('assess', $riskRegister);

    if ($riskRegister->status !== 'controls_needed') {
        return back()->withErrors(['status' => 'Hanya risk register berstatus "controls_needed" yang dapat diimplementasi kontrolnya.']);
    }

    $validated = $request->validate([
        'additional_controls' => 'required|string',
    ]);

    $oldValues = $riskRegister->toArray();
    $oldStatus = $riskRegister->status;

    $riskRegister->update($validated);
    $riskRegister->update(['status' => 'controls_in_place']);

    AuditService::updated($riskRegister, $oldValues, $request->user(), 'risk', $riskRegister->id);
    ActivityService::log('risk', $riskRegister->id, 'risk.status_changed', "Status: {$oldStatus} → controls_in_place", $request->user());

    return back()->with('success', 'Kontrol tambahan berhasil diimplementasi.');
}

// Monitor
public function monitor(Request $request, RiskRegister $riskRegister): RedirectResponse
{
    $this->authorize('assess', $riskRegister);

    if ($riskRegister->status !== 'controls_in_place') {
        return back()->withErrors(['status' => 'Hanya risk register berstatus "controls_in_place" yang dapat dipantau.']);
    }

    $oldStatus = $riskRegister->status;
    $riskRegister->update(['status' => 'monitored']);

    AuditService::updated($riskRegister, ['status' => $oldStatus], $request->user(), 'risk', $riskRegister->id);
    ActivityService::log('risk', $riskRegister->id, 'risk.status_changed', "Status: {$oldStatus} → monitored", $request->user());

    return back()->with('success', 'Risk register sekarang dipantau.');
}

// Obsolete
public function obsolete(Request $request, RiskRegister $riskRegister): RedirectResponse
{
    $this->authorize('assess', $riskRegister);

    if ($riskRegister->status === 'obsolete') {
        return back()->withErrors(['status' => 'Risk register sudah obsolete.']);
    }

    $oldStatus = $riskRegister->status;
    $riskRegister->update(['status' => 'obsolete']);

    AuditService::updated($riskRegister, ['status' => $oldStatus], $request->user(), 'risk', $riskRegister->id);
    ActivityService::log('risk', $riskRegister->id, 'risk.obsolete', "Status: {$oldStatus} → obsolete", $request->user());

    NotificationService::notifyMany(
        $this->getRiskRecipients($riskRegister),
        'risk.obsolete',
        ['register_number' => $riskRegister->register_number, 'title' => $riskRegister->title],
        $request->user(),
        'risk',
        $riskRegister->id,
        route('risk.registers.show', $riskRegister),
    );

    return back()->with('success', 'Risk register ditetapkan sebagai obsolete.');
}
```

## 9. Status Helper (Model)

```php
// In RiskRegister model

public function isTerminal(): bool
{
    return $this->status === 'obsolete';
}

public function canBeAssessed(): bool
{
    return $this->status === 'identified';
}

public function canNeedControls(): bool
{
    return $this->status === 'assessed';
}

public function canImplementControls(): bool
{
    return $this->status === 'controls_needed';
}

public function canMonitor(): bool
{
    return $this->status === 'controls_in_place';
}

public function canBeObsoleted(): bool
{
    return $this->status !== 'obsolete';
}

public function getAvailableActions(): array
{
    $actions = [];

    if ($this->canBeAssessed()) {
        $actions[] = ['action_key' => 'assess', 'action_label' => 'Assess', 'route_name' => 'risk.registers.assess'];
    }
    if ($this->canNeedControls()) {
        $actions[] = ['action_key' => 'needs_control', 'action_label' => 'Needs Controls', 'route_name' => 'risk.registers.needs_controls'];
    }
    if ($this->canImplementControls()) {
        $actions[] = ['action_key' => 'implement_controls', 'action_label' => 'Implement Controls', 'route_name' => 'risk.registers.implement_controls'];
    }
    if ($this->canMonitor()) {
        $actions[] = ['action_key' => 'monitor', 'action_label' => 'Monitor', 'route_name' => 'risk.registers.monitor'];
    }
    if ($this->canBeObsoleted()) {
        $actions[] = ['action_key' => 'obsolete', 'action_label' => 'Obsolete', 'route_name' => 'risk.registers.obsolete'];
    }

    return $actions;
}
```

## 10. Notification Recipient Resolution

```php
private function getQHSSEManagers(RiskRegister $riskRegister): Collection
{
    return User::role('QHSSE Manager')
        ->where(function ($query) use ($riskRegister) {
            // Same site scope
            $query->whereHas('employee', function ($q) use ($riskRegister) {
                $q->where('site_id', $riskRegister->site_id);
            });
        })
        ->orWhereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))
        ->get();
}

private function getRiskRecipients(RiskRegister $riskRegister): Collection
{
    $recipients = collect();

    // Owner
    if ($riskRegister->owner_id) {
        $recipients->push($riskRegister->owner);
    }

    // QHSSE Manager in same site
    $managers = User::role('QHSSE Manager')
        ->whereHas('employee', fn ($q) => $q->where('site_id', $riskRegister->site_id))
        ->get();
    $recipients = $recipients->merge($managers);

    // Supervisor of department
    if ($riskRegister->department_id) {
        $supervisors = User::role('Supervisor')
            ->whereHas('employee', fn ($q) => $q->where('department_id', $riskRegister->department_id))
            ->get();
        $recipients = $recipients->merge($supervisors);
    }

    return $recipients->unique('id');
}
```

## 11. Seeder — Permissions

Add to `CorePermissions::all()`:

```php
'risk.registers.view',
'risk.registers.create',
'risk.registers.update',
'risk.registers.assess',
'risk.registers.export',
```

Add to `CorePermissions::roleMap()`:

```php
'Super Admin' => self::all(),  // includes risk.registers.*
'Admin' => self::all(),
'QHSSE Manager' => [...$viewOnly, 'risk.registers.create', 'risk.registers.update', 'risk.registers.assess', 'risk.registers.export'],
'QHSSE Officer' => [...$viewOnly, 'risk.registers.create', 'risk.registers.update', 'risk.registers.assess', 'risk.registers.export'],
'Supervisor' => ['core.companies.view', 'risk.registers.view', 'risk.registers.create', 'risk.registers.update', 'risk.registers.export'],
'Employee / Reporter' => ['core.scope.own', 'risk.registers.view'],
'Auditor' => [...$viewOnly, 'core.scope.all', 'risk.registers.export'],
'Top Management' => [...$viewOnly, 'core.scope.all', 'risk.registers.export'],
```

Where `$viewOnly` includes `'risk.registers.view'`.
