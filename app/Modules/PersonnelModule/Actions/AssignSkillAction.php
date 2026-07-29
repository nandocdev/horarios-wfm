<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Models\EmployeeSkill;
use App\Modules\PersonnelModule\Models\Skill;
use App\Modules\PersonnelModule\Models\SkillHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AssignSkillAction
{
    public function execute(
        int $employeeId,
        int $skillId,
        int $level,
        ?int $changedBy = null,
        ?string $reason = null,
        ?float $yearsExperience = null,
        bool $isPrimary = false,
        ?string $certifiedAt = null,
        ?string $expiresAt = null,
    ): EmployeeSkill {
        $skill = Skill::findOrFail($skillId);
        $level = max(1, min(5, $level));

        return DB::transaction(function () use (
            $employeeId, $skill, $level, $changedBy, $reason,
            $yearsExperience, $isPrimary, $certifiedAt, $expiresAt,
        ) {
            $existing = EmployeeSkill::where('employee_id', $employeeId)
                ->where('skill_id', $skill->id)
                ->first();

            $oldLevel = $existing?->level;

            $employeeSkill = EmployeeSkill::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'skill_id' => $skill->id,
                ],
                [
                    'level' => $level,
                    'years_experience' => $yearsExperience,
                    'is_primary' => $isPrimary,
                    'certified_at' => $certifiedAt,
                    'expires_at' => $expiresAt,
                    'is_active' => true,
                ],
            );

            if ($oldLevel !== $level) {
                SkillHistory::create([
                    'employee_skill_id' => $employeeSkill->id,
                    'employee_id' => $employeeId,
                    'skill_id' => $skill->id,
                    'old_level' => $oldLevel,
                    'new_level' => $level,
                    'changed_by' => $changedBy,
                    'changed_at' => CarbonImmutable::now(),
                    'reason' => $reason,
                ]);
            }

            return $employeeSkill->load(['skill', 'employee']);
        });
    }

    public function remove(int $employeeId, int $skillId): void
    {
        DB::transaction(function () use ($employeeId, $skillId) {
            $employeeSkill = EmployeeSkill::where('employee_id', $employeeId)
                ->where('skill_id', $skillId)
                ->firstOrFail();

            $employeeSkill->update(['is_active' => false]);

            SkillHistory::create([
                'employee_skill_id' => $employeeSkill->id,
                'employee_id' => $employeeId,
                'skill_id' => $skillId,
                'old_level' => $employeeSkill->level,
                'new_level' => 0,
                'changed_by' => null,
                'changed_at' => CarbonImmutable::now(),
                'reason' => 'Skill removed',
            ]);
        });
    }
}
