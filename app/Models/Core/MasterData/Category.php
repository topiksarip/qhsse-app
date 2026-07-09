<?php
namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Category extends Model { /** @use HasFactory<\Database\Factories\Core\MasterData\CategoryFactory> */ use HasFactory, Auditable; protected $fillable = ['parent_id','module','code','name','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(self::class, 'parent_id'); } }
