# Workflow — Communication & Campaign

> **Important:** Modul Communication & Campaign TIDAK menggunakan WorkflowService (workflow engine).
> Status kampanye dikelola melalui **simple status field** dengan transisi manual.

## 1. Simple Status (No Workflow Engine)

Berbeda dengan modul Incident Reporting yang menggunakan `WorkflowService` untuk transisi status,
modul Communication & Campaign menggunakan pendekatan **simple status**:

- Status disimpan sebagai field `status` (varchar) di tabel `campaigns`.
- Transisi status dilakukan manual melalui publish endpoint (`POST /campaigns/{campaign}/publish`).
- **Tidak ada `workflow_instances`** atau `workflow_histories` untuk modul ini.
- Setiap perubahan status tetap dicatat di `activity_logs` dan `audit_logs` untuk audit trail.

### Alasan Tidak Menggunakan Workflow Engine

1. Kampanye memiliki lifecycle yang sangat sederhana (2 status: draft → published).
2. Tidak ada approval/review gate yang memerlukan workflow definition.
3. Publish action adalah satu arah (tidak ada unpublish).
4. Acknowledgment adalah user action terpisah, bukan workflow transition.

---

## 2. Status States

| State | Type | Description |
|---|---|---|
| `draft` | Initial | Kampanye dibuat, belum dipublikasi. Dapat di-edit. Status default saat kampanye dibuat. |
| `published` | Terminal | Kampanye dipublikasi, tidak dapat di-edit. Notifikasi blast terkirim ke target audience. |

---

## 3. Transition Table (Manual)

Transisi dilakukan melalui publish endpoint. Controller memvalidasi transisi yang diizinkan:

| From | To | Trigger | Requires Permission | Notes |
|---|---|---|---|---|
| `draft` | `published` | Manual (publish action) | `communication.campaigns.publish` | Kampanye dipublikasi. `published_at` di-set. Notifikasi blast dikirim. |

### Transisi yang TIDAK Diizinkan

| From | To | Reason |
|---|---|---|
| `published` | `draft` | Published adalah terminal. Kampanye tidak dapat di-unpublish. |
| `published` | `published` | Kampanye sudah published, tidak perlu publish ulang. |
| `draft` | (any other) | Tidak ada status lain selain draft dan published. |

---

## 4. Transition Validation (Controller)

```php
// In CampaignController::publish()

private const ALLOWED_TRANSITIONS = [
    'draft'     => ['published'],
    'published' => [], // terminal — no transitions
];

public function publish(
    Campaign $campaign,
    AuditService $auditService,
    ActivityService $activityService,
    NotificationService $notificationService,
): RedirectResponse {
    $this->authorize('publish', $campaign);

    $actor = auth()->user();
    $oldStatus = $campaign->status;

    // Validate transition
    $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];

    if (!in_array('published', $allowed)) {
        return back()->withErrors([
            'status' => "Kampanye sudah berstatus '{$oldStatus}'. Tidak dapat dipublikasi ulang.",
        ]);
    }

    // Update campaign status
    $campaign->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    // Resolve target audience and send notification blast
    $recipients = $this->resolveTargetAudience($campaign);

    $notificationService->notifyMany(
        recipients: $recipients,
        type: 'communication.campaign_published',
        context: [
            'campaign' => $campaign->only(['id', 'campaign_number', 'title', 'type', 'published_at']),
            'type_label' => $this->getTypeLabel($campaign->type),
            'acknowledgment_message' => $this->getAcknowledgmentMessage($campaign->type),
        ],
        actor: $actor,
        moduleName: 'communication',
        referenceId: $campaign->id,
        actionUrl: route('communication.campaigns.show', $campaign),
    );

    // Log status change
    $activityService->log(
        moduleName: 'communication',
        referenceId: $campaign->id,
        event: 'campaign.published',
        description: "Kampanye dipublikasi oleh {$actor->name}",
        actor: $actor,
        properties: [
            'old_status' => $oldStatus,
            'new_status' => 'published',
            'published_at' => $campaign->published_at,
            'recipient_count' => $recipients->count(),
        ],
    );

    // Audit trail
    $auditService->log(
        event: 'published',
        model: $campaign,
        oldValues: ['status' => $oldStatus, 'published_at' => null],
        newValues: ['status' => 'published', 'published_at' => $campaign->published_at],
        actor: $actor,
        moduleName: 'communication',
        referenceId: $campaign->id,
    );

    return redirect()
        ->route('communication.campaigns.show', $campaign)
        ->with('success', 'Kampanye berhasil dipublikasi. Notifikasi terkirim ke ' . $recipients->count() . ' penerima.');
}
```

---

## 5. Acknowledgment Workflow

