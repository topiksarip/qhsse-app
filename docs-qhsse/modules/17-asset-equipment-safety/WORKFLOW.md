# Workflow — Asset & Equipment Safety

## 1. Overview

Modul Asset & Equipment Safety tidak menggunakan workflow states yang kompleks seperti modul lain (Incident, CAPA, Audit). Modul ini menggunakan **lifecycle status sederhana** yang dikelola langsung pada field `assets.status`:

- `active` → `inactive` → `decommissioned`

Tidak ada WorkflowService integration yang required. Status transitions dikelola langsung di Controller dengan validation + audit trail.

Namun, modul ini memiliki **sub-process** untuk certificate expiry tracking dan inspection scheduling yang berjalan via scheduled jobs.

---

## 2. Asset Lifecycle States

| State | Type | Description |
|---|---|---|
| `active` | Initial (default) | Aset aktif beroperasi. Dapat diedit. Certificates dan inspections dapat ditambahkan. |
| `inactive` | Active | Aset tidak aktif sementara (maintenance, standby, perbaikan). Dapat diedit. Certificates dan inspections masih dapat ditambahkan. |
| `decommissioned` | **Terminal** | Aset telah dinyatakan tidak beroperasi permanen. Tidak dapat diedit. Certificates dan inspections tidak dapat ditambahkan. Read-only. |

### State Diagram

```
┌──────────┐    set_inactive    ┌──────────┐    set_active    ┌──────────┐
│          ├────────────────────►│          ├─────────────────►│          │
│  active  │                    │ inactive  │                  │  active  │
│          │◄───────────────────┤          │◄─────────────────┤          │
└────┬─────┘    set_active      └────┬─────┘                  └──────────┘
     │                                │
     │           decommission         │           decommission
     └────────────────────────────────┘
                     │
                     ▼
              ┌────────────────┐
              │                │
              │ decommissioned │  (Terminal — Read Only)
              │                │
              └────────────────┘
```

---

## 3. Transition Table

| From | To | Action | Label | Required Permission | Additional Checks |
|---|---|---|---|---|---|
| `active` | `inactive` | `set_inactive` | Set Tidak Aktif | `asset.management.update` | — |
| `inactive` | `active` | `set_active` | Set Aktif | `asset.management.update` | — |
| `active` | `decommissioned` | `decommission` | Decommission | `asset.management.update` | Only Admin, QHSSE Manager, Super Admin |
| `inactive` | `decommissioned` | `decommission` | Decommission | `asset.management.update` | Only Admin, QHSSE Manager, Super Admin |

### Notes

- `decommissioned` adalah terminal state — tidak dapat di-revert.
- Transition `decommission` hanya dapat dilakukan oleh role dengan elevated permission (Admin, QHSSE Manager, Super Admin).
- Status `active` dan `inactive` dapat berpindah bolak-balik.

---

## 4. Business Rules per Transition

### 4.1 Set Inactive (`active` → `inactive`)

**Preconditions:**
- Asset status must be `active`
- User must have `asset.management.update` permission

**Actions:**
1. Update `assets.status = 'inactive'`
2. `AuditService::updated($asset, $oldValues, $actor, 'asset', $asset->id)`
3. `ActivityService::log('asset', $asset->id, 'asset.set_inactive', "Asset set to inactive by {actor}", $actor)`

**Postconditions:**
- `assets.status = 'inactive'`
- Asset masih dapat diedit
- Certificates dan inspections masih dapat ditambahkan

### 4.2 Set Active (`inactive` → `active`)

**Preconditions:**
- Asset status must be `inactive`
- User must have `asset.management.update` permission

**Actions:**
1. Update `assets.status = 'active'`
2. `AuditService::updated($asset, $oldValues, $actor, 'asset', $asset->id)`
3. `ActivityService::log('asset', $asset->id, 'asset.set_active', "Asset set to active by {actor}", $actor)`

### 4.3 Decommission (`active`/`inactive` → `decommissioned`)

**Preconditions:**
- Asset status must be `active` or `inactive`
- User must have `asset.management.update` permission AND role must be Admin, QHSSE Manager, or Super Admin

**Actions:**
1. Update `assets.status = 'decommissioned'`
2. `AuditService::updated($asset, $oldValues, $actor, 'asset', $asset->id)`
3. `ActivityService::log('asset', $asset->id, 'asset.decommissioned', "Asset decommissioned by {actor}", $actor)`
4. `NotificationService::notifyMany($stakeholders, 'asset.decommissioned', [...])`

