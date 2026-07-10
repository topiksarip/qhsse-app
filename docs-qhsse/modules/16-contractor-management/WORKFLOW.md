# Workflow — Contractor Management

## 1. Overview

Modul Contractor Management **tidak menggunakan WorkflowService** untuk lifecycle kontraktor. Status kontraktor (`active`, `inactive`, `blacklisted`) dikelola melalui field `status` langsung di tabel `contractors`, bukan melalui workflow transitions.

Modul ini tidak memiliki workflow states formal seperti modul Incident, CAPA, atau Audit. Alasannya:

- Kontraktor bukan entitas transaksional yang melalui alur approval bertahap.
- Status kontraktor (active/inactive/blacklisted) bersifat administratif dan dapat diubah langsung oleh admin.
- Prequalification adalah boolean flag, bukan workflow state.
- Evaluasi adalah catatan riwayat — setiap evaluasi bersifat final setelah dibuat (tidak ada draft → submit → review).

Namun, modul ini tetap mencatat semua perubahan melalui audit trail, activity log, dan notifikasi.

---

## 2. Contractor Lifecycle (Status)

### States

| State | Type | Description |
|---|---|---|
| `active` | Default | Kontraktor aktif dan dapat melakukan kegiatan. Dapat diajukan PTW. |
| `inactive` | Suspended | Kontraktor nonaktif sementara (misal: kontrak berakhir, evaluasi pending). Tidak dapat diajukan PTW baru. |
| `blacklisted` | Banned | Kontraktor dilarang bekerja karena pelanggaran serius atau evaluasi fail berulang. Tidak dapat diajukan PTW. |

### Status Transition Rules

| From | To | Trigger | Required Permission | Notes |
|---|---|---|---|---|
| (create) | `active` | Contractor created | `contractor.management.create` | Default status on creation |
| `active` | `inactive` | Manual update by QHSSE/Admin | `contractor.management.update` | Kontrak berakhir, evaluasi pending |
| `inactive` | `active` | Manual update by QHSSE/Admin | `contractor.management.update` | Kontrak diperpanjang |
| `active` | `blacklisted` | Manual update by Admin/QHSSE Manager | `contractor.management.update` | Pelanggaran serius |
| `inactive` | `blacklisted` | Manual update by Admin/QHSSE Manager | `contractor.management.update` | Pelanggaran serius |
| `blacklisted` | `inactive` | Manual update by Admin only | `contractor.management.update` | Re-evaluation setelah pembinaan |
| `blacklisted` | `active` | Manual update by Admin only | `contractor.management.update` | Hanya Admin, dengan catatan khusus |

### State Diagram

```
                    ┌──────────┐
           create   │          │
              ─────►│  active  │
                    │          │
                    └────┬─────┘
                         │
                    ┌────┴─────┐
                    │          │
                    ▼          ▼
              ┌──────────┐  ┌──────────────┐
              │          │  │              │
              │ inactive │  │ blacklisted  │
              │          │  │              │
              └────┬─────┘  └──────┬───────┘
                   │               │
                   └───────┬───────┘
                           │
                      (Admin only)
                           │
                           ▼
                    ┌──────────┐
                    │          │
                    │  active  │ (re-activated)
                    │          │
                    └──────────┘
```

### Implementation Notes

- Status change dilakukan melalui `PUT /contractors/{contractor}` (update) dengan mengubah field `status`.
- Controller memvalidasi transition permission (QHSSE Manager, Admin).
- Setiap status change dicatat di `audit_logs` dan `activity_logs`.
- Jika status berubah dari `active` ke `inactive` atau `blacklisted`, sistem dapat (future) menonaktifkan semua PTW aktif yang terkait.

---

## 3. Prequalification Flow

Prequalification adalah proses verifikasi dokumen dan evaluasi kontraktor untuk menentukan apakah kontraktor memenuhi syarat keselamatan.

### Prequalification States

| State | Condition | Description |
|---|---|---|
| Not Prequalified | `is_prequalified = false` | Kontraktor belum diverifikasi atau prequalification telah dicabut. |
| Prequalified | `is_prequalified = true AND prequalified_until > now+30d` | Kontraktor terverifikasi dan masih dalam masa berlaku. |
| Expiring Soon | `is_prequalified = true AND prequalified_until BETWEEN now AND now+30d` | Prequalification akan kedaluwarsa dalam ≤30 hari. |
| Expired | `is_prequalified = true AND prequalified_until < now` | Prequalification telah kedaluwarsa. |

