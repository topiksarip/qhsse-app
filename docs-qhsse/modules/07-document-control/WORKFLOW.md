# Workflow — Document Control

## 1. Workflow Definition (Already Seeded)

The `document` workflow is pre-seeded in `WorkflowSeeder` (Phase 0).

| Property | Value |
|---|---|
| `module_name` | `document` |
| `code` | `DOCUMENT_WORKFLOW` |
| `name` | `Document Workflow` |
| `initial_status` | `draft` |
| `is_active` | `true` |

## 2. States

| State | Type | Description |
|---|---|---|
| `draft` | Initial | Dokumen dibuat, belum di-submit review. Bisa diedit. |
| `review` | Active | Dokumen di-submit untuk review QHSSE Manager. Menunggu approve/reject. |
| `approved` | Active | Dokumen disetujui QHSSE Manager. Siap untuk di-effective-kan. |
| `effective` | Active | Dokumen berlaku efektif. Dapat di-obsolete. |
| `obsolete` | **Terminal** | Dokumen sudah usang/tidak berlaku. Tidak bisa diedit. |
| `rejected` | Reversible | Dokumen ditolak. Dapat di-revise kembali ke draft. |

## 3. Transition Table (Seeded)

| From | To | Action Key | Label | Requires Reason | Required Permission |
|---|---|---|---|---|---|
| `draft` | `review` | `submit_review` | Submit for Review | ❌ | `document.control.submit_review` |
| `review` | `approved` | `approve` | Approve | ❌ | `document.control.approve` |
| `approved` | `effective` | `make_effective` | Make Effective | ❌ | `document.control.make_effective` |
| `effective` | `obsolete` | `obsolete` | Obsolete | ✅ | `document.control.obsolete` |
| `review` | `rejected` | `reject` | Reject | ✅ | `document.control.approve` |
| `rejected` | `draft` | `revise` | Revise | ❌ | `document.control.update` |

## 4. Full Workflow Path

```
draft ──(submit_review)──→ review ──(approve)──→ approved ──(make_effective)──→ effective ──(obsolete)──→ obsolete
                                  ↘(reject)──→ rejected ──(revise)──→ draft
```

### Workflow Diagram (ASCII)

```
                    ┌─────────────────────────────────────────────────────────────────┐
                    │                                                                 │
                    ▼                                                                 │
              ┌───────────┐     submit_review     ┌─────────┐     approve      ┌───────────┐
              │   draft    │──────────────────────→ │ review  │───────────────→ │ approved  │
              │           │                        │         │                  │           │
              └─────┬─────┘                        └────┬────┘                  └─────┬─────┘
                    ▲                                  │                              │
                    │                                  │ reject                       │ make_effective
                    │ revise                           ▼                              │
                    │                            ┌───────────┐                        ▼
                    │                            │ rejected  │                  ┌───────────┐
                    └────────────────────────────│           │                  │ effective  │
                                                 └───────────┘                  │           │
                                                                                └─────┬─────┘
                                                                                      │
                                                                                      │ obsolete (requires reason)
                                                                                      ▼
                                                                                ┌───────────┐
                                                                                │ obsolete  │
                                                                                │ (TERMINAL)│
                                                                                └───────────┘
```

## 5. Version Tracking via document_reviews

Each `submit_review` transition creates a new `document_reviews` record:

| Step | Action | document_reviews record |
|---|---|---|
| 1. Owner submits for review | `submit_review` | `create(['document_id' => $doc->id, 'decision' => 'pending'])` |
| 2. Manager approves | `approve` | `update(['reviewer_id' => $manager->id, 'review_date' => today(), 'decision' => 'approve', 'review_notes' => $notes])` |
| 3. Manager rejects | `reject` | `update(['reviewer_id' => $manager->id, 'review_date' => today(), 'decision' => 'reject', 'review_notes' => $reason])` |
| 4. Owner revises | `revise` | `update(['decision' => 'revise'])` |
| 5. Owner re-submits | `submit_review` | Creates **NEW** document_reviews record (pending) |

This creates a permanent history of all reviews/revisions for each document.

### Example document_reviews History for DOC-2026-0001:

| # | reviewer | review_date | review_notes | decision | created_at |
|---|---|---|---|---|---|
| 1 | NULL | NULL | Dokumen siap untuk review | pending → approve | 01/07/2026 10:00 |
| 2 | Sari W. | 01/07/2026 | Approved, perlu update pada section 3 | approve | 01/07/2026 14:30 |
| 3 | NULL | NULL | Revisi section 3 selesai | pending → reject | 05/07/2026 09:00 |
| 4 | Sari W. | 05/07/2026 | Masih kurang detail pada langkah 5 | reject | 05/07/2026 11:00 |
| 5 | NULL | NULL | Revisi lengkap | pending → approve | 07/07/2026 10:00 |
| 6 | Sari W. | 07/07/2026 | Approved, dokumen siap efektif | approve | 07/07/2026 13:00 |

## 6. Audit Trail

