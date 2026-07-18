<?php

namespace App\Models\Modules\Permit;

use App\Models\Core\Users\Employee;
use App\Models\Modules\Permit\Permit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot row linking a Permit to an Employee (pekerja) involved in the work.
 */
class PermitWorker extends Model
{
    use HasFactory;

    protected $fillable = ['permit_id', 'employee_id', 'role'];

    protected $casts = [
        'role' => 'array',
    ];

    /** @return BelongsTo<Permit, PermitWorker> */
    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }

    /** @return BelongsTo<Employee, PermitWorker> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
