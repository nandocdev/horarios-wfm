<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSkill extends Model
{
    protected $table = 'employee_skills';

    protected $fillable = [
        'employee_id',
        'skill_id',
        'level',
        'years_experience',
        'is_primary',
        'certified_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_primary' => 'boolean',
            'certified_at' => 'date',
            'expires_at' => 'date',
            'is_active' => 'boolean',
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

    public function history(): HasMany
    {
        return $this->hasMany(SkillHistory::class, 'employee_skill_id');
    }
}