### Prequalification Flow Diagram

```
┌──────────────────┐     set prequalified      ┌──────────────────┐
│                  │  (POST /prequalify)         │                  │
│  Not Prequalified ├────────────────────────────►│  Prequalified    │
│                  │                              │  (until date)    │
│  is_prequalified │                              │                  │
│  = false          │◄───────────────────────────┤  is_prequalified │
└──────────────────┘    revoke prequalified       │  = true           │
                        (DELETE /prequalify)       └────────┬─────────┘
                                                           │
                                                           │ prequalified_until ≤ 30 days from now
                                                           ▼
                                                 ┌──────────────────┐
                                                 │                  │
                                                 │  Expiring Soon   │
                                                 │  (notification)  │
                                                 │                  │
                                                 └────────┬─────────┘
                                                           │
                                                           │ prequalified_until < now
                                                           ▼
                                                 ┌──────────────────┐
                                                 │                  │
                                                 │  Expired         │
                                                 │  (flag still true│
                                                 │   but date past) │
                                                 │                  │
                                                 └──────────────────┘
```

### Set Prequalification Rules

- **Who**: QHSSE Manager, QHSSE Officer (permission `contractor.management.update`).
- **Requirement**: `prequalified_until` must be a future date.
- **Side effects**:
  - `is_prequalified` → `true`
  - `prequalified_until` → provided date
  - Audit log: `contractor.prequalified`
  - Activity log: `contractor.prequalified`
  - Notification: `contractor.prequalified` → contractor creator, related supervisors

### Revoke Prequalification Rules

- **Who**: QHSSE Manager, QHSSE Officer (permission `contractor.management.update`).
- **Requirement**: `is_prequalified` must currently be `true`.
- **Side effects**:
  - `is_prequalified` → `false`
  - `prequalified_until` → `NULL`
  - Audit log: `contractor.prequalification_revoked`
  - Activity log: `contractor.prequalification_revoked`

### Prequalification Expiry Check (Scheduled Job)

- **Schedule**: Daily at 08:00 via Laravel Scheduler.
- **Logic**: Find all contractors where `is_prequalified = true` AND `prequalified_until` ≤ `now() + 30 days` AND `prequalified_until` > `now()`.
- **Action**: Send `contractor.expiring_soon` notification to QHSSE Officer(s), QHSSE Manager(s), and contractor creator.
- **Note**: Sistem **tidak** otomatis mengubah `is_prequalified` ke `false` saat expired. Status prequalification tetap `true` namun badge menunjukkan "Kedaluwarsa". Revocation harus dilakukan manual.

---

## 4. Evaluation Flow

Evaluasi adalah penilaian kinerja keselamatan kontraktor yang dilakukan secara berkala.

### Evaluation Process

```
┌──────────────────┐    submit evaluation    ┌──────────────────────────┐
│                  │  (POST /evaluations)     │                          │
│  No Evaluation   ├─────────────────────────►│  Evaluation Created      │
│                  │                          │                          │
│  safety_rating   │                          │  1. Calculate total     │
│  = NULL          │                          │     score (sum criteria)│
└──────────────────┘                          │                          │
                                              │  2. Derive result        │
                                              │     ≥80 → pass           │
                                              │     60-79 → conditional  │
                                              │     <60 → fail           │
                                              │                          │
                                              │  3. Recalculate          │
                                              │     safety_rating        │
                                              │     (avg of 3 latest)    │
                                              │                          │
                                              │  4. Audit + Activity     │
                                              │     + Notification        │
                                              └──────────────────────────┘
```