All transitions automatically create:
1. `workflow_histories` record (via `WorkflowService::recordHistory()`)
2. `audit_logs` record with event `workflow.transitioned` (via `AuditService`)
3. `activity_logs` record with event `workflow.transitioned` (via `ActivityService::log()`)

### Audit Events per Transition

| Action Key | Audit Event | Additional Data |
|---|---|---|
| `submit_review` | `document.submitted` | review_notes |
| `approve` | `document.approved` | approver_id, review_notes |
| `make_effective` | `document.effective` | effective_date |
| `obsolete` | `document.obsolete` | reason |
| `reject` | `document.rejected` | reason, review_notes |
| `revise` | `document.revised` | — |

## 7. Terminal Status Rules

- `obsolete` is terminal (per `WorkflowService::isTerminalStatus()`)
- Terminal status sets `workflow_instances.completed_at = now()`
- No further transitions allowed from `obsolete`
- `rejected` is **not** terminal — can transition back to `draft` via `revise`
- Reopen from `obsolete` is NOT supported in Phase 7

## 8. Controller Integration

### Submit Review

```php
public function submitReview(Request $request, ControlledDocument $document): RedirectResponse
{
    $this->authorize('submit_review', $document);
    $actor = Auth::user();

    // Validate mandatory fields
    $request->validate([
        'review_notes' => 'nullable|string|max:2000',
    ]);

    // Check document has file uploaded
    $hasFile = ManagedFile::where('module_name', 'document')
        ->where('reference_id', $document->id)
        ->where('collection', 'document_file')
        ->whereNull('deleted_at')
        ->exists();

    if (!$hasFile) {
        return back()->withErrors(['file' => 'File dokumen wajib diupload sebelum submit review.']);
    }

    // Workflow transition
    $this->workflowService->transition('document', $document->id, 'submit_review', $actor);

    // Create document_reviews record
    DocumentReview::create([
        'document_id' => $document->id,
        'decision' => 'pending',
    ]);

    // Activity & Audit
    ActivityService::log('document', $document->id, 'document.submitted', 'Document submitted for review', $actor);
    AuditService::log('workflow.transitioned', $document, ['status' => 'draft'], ['status' => 'review'], $actor, 'document', $document->id);

    // Notify QHSSE Managers
    $managers = User::role('QHSSE Manager')->get();
    NotificationService::notifyMany($managers, 'document.submitted', [
        'document' => $document,
        'owner' => $document->owner,
    ], $actor, 'document', $document->id, "/documents/{$document->id}");

    return back()->with('success', 'Dokumen berhasil di-submit untuk review.');
}
```

### Approve

```php
public function approve(Request $request, ControlledDocument $document): RedirectResponse
{
    $this->authorize('approve', $document);
    $actor = Auth::user();

    $request->validate([
        'review_notes' => 'nullable|string|max:2000',
    ]);

    $this->workflowService->transition('document', $document->id, 'approve', $actor);

    // Update latest document_reviews record
    $latestReview = $document->reviews()->latest()->first();
    $latestReview->update([
        'reviewer_id' => $actor->id,
        'review_date' => now()->toDateString(),
        'review_notes' => $request->review_notes,
        'decision' => 'approve',
    ]);

    // Set approver_id on document
    $document->update(['approver_id' => $actor->id]);

    // Notify owner
    NotificationService::notify($document->owner, 'document.approved', [
        'document' => $document,
        'approver' => $actor,
    ], $actor, 'document', $document->id, "/documents/{$document->id}");

    return back()->with('success', 'Dokumen berhasil disetujui.');
}
```

### Make Effective

```php
public function makeEffective(Request $request, ControlledDocument $document): RedirectResponse
{
    $this->authorize('make_effective', $document);
    $actor = Auth::user();

    $request->validate([
        'effective_date' => 'nullable|date',
    ]);

    $this->workflowService->transition('document', $document->id, 'make_effective', $actor);

    // Set effective_date
    $document->update(['effective_date' => $request->effective_date ?? now()->toDateString()]);

    // Notify owner + department users
    NotificationService::notify($document->owner, 'document.effective', [...], $actor, ...);

    return back()->with('success', 'Dokumen berhasil diterapkan efektif.');
}
```

### Obsolete (requires reason)

```php
public function obsolete(Request $request, ControlledDocument $document): RedirectResponse
{
    $this->authorize('obsolete', $document);
    $actor = Auth::user();

    $request->validate([
        'reason' => 'required|string|min:10|max:2000',
    ]);

    $this->workflowService->transition('document', $document->id, 'obsolete', $actor, $request->reason);

    // Notify owner + stakeholders
    NotificationService::notifyMany($stakeholders, 'document.obsolete', [
        'document' => $document,
        'obsoletter' => $actor,
        'obsolete_reason' => $request->reason,
    ], $actor, 'document', $document->id, "/documents/{$document->id}");

    return back()->with('success', 'Dokumen telah dinyatakan obsolete.');
}
```

### Reject (requires reason)

