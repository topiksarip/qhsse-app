<?php

namespace App\Models\Modules\Contractor;

use App\Models\User;
use App\Models\Modules\Contractor\Contractor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractorEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contractor_evaluations';

    protected $fillable = [
        'contractor_id',
        'evaluation_date',
        'evaluator_id',
        'criteria',
        'total_score',
        'result',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'criteria' => 'array',
        'total_score' => 'integer',
    ];

    // Relationships
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Result derivation helper (kept consistent with WORKFLOW.md §4)
    public static function deriveResult(int $totalScore): string
    {
        return match (true) {
            $totalScore >= 80 => 'pass',
            $totalScore >= 60 => 'conditional',
            default => 'fail',
        };
    }
}
