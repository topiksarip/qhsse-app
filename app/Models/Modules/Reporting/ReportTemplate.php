<?php

namespace App\Models\Modules\Reporting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'config',
        'is_active',
        'is_predefined',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'is_predefined' => 'boolean',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePredefined($query)
    {
        return $query->where('is_predefined', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_predefined', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'incident_summary' => 'Ringkasan Insiden',
            'capa_summary' => 'Ringkasan CAPA',
            'inspection_summary' => 'Ringkasan Inspection',
            'audit_summary' => 'Ringkasan Audit',
            'training_compliance' => 'Kepatuhan Training',
            'monthly_qhsse' => 'Laporan Bulanan QHSSE',
            'annual_qhsse' => 'Laporan Tahunan QHSSE',
            'custom' => 'Laporan Custom',
            default => $this->type,
        };
    }

    public function getReportCountAttribute(): int
    {
        return $this->savedReports()->count();
    }

    public function getCompletedReportCountAttribute(): int
    {
        return $this->savedReports()->where('status', 'completed')->count();
    }

    // Helper methods
    public function canBeEdited(): bool
    {
        // Pre-defined templates: only description and is_active can be edited
        // Custom templates: all fields can be edited
        return !$this->is_predefined;
    }

    public function canBeDeleted(): bool
    {
        // Pre-defined templates cannot be deleted
        // Custom templates can be deleted only if no reports exist
        if ($this->is_predefined) {
            return false;
        }

        return $this->savedReports()->count() === 0;
    }

    public function getDefaultParameters(): array
    {
        $config = $this->config ?? [];
        return $config['default_parameters'] ?? [
            'date_range' => 'last_month',
            'site_id' => null,
            'department_id' => null,
            'format' => 'pdf',
            'include_charts' => true,
        ];
    }

    public function getSections(): array
    {
        $config = $this->config ?? [];
        return $config['sections'] ?? [];
    }

    public function getDataSources(): array
    {
        // Extract unique data sources from sections config
        $sections = $this->getSections();
        $sources = [];

        foreach ($sections as $section) {
            if (isset($section['data_source'])) {
                $sources[] = $section['data_source'];
            }
        }

        return array_unique($sources);
    }

    public function isMultiModule(): bool
    {
        // Monthly and Annual QHSSE reports aggregate from multiple modules
        return in_array($this->type, ['monthly_qhsse', 'annual_qhsse']);
    }

    public function getEstimatedGenerationTime(): int
    {
        // Estimate generation time in seconds based on template type
        return match ($this->type) {
            'monthly_qhsse', 'annual_qhsse' => 300, // 5 minutes
            'incident_summary', 'capa_summary', 'inspection_summary', 'audit_summary' => 120, // 2 minutes
            'training_compliance' => 180, // 3 minutes
            'custom' => 240, // 4 minutes
            default => 120,
        };
    }

    public function getSupportedFormats(): array
    {
        // All templates support all formats
        return ['csv', 'pdf', 'excel'];
    }

    public function supportsCharts(): bool
    {
        // CSV doesn't support charts, PDF and Excel do
        return true; // Chart support depends on chosen format
    }

    // Static helper for type validation
    public static function getValidTypes(): array
    {
        return [
            'incident_summary',
            'capa_summary',
            'inspection_summary',
            'audit_summary',
            'training_compliance',
            'monthly_qhsse',
            'annual_qhsse',
            'custom',
        ];
    }

    public static function getPredefinedTypes(): array
    {
        return [
            'incident_summary',
            'capa_summary',
            'inspection_summary',
            'audit_summary',
            'training_compliance',
            'monthly_qhsse',
            'annual_qhsse',
        ];
    }
}