**Postconditions:**
- `assets.status = 'decommissioned'`
- Asset record is fully locked (read-only)
- Certificates tidak dapat ditambahkan/diedit
- Inspections tidak dapat ditambahkan
- Asset tetap muncul di list dengan badge "Decommissioned"

---

## 5. Certificate Expiry Tracking (Scheduled Process)

Modul ini menjalankan scheduled job harian untuk tracking masa berlaku sertifikat.

### 5.1 AssetCertificateStatusJob

**Schedule:** Daily at 06:00 (`schedule:job AssetCertificateStatusJob '06:00'`)

**Process:**

```
┌─────────────────────────────────────────────────────────────┐
│                  AssetCertificateStatusJob                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Query all asset_certificates where expiry_date IS NOT   │
│     NULL                                                    │
│                                                             │
│  2. For each certificate:                                   │
│     ┌───────────────────────────────────────────────────┐   │
│     │  Calculate status:                                 │   │
│     │                                                    │   │
│     │  if expiry_date < now():                           │   │
│     │    → status = 'expired'                            │   │
│     │                                                    │   │
│     │  elif expiry_date <= now() + 7 days:               │   │
│     │    → status = 'expiring_critical'                  │   │
│     │                                                    │   │
│     │  elif expiry_date <= now() + 30 days:              │   │
│     │    → status = 'expiring_soon'                     │   │
│     │                                                    │   │
│     │  else:                                             │   │
│     │    → status = 'valid'                              │   │
│     └───────────────────────────────────────────────────┘   │
│                                                             │
│  3. If status changed:                                      │
│     - Update certificate.status in DB                       │
│     - Log activity: 'certificate.status_changed'            │
│     - If new status = 'expired':                            │
│       → Send 'asset.certificate.expired' notification       │
│     - If new status = 'expiring_critical':                 │
│       → Send 'asset.certificate.expiring_critical' notif    │
│     - If new status = 'expiring_soon':                     │
│       → Send 'asset.certificate.expiring_soon' notif        │
│                                                             │
│  4. For safety-critical assets:                             │
│     - Additional notification at 14 days before expiry      │
│     - Escalation to Top Management if expired               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Notification Triggers

| Trigger | Condition | Notification Type | Recipients |
|---|---|---|---|
| Certificate expired | `expiry_date < now()` | `asset.certificate.expired` | QHSSE Manager, QHSSE Officer, Dept Head |
| Certificate expiring critical | `expiry_date` within 1-7 days | `asset.certificate.expiring_critical` | QHSSE Manager, QHSSE Officer, Dept Head, Top Mgmt (if safety_critical) |
| Certificate expiring soon | `expiry_date` within 8-30 days | `asset.certificate.expiring_soon` | QHSSE Manager, QHSSE Officer, Dept Head |

### 5.3 Status Calculation Logic

```php
// App\Services\Modules\Asset\AssetCertificateStatusService.php