Acknowledgment adalah user action terpisah yang tidak terkait dengan status transition kampanye. Ini adalah konfirmasi individu bahwa user telah membaca kampanye.

### 5.1 Acknowledgment Flow

```
User menerima notifikasi kampanye published
        │
        ▼
User membuka halaman detail kampanye (/campaigns/{id})
        │
        ▼
Sistem cek: apakah user sudah acknowledge?
        │
        ├─── YA ──► Tampilkan "✅ Anda telah mengkonfirmasi"
        │
        └─── TIDAK ──► Tampilkan tombol "Saya Sudah Membaca"
                                    │
                                    ▼
                        User klik tombol acknowledge
                                    │
                                    ▼
                        POST /campaigns/{id}/acknowledge
                                    │
                                    ▼
                        Sistem validasi:
                        - Campaign status = published?
                        - User in target audience?
                        - User belum acknowledge?
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                    ▼               ▼               ▼
              Valid          Sudah ack         Tidak valid
                    │               │               │
                    ▼               ▼               ▼
          Create acknowledgment  Error:          Error:
          Log audit + activity   "sudah          "tidak termasuk
          Redirect + success     mengkonfirmasi"  target audiens"
```

### 5.2 Acknowledgment Reminder (Scheduled Command)

File: `app/Console/Commands/SendAcknowledgmentReminders.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Modules\Communication\Campaign;
use App\Core\Services\NotificationService;
use Illuminate\Console\Command;

class SendAcknowledgmentReminders extends Command
{
    protected $signature = 'communication:send-acknowledgment-reminders';
    protected $description = 'Send reminders for unacknowledged safety alerts';

    public function handle(NotificationService $notificationService): int
    {
        // Find published safety alerts that are not expired
        $campaigns = Campaign::where('type', 'safety_alert')
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->get();

        $reminderCount = 0;

        foreach ($campaigns as $campaign) {
            // Resolve target audience
            $targetUsers = $this->resolveTargetAudience($campaign);

            // Find users who haven't acknowledged
            $acknowledgedUserIds = $campaign->acknowledgments()
                ->pluck('user_id')
                ->toArray();

            $unacknowledgedUsers = $targetUsers->reject(
                fn ($user) => in_array($user->id, $acknowledgedUserIds)
            );

            if ($unacknowledgedUsers->isEmpty()) {
                continue;
            }

            $notificationService->notifyMany(
                recipients: $unacknowledgedUsers,
                type: 'communication.acknowledgment_reminder',
                context: [
                    'campaign' => $campaign->only(['id', 'campaign_number', 'title', 'type']),
                ],
                actor: null,
                moduleName: 'communication',
                referenceId: $campaign->id,
                actionUrl: route('communication.campaigns.show', $campaign),
            );

            $reminderCount += $unacknowledgedUsers->count();
        }

        $this->info("Sent {$reminderCount} acknowledgment reminders.");
        return self::SUCCESS;
    }

    private function resolveTargetAudience(Campaign $campaign)
    {
        $query = \App\Models\User::where('is_active', true);

        match ($campaign->target_audience) {
            'all' => $query,
            'specific_site' => $query->whereHas('employee', fn ($q) =>
                $q->where('site_id', $campaign->site_id)
            ),
            'specific_department' => $query->whereHas('employee', fn ($q) =>
                $q->where('department_id', $campaign->department_id)
            ),
            'specific_role' => $query->whereHas('roles', fn ($q) =>
                $q->where('name', $campaign->target_role)
            ),
        };

        return $query->get();
    }
}
```

### 5.3 Schedule Registration

File: `routes/console.php` or `app/Console/Kernel.php`

```php
use App\Console\Commands\SendAcknowledgmentReminders;

Schedule::command(SendAcknowledgmentReminders::class)->dailyAt('08:00');
```

---

## 6. Audit Trail

All critical actions are logged via `AuditService` and `ActivityService`:

| Event | Trigger | Where Logged |
|---|---|---|
| `communication.campaign.created` | Campaign created | `audit_logs` + `activity_logs` |
| `communication.campaign.updated` | Campaign fields updated | `audit_logs` (changed fields) + `activity_logs` |
| `communication.campaign.published` | Campaign published | `audit_logs` + `activity_logs` (with recipient count) |
| `communication.campaign.acknowledged` | User acknowledged campaign | `audit_logs` + `activity_logs` (with user info) |
| `communication.file.uploaded` | Attachment uploaded | `audit_logs` |
| `communication.file.downloaded` | Attachment downloaded | `audit_logs` |

### Audit Log Entry Format

