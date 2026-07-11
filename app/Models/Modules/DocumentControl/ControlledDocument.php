<?php

namespace App\Models\Modules\DocumentControl;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\MasterData\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlledDocument extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory;

    protected $table = 'controlled_documents';

    protected $fillable = [
        'document_number', 'title', 'type', 'version', 'revision_notes',
        'effective_date', 'review_date', 'expiry_date',
        'department_id', 'owner_id', 'approver_id',
        'status', 'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'review_date' => 'date',
            'expiry_date' => 'date',
            'is_confidential' => 'boolean',
        ];
    }

    /** @return BelongsTo<Department, self> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, self> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, self> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** @return HasMany<DocumentReview> */
    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class, 'document_id');
    }

    public function auditContext(): array
    {
        return ['module_name' => 'document', 'reference_id' => $this->getKey()];
    }

    public function isExpiringSoon(): bool
    {
        return $this->review_date !== null
            && $this->review_date->greaterThanOrEqualTo(today())
            && $this->review_date->lessThanOrEqualTo(today()->addDays(30));
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }
}
