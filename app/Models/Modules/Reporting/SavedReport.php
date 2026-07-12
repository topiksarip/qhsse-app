<?php

namespace App\Models\Modules\Reporting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'template_id',
        'status',
        'parameters',
        'format',
        'file_path',
        'file_size',
        'generated_by',
        'generated_at',
        'completed_at',
        'failed_at',
        'error_message',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'file_size' => 'integer',
        'generated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeGeneratedBy($query, int $userId)
    {
        return $query->where('generated_by', $userId);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'processing' => 'Memproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'blue',
            'processing' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getFormatLabelAttribute(): string
    {
        return match ($this->format) {
            'csv' => 'CSV',
            'pdf' => 'PDF',
            'excel' => 'Excel',
            default => strtoupper($this->format),
        };
    }

    public function getFileSizeHumanAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getDateRangeAttribute(): string
    {
        $params = $this->parameters ?? [];
        $from = $params['date_from'] ?? null;
        $to = $params['date_to'] ?? null;

        if (!$from || !$to) {
            return '-';
        }

        return date('d/m/Y', strtotime($from)) . ' - ' . date('d/m/Y', strtotime($to));
    }

    public function getGenerationDurationAttribute(): ?int
    {
        if (!$this->generated_at || !$this->completed_at) {
            return null;
        }

        return $this->generated_at->diffInSeconds($this->completed_at);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canDownload(): bool
    {
        return $this->isCompleted() && $this->file_path && file_exists(storage_path('app/' . $this->file_path));
    }

    public function canRegenerate(): bool
    {
        // Can regenerate if completed or failed
        return in_array($this->status, ['completed', 'failed']);
    }

    public function canDelete(): bool
    {
        // Can delete if not currently processing
        return $this->status !== 'processing';
    }

    public function markAsPending(): void
    {
        $this->update([
            'status' => 'pending',
            'generated_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'generated_at' => now(),
        ]);
    }

    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'completed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'file_path' => null,
            'file_size' => null,
        ]);
    }

    public function getDownloadFileName(): string
    {
        $timestamp = $this->completed_at?->format('Ymd_His') ?? date('Ymd_His');
        $extension = match ($this->format) {
            'csv' => 'csv',
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            default => 'txt',
        };

        return str_replace(' ', '_', $this->name) . '_' . $timestamp . '.' . $extension;
    }
}
