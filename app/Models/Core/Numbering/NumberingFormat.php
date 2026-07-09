<?php

namespace App\Models\Core\Numbering;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberingFormat extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'module_name', 'prefix', 'padding', 'separator', 'reset_frequency',
        'include_year', 'include_site_code', 'sample', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'padding' => 'integer',
            'include_year' => 'boolean',
            'include_site_code' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
