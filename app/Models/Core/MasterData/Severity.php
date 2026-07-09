<?php
namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Severity extends Model { /** @use HasFactory<\Database\Factories\Core\MasterData\SeverityFactory> */ use HasFactory, Auditable; protected $fillable = ['code','name','level','color','description','is_active'];
    protected function casts(): array { return ['level'=>'integer','is_active'=>'boolean']; } }