```php
// Publish audit
AuditService::log(
    event: 'published',
    model: $campaign,
    oldValues: ['status' => 'draft', 'published_at' => null],
    newValues: ['status' => 'published', 'published_at' => $campaign->published_at],
    actor: $actor,
    moduleName: 'communication',
    referenceId: $campaign->id,
);

// Acknowledgment audit
AuditService::log(
    event: 'acknowledged',
    model: $acknowledgment,
    oldValues: null,
    newValues: $acknowledgment->toArray(),
    actor: $user,
    moduleName: 'communication',
    referenceId: $campaign->id,
);

// Activity log entry
ActivityService::log(
    moduleName: 'communication',
    referenceId: $campaign->id,
    event: 'campaign.published',
    description: "Kampanye dipublikasi oleh {$actor->name}",
    actor: $actor,
    properties: [
        'old_status' => 'draft',
        'new_status' => 'published',
        'recipient_count' => $recipients->count(),
    ],
);
```

---

## 7. Terminal Status Rules

- `published` is the only terminal status.
- Once `published`, a campaign cannot be transitioned back to `draft` or any other status.
- A published campaign cannot be edited (except `expires_at` by Super Admin).
- If content needs correction after publishing, create a **new** campaign with a reference to the original.
- `draft` campaigns can be deleted (if needed) but published campaigns are permanent historical records.

---

## 8. Controller Integration Code

### Full Publish Method (Reference)

```php
// CampaignController.php

private const ALLOWED_TRANSITIONS = [
    'draft'     => ['published'],
    'published' => [],
];

public function publish(
    Campaign $campaign,
    AuditService $auditService,
    ActivityService $activityService,
    NotificationService $notificationService,
): RedirectResponse {
    $this->authorize('publish', $campaign);

    $actor = auth()->user();
    $oldStatus = $campaign->status;

    // Validate transition
    $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];

    if (!in_array('published', $allowed)) {
        return back()->withErrors([
            'status' => "Kampanye sudah berstatus '{$oldStatus}'. Tidak dapat dipublikasi ulang.",
        ]);
    }

    // Update campaign status
    $campaign->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    // Resolve target audience and send notification blast
    $recipients = $this->resolveTargetAudience($campaign);

    $notificationService->notifyMany(
        recipients: $recipients,
        type: 'communication.campaign_published',
        context: [
            'campaign' => $campaign->only(['id', 'campaign_number', 'title', 'type', 'published_at']),
            'type_label' => $this->getTypeLabel($campaign->type),
            'acknowledgment_message' => $this->getAcknowledgmentMessage($campaign->type),
        ],
        actor: $actor,
        moduleName: 'communication',
        referenceId: $campaign->id,
        actionUrl: route('communication.campaigns.show', $campaign),
    );

    // Log status change
    $activityService->log(
        moduleName: 'communication',
        referenceId: $campaign->id,
        event: 'campaign.published',
        description: "Kampanye dipublikasi oleh {$actor->name}",
        actor: $actor,
        properties: [
            'old_status' => $oldStatus,
            'new_status' => 'published',
            'published_at' => $campaign->published_at,
            'recipient_count' => $recipients->count(),
        ],
    );

    // Audit trail
    $auditService->log(
        event: 'published',
        model: $campaign,
        oldValues: ['status' => $oldStatus, 'published_at' => null],
        newValues: ['status' => 'published', 'published_at' => $campaign->published_at],
        actor: $actor,
        moduleName: 'communication',
        referenceId: $campaign->id,
    );

    return redirect()
        ->route('communication.campaigns.show', $campaign)
        ->with('success', 'Kampanye berhasil dipublikasi. Notifikasi terkirim ke ' . $recipients->count() . ' penerima.');
}

private function getTypeLabel(string $type): string
{
    return match ($type) {
        'safety_alert'    => 'Safety Alert',
        'lesson_learned'  => 'Lesson Learned',
        'campaign'        => 'Kampanye',
        'announcement'     => 'Pengumuman',
        'newsletter'      => 'Newsletter',
        default            => ucfirst($type),
    };
}

private function getAcknowledgmentMessage(string $type): string
{
    return match ($type) {
        'safety_alert'    => 'Mohon segera baca dan konfirmasi (acknowledge) safety alert ini.',
        'lesson_learned'  => 'Silakan baca pelajaran yang dapat dipetik dari kejadian ini.',
        'campaign'        => 'Ikuti kampanye ini untuk meningkatkan budaya keselamatan.',
        'announcement'     => 'Mohon perhatikan pengumuman ini.',
        'newsletter'      => 'Buletin QHSSE terbaru telah terbit.',
        default            => 'Silakan baca kampanye ini.',
    };
}
```

### Full Acknowledge Method (Reference)

