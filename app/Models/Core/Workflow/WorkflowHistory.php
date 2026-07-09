<?php

namespace App\Models\Core\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowHistory extends Model
{
    protected $fillable = [
        'workflow_instance_id', 'module_name', 'reference_id', 'from_status',
        'to_status', 'action_key', 'action_label', 'reason', 'actor_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