### Safety Rating Calculation

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Safety Rating Calculation                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  1. Get 3 latest evaluations (ORDER BY evaluation_date DESC)      │
│                                                                    │
│  2. Calculate average total_score:                                │
│     avg = SUM(total_score) / COUNT(evaluations)                    │
│                                                                    │
│  3. Derive safety_rating:                                         │
│     avg ≥ 85  →  excellent   (🟢)                                  │
│     avg ≥ 70  →  good        (🔵)                                  │
│     avg ≥ 55  →  fair        (🟡)                                  │
│     avg < 55  →  poor        (🔴)                                  │
│                                                                    │
│  4. If no evaluations: safety_rating = NULL                       │
│                                                                    │
└─────────────────────────────────────────────────────────────────────┘
```

### Evaluation Result Derivation

| total_score | result | Description |
|---|---|---|
| ≥ 80 | `pass` | Memenuhi syarat — kontraktor dapat dipertimbangkan untuk prequalification |
| 60–79 | `conditional` | Bersyarat — perlu perbaikan pada area tertentu sebelum prequalification |
| < 60 | `fail` | Tidak memenuhi syarat — kontraktor tidak boleh di-prequalify |

### Evaluation Constraints

- Evaluasi bersifat **append-only** — tidak dapat diedit atau dihapus setelah dibuat (regulatory requirement).
- Setiap evaluasi wajib memiliki minimal 1 kriteria di `criteria` JSON.
- `evaluator_id` di-set otomatis ke user yang melakukan POST (tidak bisa di-set manual).
- Setelah evaluasi dibuat, `safety_rating` di contractor di-update otomatis.

---

## 5. Audit Trail

All changes to contractor records are logged via `AuditService` to the `audit_logs` table.

### Audited Events

| Event | Trigger | Auditable | What's Recorded |
|---|---|---|---|
| `contractor.created` | POST store | Contractor | new_values: all fields |
| `contractor.updated` | PUT update | Contractor | changed fields only (old vs new) |
| `contractor.evaluated` | POST evaluations.store | ContractorEvaluation | new_values: all evaluation fields |
| `contractor.safety_rating_updated` | Auto after evaluation | Contractor | old/new safety_rating value |
| `contractor.prequalified` | POST prequalify | Contractor | is_prequalified, prequalified_until changes |
| `contractor.prequalification_revoked` | DELETE prequalify | Contractor | is_prequalified, prequalified_until changes |
| `contractor.status_changed` | PUT update (status field) | Contractor | old/new status |
| `contractor.file.uploaded` | File upload | ManagedFile | new_values |
| `contractor.file.downloaded` | File download | ManagedFile | metadata: user, ip |

### Audit Log Entry Example

```php
// After contractor creation
$this->auditService->created(
    model: $contractor,
    actor: $actor,
    moduleName: 'contractor',
    referenceId: $contractor->id,
);
// Creates audit_logs entry:
// event: 'created'
// auditable_type: 'Contractor'
// auditable_id: {contractor.id}
// module_name: 'contractor'
// reference_id: {contractor.id}
// old_values: null
// new_values: {all contractor fields}
// actor_id: {actor.id}
// actor_name: {actor.name}
```

### Activity Log Events

| Event | Description Template |
|---|---|
| `contractor.created` | "Contractor {contractor_number} registered by {actor_name}" |
| `contractor.updated` | "Contractor {contractor_number} updated by {actor_name}" |
| `contractor.evaluated` | "Evaluation created by {actor_name}. Score: {total_score}/100 ({result}). Safety rating: {safety_rating}" |
| `contractor.prequalified` | "Contractor {contractor_number} prequalified until {date} by {actor_name}" |
| `contractor.prequalification_revoked` | "Contractor {contractor_number} prequalification revoked by {actor_name}" |
| `contractor.status_changed` | "Contractor {contractor_number} status changed from {old} to {new} by {actor_name}" |

---

## 6. Notification Events

| Event | Trigger | Recipients |
|---|---|---|
| `contractor.registered` | Contractor created | QHSSE Officer(s), QHSSE Manager(s) |
| `contractor.evaluated` | Evaluation created | QHSSE Manager(s), contractor creator |
| `contractor.prequalified` | Prequalification set | Contractor creator, related supervisors |
| `contractor.expiring_soon` | Scheduled job (30 days before expiry) | QHSSE Officer(s), QHSSE Manager(s), contractor creator |

### Notification Implementation

```php
// Example: contractor.registered notification
$this->notificationService->notifyMany(
    recipients: $this->getQhsseTeamUsers(),
    type: 'contractor.registered',
    context: [
        'contractor' => $contractor->toArray(),
        'company' => $contractor->company->toArray(),
        'actor' => $actor->toArray(),
    ],
    actor: $actor,
    moduleName: 'contractor',
    referenceId: $contractor->id,
    actionUrl: "/contractors/{$contractor->id}",
);
```

---

## 7. Controller Integration

### Full Controller: storeEvaluation

```php
/**
 * Store a new evaluation for a contractor.
 * Calculates total_score, derives result, and updates safety_rating.
 */
