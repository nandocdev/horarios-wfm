<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillHistory extends Model
{
    protected $table = 'skill_history';

    protected $fillable = [
        'employee_skill_id',
        'employee_id',
        'skill_id',
        'old_level',
        'new_level',
        'changed_by',
        'changed_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function employeeSkill(): BelongsTo
    {
        return $this->belongsTo(EmployeeSkill::class, 'employee_skill_id');
    }
}