```php
public function acknowledge(
    Campaign $campaign,
    AuditService $auditService,
    ActivityService $activityService,
): RedirectResponse {
    $user = auth()->user();

    // Check campaign is published
    if ($campaign->status !== 'published') {
        return back()->withErrors([
            'status' => 'Hanya kampanye yang sudah dipublikasi yang dapat dikonfirmasi.',
        ]);
    }

    // Check user is in target audience
    if (!$campaign->isTargetedAt($user)) {
        return back()->withErrors([
            'target_audience' => 'Anda tidak termasuk target audiens kampanye ini.',
        ]);
    }

    // Check if already acknowledged (unique constraint also enforces)
    if ($campaign->isAcknowledgedBy($user)) {
        return back()->withErrors([
            'acknowledgment' => 'Anda sudah mengkonfirmasi (acknowledge) kampanye ini.',
        ]);
    }

    // Create acknowledgment
    $acknowledgment = CampaignAcknowledgment::create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'acknowledged_at' => now(),
        'ip_address' => request()->ip(),
    ]);

    // Audit trail
    $auditService->log(
        event: 'acknowledged',
        model: $acknowledgment,
        oldValues: null,
        newValues: $acknowledgment->toArray(),
        actor: $user,
        moduleName: 'communication',
        referenceId: $campaign->id,
    );

    // Activity log
    $activityService->log(
        moduleName: 'communication',
        referenceId: $campaign->id,
        event: 'campaign.acknowledged',
        description: "Dikonfirmasi oleh {$user->name}",
        actor: $user,
        properties: [
            'acknowledgment_id' => $acknowledgment->id,
            'ip_address' => $acknowledgment->ip_address,
        ],
    );

    return redirect()
        ->route('communication.campaigns.show', $campaign)
        ->with('success', 'Konfirmasi berhasil. Terima kasih telah membaca.');
}
```

---

## 9. Visual Status Flow

```
                    ┌──────────┐
                    │  draft   │ (initial)
                    │          │
                    └────┬─────┘
                         │
                         │ publish action
                         │ (communication.campaigns.publish)
                         │
                    ┌────▼─────┐
                    │ published │ (terminal)
                    │           │
                    └──────────┘
                         │
                         │ user actions (parallel, not status changes)
                         │
              ┌──────────┼──────────┐
              │                     │
              ▼                     ▼
    ┌──────────────────┐   ┌──────────────────┐
    │   View (increment │   │  Acknowledge      │
    │   view_count)     │   │  (create record   │
    │                   │   │   in              │
    │   Deduplicated    │   │   campaign_       │
    │   per user        │   │   acknowledgments)│
    └──────────────────┘   │                   │
                           │   Unique per      │
                           │   campaign+user   │
                           └──────────────────┘
```

---

## 10. Notification Integration

### Publish Blast Flow

```
1. User klik "Publish" pada draft campaign
        │
        ▼
2. Controller validate transition (draft → published)
        │
        ▼
3. Update campaign: status=published, published_at=now()
        │
        ▼
4. Resolve target audience:
   - all → all active users
   - specific_site → users at site_id
   - specific_department → users at department_id
   - specific_role → users with role=target_role
        │
        ▼
5. NotificationService::notifyMany()
   → Write to core_notifications for each recipient
   → Dispatch email if configured (queued)
        │
        ▼
6. Log activity: campaign.published (with recipient_count)
        │
        ▼
7. Log audit: published (old: draft, new: published)
        │
        ▼
8. Redirect to show page with success message
   "Kampanye berhasil dipublikasi. Notifikasi terkirim ke N penerima."
```

### Acknowledgment Reminder Schedule

```
Daily at 08:00 (Scheduled Command)
        │
        ▼
1. Query published safety_alert campaigns (not expired)
        │
        ▼
2. For each campaign:
   a. Resolve target audience
   b. Get acknowledged user IDs
   c. Find unacknowledged users
   d. If any unacknowledged:
      → Send reminder notification via notifyMany()
        │
        ▼
3. Log: "Sent N acknowledgment reminders."
```

---

## 11. Expiry Handling

### 11.1 Expiry Check (On-Access)

In `CampaignController::show()`:

```php
// Check if campaign is expired
$isExpired = $campaign->expires_at && $campaign->expires_at < now();
```

- Expired campaigns remain accessible (historical data).
- UI displays "Kedaluwarsa" badge when expired.
- Acknowledgment remains available for expired campaigns (late acknowledgment is allowed).
- No status change on expiry — campaign stays `published`.

### 11.2 No Auto-Status-Change on Expiry

Unlike training records (which auto-update `completed → expired`), campaigns do NOT change status on expiry. The `status` field remains `published`. Expiry is a UI display concern only.

This is intentional:
- Published campaigns are permanent communication records.
- Expiry indicates the campaign is no longer "active" but the information remains relevant.
- Users may still need to acknowledge late (especially for safety alerts).
