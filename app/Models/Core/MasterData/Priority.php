<?php
namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Priority extends Model { /** @use HasFactory<\Database\Factories\Core\MasterData\PriorityFactory> */ use HasFactory, Auditable; protected $fillable = ['code','name','sla_days','color','is_active'];
    protected function casts(): array { return ['sla_days'=>'integer','is_active'=>'boolean']; } }
