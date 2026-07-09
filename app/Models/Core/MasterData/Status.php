<?php
namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Status extends Model { /** @use HasFactory<\Database\Factories\Core\MasterData\StatusFactory> */ use HasFactory, Auditable; protected $fillable = ['module','code','name','sequence','is_terminal','is_active'];
    protected function casts(): array { return ['sequence'=>'integer','is_terminal'=>'boolean','is_active'=>'boolean']; } }