class AssetCertificateStatusService
{
    public function calculateStatus(AssetCertificate $certificate): string
    {
        if ($certificate->expiry_date === null) {
            return 'valid';
        }

        $now = Carbon::now();
        $expiry = Carbon::parse($certificate->expiry_date);

        if ($expiry->isPast()) {
            return 'expired';
        }

        $daysUntilExpiry = $now->diffInDays($expiry, false);

        if ($daysUntilExpiry <= 7) {
            return 'expiring_critical';
        }

        if ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function updateStatuses(): int
    {
        $certificates = AssetCertificate::whereNotNull('expiry_date')->get();
        $updated = 0;

        foreach ($certificates as $certificate) {
            $newStatus = $this->calculateStatus($certificate);

            if ($certificate->status !== $newStatus) {
                $oldStatus = $certificate->status;
                $certificate->update(['status' => $newStatus]);

                $this->auditService->log(
                    event: 'certificate.status_changed',
                    model: $certificate,
                    oldValues: ['status' => $oldStatus],
                    newValues: ['status' => $newStatus],
                    actor: User::system(),
                    moduleName: 'asset',
                    referenceId: $certificate->asset_id,
                );

                $this->sendNotification($certificate, $newStatus);
                $updated++;
            }
        }

        return $updated;
    }

    private function sendNotification(AssetCertificate $certificate, string $status): void
    {
        $asset = $certificate->asset;
        $recipients = $this->getAssetStakeholders($asset);

        // Add Top Management for safety-critical expired certs
        if ($asset->safety_critical && $status === 'expired') {
            $recipients = array_merge(
                $recipients,
                User::role('Top Management')->get()->all()
            );
        }

        $type = match ($status) {
            'expired' => 'asset.certificate.expired',
            'expiring_critical' => 'asset.certificate.expiring_critical',
            'expiring_soon' => 'asset.certificate.expiring_soon',
            default => null,
        };

        if ($type === null) {
            return;
        }

        $this->notificationService->notifyMany(
            recipients: $recipients,
            type: $type,
            context: [
                'asset' => $asset->toArray(),
                'certificate' => $certificate->toArray(),
            ],
            actor: User::system(),
            moduleName: 'asset',
            referenceId: $asset->id,
            actionUrl: "/assets/{$asset->id}?tab=certificates",
        );
    }
}
```

---

## 6. Inspection Due Tracking (Scheduled Process)

### 6.1 AssetInspectionDueJob

**Schedule:** Daily at 06:30 (`schedule:job AssetInspectionDueJob '06:30'`)

**Process:**

```
┌─────────────────────────────────────────────────────────────┐
│                  AssetInspectionDueJob                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Query all asset_inspections where next_inspection_date   │
│     IS NOT NULL                                             │
│                                                             │
│  2. Filter: next_inspection_date <= now() + 7 days           │
│     AND no newer inspection exists for that asset            │
│                                                             │
│  3. For each due inspection:                                │
│     - Send 'asset.inspection.due' notification               │
│     - Recipients: QHSSE Officer (site), Supervisor          │
│     - If safety_critical: also notify QHSSE Manager         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. Certificate Status Flow

```
                    ┌─────────┐
                    │  valid  │  (expiry_date >= now+30d or NULL)
                    └────┬────┘
                         │
              expiry_date approaches
              (within 30 days)
                         │
                         ▼
               ┌─────────────────┐
               │ expiring_soon   │  (8-30 days until expiry)
               └───────┬─────────┘
                       │
              expiry_date approaches
              (within 7 days)
                       │
                       ▼
              ┌──────────────────┐
              │ expiring_critical│  (1-7 days until expiry)
              └────────┬─────────┘
                       │
              expiry_date < now()
                       │
                       ▼
               ┌──────────┐
               │ expired  │  (Terminal — until certificate renewed)
               └──────────┘
```

### Certificate Renewal Flow

When a certificate is renewed (updated with new expiry_date):
1. User updates certificate with new `expiry_date` and optionally new file
2. Status is recalculated based on new `expiry_date`
3. If new `expiry_date >= now() + 30 days` → status resets to `valid`
4. Activity log records: `certificate.renewed`
5. Audit trail records the update

---

## 8. Inspection Result Flow

```
┌────────────────────────────────────────────────────────────────┐
│                   Inspection Result Flow                        │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌──────────────┐                                              │
│  │ New          │                                              │
│  │ Inspection   │                                              │
│  └──────┬───────┘                                              │
│         │                                                      │
│    ┌────┼────────────┬───────────────┐                         │
│    │    │            │               │                         │
│    ▼    ▼            ▼               ▼                         │
│  ┌──────┐  ┌──────────────┐  ┌──────────────────┐             │
│  │ Pass │  │ Fail         │  │ Maintenance      │             │
│  └──┬───┘  └──────┬───────┘  │ Required         │             │
│     │             │          └────────┬─────────┘             │
│     │             │                   │                       │
│     │     Create CAPA?         Optional CAPA?                 │
│     │     (required)            (recommended)                 │
│     │             │                   │                       │
│     │             ▼                   ▼                       │
│     │     ┌──────────────┐    ┌──────────────┐                │
│     │     │ CAPA Action   │    │ CAPA Action  │                │
│     │     │ (linked)      │    │ (optional)   │                │
│     │     └──────────────┘    └──────────────┘                │
│     │             │                   │                         │
│     │             ▼                   ▼                       │
│     │     Asset can return    Asset can continue               │
│     │     to operation        with monitoring                  │
│     │             │                                         │
│     ▼             ▼                                         │
│  ┌──────────────────────┐                                   │
│  │ Next Inspection      │                                   │
│  │ Scheduled            │                                   │
│  │ (next_inspection_date)│                                   │
│  └──────────────────────┘                                   │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## 9. Audit Trail

All status changes and critical operations create audit trail entries:

### Asset Operations Audit Trail

| Operation | Event | Auditable | Old/New Values |
|---|---|---|---|
| Create asset | `asset.created` | Asset | new_values: all fields |
| Update asset | `asset.updated` | Asset | changed fields only |
| Set inactive | `asset.set_inactive` | Asset | status change |
| Set active | `asset.set_active` | Asset | status change |
| Decommission | `asset.decommissioned` | Asset | status change |
| Export | `asset.exported` | Asset | metadata: user, filters |

### Certificate Operations Audit Trail

| Operation | Event | Auditable | Old/New Values |
|---|---|---|---|
| Create certificate | `certificate.created` | AssetCertificate | new_values |
| Update certificate | `certificate.updated` | AssetCertificate | changed fields |
| Status changed (auto) | `certificate.status_changed` | AssetCertificate | status change |
| Certificate renewed | `certificate.renewed` | AssetCertificate | expiry_date change |
| File uploaded | `certificate.file_uploaded` | ManagedFile | new_values |
| File downloaded | `certificate.file_downloaded` | ManagedFile | metadata: user, ip |

### Inspection Operations Audit Trail

| Operation | Event | Auditable | Old/New Values |
|---|---|---|---|
| Create inspection | `inspection.created` | AssetInspection | new_values |
| CAPA linked | `inspection.capa_linked` | AssetInspection | capa_action_id change |

---

## 10. Terminal Status Rules

- `decommissioned` is the only terminal status for assets.
- No further status transitions allowed from `decommissioned`.
- Reopen/reactivate is **NOT** supported (asset is permanently out of service).
- Certificates and inspections cannot be added to decommissioned assets.
- Existing certificates and inspections remain visible (read-only).
- Evidence/certificate files cannot be uploaded or deleted after decommission.

---

## 11. Controller Integration

### Decommission Asset

```php
public function decommission(Asset $asset, Request $request): RedirectResponse
{
    $this->authorize('update', $asset);

    // Only Admin, QHSSE Manager, Super Admin can decommission
    if (!$request->user()->hasRole(['Super Admin', 'Admin', 'QHSSE Manager'])) {
        return back()->withErrors([
            'decommission' => 'Hanya Admin, QHSSE Manager, atau Super Admin yang dapat decommission aset.'
        ]);
    }

    if ($asset->status === 'decommissioned') {
        return back()->withErrors([
            'decommission' => 'Aset sudah berstatus decommissioned.'
        ]);
    }

    $oldValues = $asset->toArray();
    $asset->update(['status' => 'decommissioned']);

    $this->auditService->updated(
        model: $asset,
        oldValues: $oldValues,
        actor: $request->user(),
        moduleName: 'asset',
        referenceId: $asset->id,
    );

    $this->activityService->log(
        moduleName: 'asset',
        referenceId: $asset->id,
        event: 'asset.decommissioned',
        description: "Asset {$asset->asset_number} decommissioned by {$request->user()->name}",
        actor: $request->user(),
    );

    return back()->with('success', 'Aset berhasil di-decommission.');
}
```

### Set Status (Active/Inactive)

```php
public function setStatus(Asset $asset, Request $request): RedirectResponse
{
    $this->authorize('update', $asset);

    $validated = $request->validate([
        'status' => 'required|in:active,inactive',
    ]);

    if ($asset->status === 'decommissioned') {
        return back()->withErrors([
            'status' => 'Aset yang decommissioned tidak dapat diubah statusnya.'
        ]);
    }

    if ($asset->status === $validated['status']) {
        return back()->with('info', 'Status aset sudah ' . $validated['status'] . '.');
    }

    $oldValues = $asset->toArray();
    $asset->update(['status' => $validated['status']]);

    $this->auditService->updated(
        model: $asset,
        oldValues: $oldValues,
        actor: $request->user(),
        moduleName: 'asset',
        referenceId: $asset->id,
    );

    $event = $validated['status'] === 'active' ? 'asset.set_active' : 'asset.set_inactive';
    $label = $validated['status'] === 'active' ? 'activated' : 'set to inactive';

    $this->activityService->log(
        moduleName: 'asset',
        referenceId: $asset->id,
        event: $event,
        description: "Asset {$asset->asset_number} {$label} by {$request->user()->name}",
        actor: $request->user(),
    );

    return back()->with('success', "Status aset berhasil diubah menjadi {$validated['status']}.");
}
```

### Store Certificate

```php
public function store(Asset $asset, StoreAssetCertificateRequest $request): RedirectResponse
{
    if ($asset->status === 'decommissioned') {
        return back()->withErrors([
            'certificate' => 'Tidak dapat menambah sertifikat ke aset yang decommissioned.'
        ]);
    }

    $validated = $request->validated();

    // Upload file if provided
    $fileId = null;
    if ($request->hasFile('certificate_file')) {
        $managedFile = $this->fileService->store(
            file: $request->file('certificate_file'),
            reference: FileReference::for('asset', $asset->id, 'certificate'),
            user: $request->user(),
        );
        $fileId = $managedFile->id;
    }

    // Calculate initial status
    $status = $this->certificateStatusService->calculateStatus(
        new AssetCertificate(['expiry_date' => $validated['expiry_date'] ?? null])
    );

    $certificate = AssetCertificate::create([
        'asset_id' => $asset->id,
        'certificate_type' => $validated['certificate_type'],
        'certificate_number' => $validated['certificate_number'],
        'issued_date' => $validated['issued_date'],
        'expiry_date' => $validated['expiry_date'] ?? null,
        'issuing_body' => $validated['issuing_body'],
        'certificate_file_id' => $fileId,
        'status' => $status,
    ]);

    $this->auditService->created(
        model: $certificate,
        actor: $request->user(),
        moduleName: 'asset',
        referenceId: $certificate->id,
    );

    $this->activityService->log(
        moduleName: 'asset',
        referenceId: $asset->id,
        event: 'certificate.created',
        description: "Certificate {$certificate->certificate_number} created for asset {$asset->asset_number}",
        actor: $request->user(),
    );

    // If certificate is already expired or expiring, send notification
    if ($status !== 'valid') {
        $this->certificateStatusService->sendNotification($certificate, $status);
    }

    return back()->with('success', 'Sertifikat berhasil ditambahkan.');
}
```

### Store Inspection

```php
public function store(Asset $asset, StoreAssetInspectionRequest $request): RedirectResponse
{
    if ($asset->status === 'decommissioned') {
        return back()->withErrors([
            'inspection' => 'Tidak dapat menambah inspeksi ke aset yang decommissioned.'
        ]);
    }

    $validated = $request->validated();

    $inspection = AssetInspection::create([
        'asset_id' => $asset->id,
        'inspection_date' => $validated['inspection_date'],
        'inspector_id' => $validated['inspector_id'],
        'result' => $validated['result'],
        'notes' => $validated['notes'] ?? null,
        'next_inspection_date' => $validated['next_inspection_date'] ?? null,
    ]);

    $this->auditService->created(
        model: $inspection,
        actor: $request->user(),
        moduleName: 'asset',
        referenceId: $inspection->id,
    );

    $this->activityService->log(
        moduleName: 'asset',
        referenceId: $asset->id,
        event: 'inspection.created',
        description: "Inspection created for asset {$asset->asset_number}: result={$inspection->result}",
        actor: $request->user(),
    );

    $message = 'Inspeksi berhasil ditambahkan.';
    if ($validated['result'] === 'fail') {
        $message .= ' Hasil inspeksi GAGAL. Mohon buat CAPA untuk menindaklanjuti.';
    }

    return back()->with('success', $message);
}
```

---

## 12. Permission Registration

### Permissions to Register in `CorePermissions::all()`

```php
// Asset Management
'asset.management.view',
'asset.management.create',
'asset.management.update',
'asset.management.export',

// Asset Certificates
'asset.certificates.view',
'asset.certificates.create',
'asset.certificates.update',

// Asset Inspections
'asset.inspections.view',
'asset.inspections.create',
```

### Role-Permission Mapping in `CorePermissions::roleMap()`

```php
'Super Admin' => self::all(),
'Admin' => self::all(),

'QHSSE Manager' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.management.create',
    'asset.management.update',
    'asset.management.export',
    'asset.certificates.view',
    'asset.certificates.create',
    'asset.certificates.update',
    'asset.inspections.view',
    'asset.inspections.create',
],