```php
public function reject(Request $request, ControlledDocument $document): RedirectResponse
{
    $this->authorize('approve', $document); // same permission as approve
    $actor = Auth::user();

    $request->validate([
        'reason' => 'required|string|min:10|max:2000',
    ]);

    $this->workflowService->transition('document', $document->id, 'reject', $actor, $request->reason);

    // Update latest document_reviews record
    $latestReview = $document->reviews()->latest()->first();
    $latestReview->update([
        'reviewer_id' => $actor->id,
        'review_date' => now()->toDateString(),
        'review_notes' => $request->reason,
        'decision' => 'reject',
    ]);

    // Notify owner
    NotificationService::notify($document->owner, 'document.rejected', [
        'document' => $document,
        'rejecter' => $actor,
        'reject_reason' => $request->reason,
    ], $actor, 'document', $document->id, "/documents/{$document->id}");

    return back()->with('success', 'Dokumen telah ditolak.');
}
```

### Revise

```php
public function revise(ControlledDocument $document): RedirectResponse
{
    $this->authorize('update', $document);
    $actor = Auth::user();

    $this->workflowService->transition('document', $document->id, 'revise', $actor);

    // Update latest document_reviews record
    $latestReview = $document->reviews()->latest()->first();
    $latestReview->update(['decision' => 'revise']);

    ActivityService::log('document', $document->id, 'document.revised', 'Document sent back to draft for revision', $actor);

    return back()->with('success', 'Dokumen dikembalikan ke draft untuk revisi.');
}
```

### Invalid Transition Handling

```php
try {
    $this->workflowService->transition('document', $document->id, $actionKey, $actor, $reason);
} catch (RuntimeException $e) {
    return back()->withErrors(['workflow' => $e->getMessage()]);
}
```

## 9. Available Transitions by Status

The Show page receives `availableTransitions` in Inertia props. These are computed based on the document's current status and the user's permissions.

| Current Status | Available Transitions | Permission Required |
|---|---|---|
| `draft` | submit_review | `document.control.submit_review` |
| `review` | approve, reject | `document.control.approve` |
| `approved` | make_effective | `document.control.make_effective` |
| `effective` | obsolete | `document.control.obsolete` |
| `obsolete` | (none — terminal) | — |
| `rejected` | revise | `document.control.update` |

### Controller method to compute available transitions:

```php
protected function getAvailableTransitions(ControlledDocument $document, User $user): array
{
    $transitions = [];

    switch ($document->status) {
        case 'draft':
            if ($user->can('document.control.submit_review')) {
                $transitions[] = [
                    'action_key' => 'submit_review',
                    'action_label' => 'Submit for Review',
                    'requires_reason' => false,
                ];
            }
            break;

        case 'review':
            if ($user->can('document.control.approve')) {
                $transitions[] = [
                    'action_key' => 'approve',
                    'action_label' => 'Approve',
                    'requires_reason' => false,
                ];
                $transitions[] = [
                    'action_key' => 'reject',
                    'action_label' => 'Reject',
                    'requires_reason' => true,
                ];
            }
            break;

        case 'approved':
            if ($user->can('document.control.make_effective')) {
                $transitions[] = [
                    'action_key' => 'make_effective',
                    'action_label' => 'Make Effective',
                    'requires_reason' => false,
                ];
            }
            break;

        case 'effective':
            if ($user->can('document.control.obsolete')) {
                $transitions[] = [
                    'action_key' => 'obsolete',
                    'action_label' => 'Obsolete',
                    'requires_reason' => true,
                ];
            }
            break;

        case 'rejected':
            if ($user->can('document.control.update')) {
                $transitions[] = [
                    'action_key' => 'revise',
                    'action_label' => 'Revise',
                    'requires_reason' => false,
                ];
            }
            break;
    }

    return $transitions;
}
```

## 10. Expiry Reminder Workflow

A scheduled command runs daily to check for documents approaching their review or expiry date.

### Schedule

```php
// In app/Console/Kernel.php or routes/console.php
Schedule::command('documents:check-expiry')->dailyAt('08:00');
```

### Logic

```
For each document where status = 'effective':
    If review_date is within 30/7/1 days:
        Send notification to owner + QHSSE Manager
    If expiry_date is within 30/7/1 days:
        Send notification to owner + QHSSE Manager
    If review_date or expiry_date has passed:
        Send urgent notification to owner + QHSSE Manager
```

### Notification Details

| Trigger | Notification Type | Recipients | Message |
|---|---|---|---|
| review_date in 30 days | `document.expiry_reminder` | Owner, QHSSE Manager | "Dokumen {number} akan jatuh tempo review dalam 30 hari" |
| review_date in 7 days | `document.expiry_reminder` | Owner, QHSSE Manager | "Dokumen {number} akan jatuh tempo review dalam 7 hari" |
| review_date in 1 day | `document.expiry_reminder` | Owner, QHSSE Manager | "Dokumen {number} akan jatuh tempo review besok" |
| expiry_date in 30/7/1 days | `document.expiry_reminder` | Owner, QHSSE Manager | "Dokumen {number} akan kadaluarsa dalam X hari" |

Notifications are only sent for documents with `status = 'effective'`. Obsolete documents are skipped.
