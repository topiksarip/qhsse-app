<?php

namespace App\Models\Core\Workflow;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'workflow_definition_id', 'from_status', 'to_status', 'action_key',
        'action_label', 'requires_reason', 'required_permission', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_reason' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }
}
