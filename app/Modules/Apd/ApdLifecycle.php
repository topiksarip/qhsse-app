<?php

namespace App\Modules\Apd;

use App\Core\Activity\ActivityService;
use App\Core\Workflow\WorkflowService;
use App\Models\Modules\Apd\ApdIssuance;
use App\Models\Modules\Apd\ApdItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApdLifecycle
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly ActivityService $activity,
    ) {}

    /**
     * Create issuance (draft) and optionally move to requested or directly issue.
     */
    public function create(
        array $data,
        User $actor,
        bool $asRequest = false,
    ): ApdIssuance {
        return DB::transaction(function () use ($data, $actor, $asRequest): ApdIssuance {
            $generated = app(\App\Core\Numbering\NumberingService::class)->generate(
                moduleName: 'apd_issue',
                actor: $actor,
                referenceType: ApdIssuance::class,
            );

            $issuance = ApdIssuance::create([
                'issue_number' => $generated->number,
                'apd_item_id' => $data['apd_item_id'],
                'quantity' => $data['quantity'] ?? 1,
                'holder_type' => $data['holder_type'],
                'holder_id' => $data['holder_id'],
                'condition_out' => $data['condition_out'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'expected_return_date' => $data['expected_return_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $generated->update(['reference_id' => $issuance->id]);

            $this->workflow->start('apd', $issuance->id, $actor);

            if ($asRequest) {
                $this->applyTransition($issuance, $actor, 'request', 'requested', [
                    'requested_by' => $actor->id,
                    'requested_date' => $data['requested_date'] ?? now()->toDateString(),
                ]);
            } elseif ($actor->can('apd.issue')) {
                $this->issue($issuance, $actor, $data);
            }

            return $issuance->refresh();
        });
    }

    public function request(ApdIssuance $issuance, User $actor): void
    {
        $this->applyTransition($issuance, $actor, 'request', 'requested', [
            'requested_by' => $actor->id,
            'requested_date' => now()->toDateString(),
        ]);
    }

    public function approve(ApdIssuance $issuance, User $actor): void
    {
        $this->applyTransition($issuance, $actor, 'approve', 'approved', [
            'approved_by' => $actor->id,
        ]);
    }

    public function issue(ApdIssuance $issuance, User $actor, array $extra = []): void
    {
        $this->applyTransition($issuance, $actor, 'issue', 'issued', array_merge([
            'issued_by' => $actor->id,
            'issue_date' => $issuance->issue_date ?? now()->toDateString(),
        ], $extra));

        // Stock effect: item leaves stock, assigned to holder
        $item = $issuance->item;
        $item->update([
            'status' => 'issued',
            'holder_type' => $issuance->holder_type,
            'holder_id' => $issuance->holder_id,
            'updated_by' => $actor->id,
        ]);

        $this->activity->log('apd', $item->id, 'apd.item.issued', "Item {$item->item_number} diissue ke {$issuance->holder_label}", $actor);
    }

    public function return(ApdIssuance $issuance, User $actor, ?string $conditionIn = null, ?string $returnedDate = null): void
    {
        $this->applyTransition($issuance, $actor, 'return', 'returned', [
            'returned_by' => $actor->id,
            'returned_date' => $returnedDate ?? now()->toDateString(),
            'condition_in' => $conditionIn,
        ]);

        $item = $issuance->item;
        $item->update([
            'status' => 'in_stock',
            'holder_type' => null,
            'holder_id' => null,
            'condition' => $conditionIn ?? $item->condition,
            'updated_by' => $actor->id,
        ]);

        $this->activity->log('apd', $item->id, 'apd.item.returned', "Item {$item->item_number} dikembalikan ke stok", $actor);
    }

    public function dispose(ApdIssuance $issuance, User $actor, ?string $reason = null): void
    {
        $this->applyTransition($issuance, $actor, 'dispose', 'disposed', [
            'returned_by' => $actor->id,
            'returned_date' => now()->toDateString(),
        ], $reason);

        $item = $issuance->item;
        $item->update([
            'status' => 'disposed',
            'holder_type' => null,
            'holder_id' => null,
            'updated_by' => $actor->id,
        ]);

        $this->activity->log('apd', $item->id, 'apd.item.disposed', "Item {$item->item_number} dimusnahkan", $actor);
    }

    public function reject(ApdIssuance $issuance, User $actor, ?string $reason = null): void
    {
        $this->applyTransition($issuance, $actor, 'reject', 'rejected', [], $reason);
    }

    private function applyTransition(ApdIssuance $issuance, User $actor, string $action, string $status, array $fields = [], ?string $reason = null): void
    {
        $this->workflow->transition('apd', $issuance->id, $action, $actor, $reason);
        $issuance->update(array_merge($fields, [
            'status' => $status,
            'updated_by' => $actor->id,
        ]));
    }
}
