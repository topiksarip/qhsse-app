<?php

namespace App\Models\Core\Numbering;

use Illuminate\Database\Eloquent\Model;

class NumberingCounter extends Model
{
    protected $fillable = ['module_name', 'site_code', 'year', 'current_number'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_number' => 'integer',
        ];
    }
}