public function storeEvaluation(
    Contractor $contractor,
    StoreContractorEvaluationRequest $request
): RedirectResponse {
    $this->authorize('evaluate', $contractor);

    $actor = $request->user();
    $data = $request->validated();

    // 1. Calculate total_score from criteria
    $totalScore = array_sum($data['criteria']);

    // 2. Derive result from total_score
    $result = match (true) {
        $totalScore >= 80 => 'pass',
        $totalScore >= 60 => 'conditional',
        default           => 'fail',
    };

    // 3. Create evaluation record
    $evaluation = $contractor->evaluations()->create([
        'evaluation_date' => $data['evaluation_date'],
        'evaluator_id'    => $actor->id,
        'criteria'         => $data['criteria'],
        'total_score'      => $totalScore,
        'result'           => $result,
        'notes'            => $data['notes'] ?? null,
    ]);

    // 4. Recalculate contractor safety_rating
    $oldRating = $contractor->safety_rating;
    $newRating = $this->calculateSafetyRating($contractor);

    $contractor->update(['safety_rating' => $newRating]);

    // 5. Audit trail: evaluation created
    $this->auditService->created(
        model: $evaluation,
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
    );

    // 6. Audit trail: safety_rating updated (if changed)
    if ($oldRating !== $newRating) {
        $this->auditService->log(
            event: 'contractor.safety_rating_updated',
            model: $contractor,
            oldValues: ['safety_rating' => $oldRating],
            newValues: ['safety_rating' => $newRating],
            actor: $actor,
            moduleName: 'contractor',
            referenceId: $contractor->id,
        );
    }

    // 7. Activity log
    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.evaluated',
        description: "Evaluation created by {$actor->name}. Score: {$totalScore}/100 ({$result}). Safety rating: " . ($newRating ?? 'N/A'),
        actor: $actor,
    );

    // 8. Notification
    $this->notificationService->notifyMany(
        recipients: $this->getQhsseManagers(),
        type: 'contractor.evaluated',
        context: [
            'contractor'  => $contractor->fresh()->toArray(),
            'evaluation'  => $evaluation->toArray(),
            'evaluator'   => $actor->toArray(),
        ],
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
        actionUrl: "/contractors/{$contractor->id}",
    );

    return back()->with('success', 'Evaluasi berhasil ditambahkan.');
}
```

### Full Controller: setPrequalified

```php
/**
 * Activate prequalification for a contractor.
 */
public function setPrequalified(
    Contractor $contractor,
    UpdateContractorPrequalificationRequest $request
): RedirectResponse {
    $this->authorize('update', $contractor);

    $actor = $request->user();
    $oldValues = $contractor->toArray();

    $contractor->update([
        'is_prequalified'    => true,
        'prequalified_until'  => $request->validated()['prequalified_until'],
    ]);

    // Audit trail
    $this->auditService->updated(
        model: $contractor,
        oldValues: $oldValues,
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
    );

    // Activity log
    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.prequalified',
        description: "Contractor {$contractor->contractor_number} prequalified until {$contractor->prequalified_until} by {$actor->name}",
        actor: $actor,
    );

    // Notification
    $this->notificationService->notifyMany(
        recipients: $this->getContractorStakeholders($contractor),
        type: 'contractor.prequalified',
        context: [
            'contractor' => $contractor->fresh()->toArray(),
            'actor'      => $actor->toArray(),
        ],
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
        actionUrl: "/contractors/{$contractor->id}",
    );

    return back()->with('success', 'Prequalification berhasil diaktifkan.');
}
```

### Full Controller: revokePrequalified

```php
/**
 * Revoke prequalification from a contractor.
 */
public function revokePrequalified(Contractor $contractor, Request $request): RedirectResponse
{
    $this->authorize('update', $contractor);

    if (!$contractor->is_prequalified) {
        return back()->withErrors([
            'prequalify' => 'Kontraktor belum prequalified.',
        ]);
    }

    $actor = $request->user();
    $oldValues = $contractor->toArray();

    $contractor->update([
        'is_prequalified'    => false,
        'prequalified_until' => null,
    ]);

    // Audit trail
    $this->auditService->updated(
        model: $contractor,
        oldValues: $oldValues,
        actor: $actor,
        moduleName: 'contractor',
        referenceId: $contractor->id,
    );

    // Activity log
    $this->activityService->log(
        moduleName: 'contractor',
        referenceId: $contractor->id,
        event: 'contractor.prequalification_revoked',
        description: "Contractor {$contractor->contractor_number} prequalification revoked by {$actor->name}",
        actor: $actor,
    );

    return back()->with('success', 'Prequalification berhasil dicabut.');
}
```

### Safety Rating Calculation Helper

```php
/**
 * Calculate safety rating based on 3 latest evaluations.
 *
 * @return string|null One of: 'excellent', 'good', 'fair', 'poor', or null
 */
