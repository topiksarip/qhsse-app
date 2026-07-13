<?php

namespace App\Models\Core\Users;

use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\Core\Users\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'site_id',
        'department_id',
        'position_id',
        'employee_no',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Company, Employee> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Site, Employee> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<Department, Employee> */
    public function departmentMaster(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return BelongsTo<Department, Employee> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return BelongsTo<Position, Employee> */
    public function positionMaster(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /** @return BelongsTo<Position, Employee> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /** @return HasOne<User> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
