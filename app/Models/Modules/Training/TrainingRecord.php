<?php

namespace App\Models\Modules\Training;

use App\Models\Core\Files\ManagedFile;
use App\Models\Core\Users\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRecord extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Training\TrainingRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'training_number',
        'employee_id',
        'training_program_id',
        'provider',
        'start_date',
        'end_date',
        'status',
        'score',
        'result',
        'certificate_number',
        'certificate_file_id',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'expiry_date' => 'date',
            'score' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Employee, TrainingRecord> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<TrainingProgram, TrainingRecord> */
    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /** @return BelongsTo<ManagedFile, TrainingRecord> */
    public function certificateFile(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'certificate_file_id');
    }

    /**
     * Check if record is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'completed'
            && $this->expiry_date !== null
            && $this->expiry_date->isPast();
    }

    /**
     * Check if expiry is near (within 30 days)
     */
    public function isExpiryNear(): bool
    {
        return $this->status === 'completed'
            && $this->expiry_date !== null
            && $this->expiry_date->isFuture()
            && $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get expired records
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'completed')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope to get expiring soon (within days)
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'completed')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Get all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            'scheduled' => 'Terjadwal',
            'in_progress' => 'Sedang Berlangsung',
            'completed' => 'Selesai',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Get all valid results
     */
    public static function getResults(): array
    {
        return [
            'pass' => 'Lulus',
            'fail' => 'Tidak Lulus',
            'pending' => 'Menunggu',
        ];
    }
}