private function calculateSafetyRating(Contractor $contractor): ?string
{
    $evaluations = $contractor->evaluations()
        ->orderBy('evaluation_date', 'desc')
        ->limit(3)
        ->get();

    if ($evaluations->isEmpty()) {
        return null;
    }

    $avgScore = $evaluations->avg('total_score');

    return match (true) {
        $avgScore >= 85 => 'excellent',
        $avgScore >= 70 => 'good',
        $avgScore >= 55 => 'fair',
        default         => 'poor',
    };
}
```

---

## 8. Permission Registration

### Permissions to Register in `CorePermissions::all()`

```php
// Contractor Management
'contractor.management.view',
'contractor.management.create',
'contractor.management.update',
'contractor.management.evaluate',
'contractor.management.export',
```

### Role-Permission Mapping in `CorePermissions::roleMap()`

```php
'Super Admin' => self::all(),

'Admin' => self::all(),

'QHSSE Manager' => [
    ...$viewOnly,
    'contractor.management.view',
    'contractor.management.create',
    'contractor.management.update',
    'contractor.management.evaluate',
    'contractor.management.export',
],

'QHSSE Officer' => [
    ...$viewOnly,
    'contractor.management.view',
    'contractor.management.create',
    'contractor.management.update',
    'contractor.management.evaluate',
    'contractor.management.export',
],

'Supervisor' => [
    ...$viewOnly,
    'contractor.management.view',
    'contractor.management.export',
],

'Department Head' => [
    ...$viewOnly,
    'contractor.management.view',
    'contractor.management.export',
],

'Employee / Reporter' => [
    'core.scope.own',
    'contractor.management.view',
],

'Contractor' => [
    'core.scope.company',
    'contractor.management.view',
],

'Auditor' => [
    ...$viewOnly,
    'core.scope.all',
    'contractor.management.view',
    'contractor.management.export',
],

'Top Management' => [
    ...$viewOnly,
    'core.scope.all',
    'contractor.management.view',
    'contractor.management.export',
],
```

---

## 9. Numbering Registration

Numbering format for `contractor` is already seeded in Phase 0 (`numbering_formats` table):

| Property | Value |
|---|---|
| `module_name` | `contractor` |
| `prefix` | `CTR` |
| `padding` | `4` |
| `separator` | `-` |
| `reset_frequency` | `yearly` |
| `include_year` | `true` |
| `include_site_code` | `false` |
| `sample` | `CTR-2026-0001` |

No seeder changes needed for numbering — it's already in the database from Phase 0.

---

## 10. Summary: No Workflow Seeder Needed

Because Contractor Management does not use the `WorkflowService`:

- **No `workflow_definitions` entry** needed for `contractor`.
- **No `workflow_transitions` entries** needed.
- **No `ContractorManagementWorkflowSeeder`** needed.
- Status changes are handled directly via Eloquent `update()` on the `contractors` table.
- All changes are tracked via `audit_logs`, `activity_logs`, and `core_notifications`.

### What IS Needed in the Seeder

File: `database/seeders/ContractorManagementSeeder.php`

```php
class ContractorManagementSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions are registered via CorePermissions::all()
        //    (no separate permission seeding needed)

        // 2. Register the scheduled command in routes/console.php:
        //    Schedule::command('contractor:check-prequalification-expiry')
        //        ->dailyAt('08:00');

        // 3. (Optional) Seed sample contractors for development
        if (app()->environment('local', 'testing')) {
            $companies = Company::where('type', 'contractor')
                ->orWhere('type', 'vendor')
                ->where('is_active', true)
                ->limit(5)
                ->get();

            foreach ($companies as $company) {
                $contractor = Contractor::factory()->create([
                    'company_id' => $company->id,
                ]);

                // Create 1-3 evaluations per contractor
                ContractorEvaluation::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->create([
                        'contractor_id' => $contractor->id,
                    ]);
            }
        }
    }
}
```