'QHSSE Officer' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.management.create',
    'asset.management.update',
    'asset.management.export',
    'asset.certificates.view',
    'asset.certificates.create',
    'asset.certificates.update',
    'asset.inspections.view',
    'asset.inspections.create',
],

'Supervisor' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.management.export',
    'asset.certificates.view',
    'asset.inspections.view',
],

'Department Head' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.management.export',
    'asset.certificates.view',
    'asset.inspections.view',
],

'Employee / Reporter' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.certificates.view',
    'asset.inspections.view',
],

'Contractor' => [
    'asset.management.view',
],

'Auditor' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.management.export',
    'asset.certificates.view',
    'asset.inspections.view',
],

'Top Management' => [
    ...$viewOnly,
    'asset.management.view',
    'asset.management.export',
    'asset.certificates.view',
    'asset.inspections.view',
],
```

---

## 13. Scheduled Job Registration

Register in `routes/console.php` or `app/Console/Kernel.php`:

```php
// In routes/console.php (Laravel 12)
use App\Jobs\Modules\Asset\AssetCertificateStatusJob;
use App\Jobs\Modules\Asset\AssetInspectionDueJob;

Schedule::job(new AssetCertificateStatusJob)->dailyAt('06:00');
Schedule::job(new AssetInspectionDueJob)->dailyAt('06:30');
```
