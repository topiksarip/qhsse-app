<?php

namespace App\Models\Modules\Training;

use App\Models\Core\Users\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Training\TrainingProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'duration_hours',
        'is_certification',
        'validity_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_hours' => 'integer',
            'is_certification' => 'boolean',
            'validity_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<TrainingRecord> */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Scope to only active programs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get all valid categories
     */
    public static function getCategories(): array
    {
        return [
            'safety' => 'Keselamatan',
            'technical' => 'Teknis',
            'compliance' => 'Kepatuhan',
            'soft_skill' => 'Soft Skill',
            'environment' => 'Lingkungan',
            'security' => 'Keamanan',
            'quality' => 'Kualitas',
            'first_aid' => 'P3K',
        ];
    }
}
