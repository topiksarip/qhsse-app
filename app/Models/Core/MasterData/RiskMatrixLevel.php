<?php
namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class RiskMatrixLevel extends Model { /** @use HasFactory<\Database\Factories\Core\MasterData\RiskMatrixLevelFactory> */ use HasFactory, Auditable; protected $fillable = ['likelihood','consequence','score','level','color','description','is_active'];
    protected function casts(): array { return ['likelihood'=>'integer','consequence'=>'integer','score'=>'integer','is_active'=>'boolean']; } }
