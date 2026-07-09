<?php

namespace App\Models\Core\Numbering;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedNumber extends Model
{
    protected $fillable = [
        'module_name', 'number', 'site_code', 'year', 'sequence',
        'reference_type', 'reference_id', 'generated_by', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'sequence' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
