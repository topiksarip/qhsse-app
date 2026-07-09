<?php

namespace App\Models\Core\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'module_name', 'reference_id', 'current_status',
        'started_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class);
    }
}
