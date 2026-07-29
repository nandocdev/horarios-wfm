<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\OperationsModule\Models\QueueSkill;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmployeeSkill;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EvaluateSkillCoverageAction
{
    /**
     * @return Collection<int, array{queue_id: int, queue_name: string, skill_id: int, skill_name: string, required_count: int, available_count: int, coverage: float, gap: int}>
     */
    public function execute(?int $queueId = null): Collection
    {
        $query = QueueSkill::with(['queue', 'skill']);

        if ($queueId !== null) {
            $query->where('queue_id', $queueId);
        }

        $queueSkills = $query->get();

        if ($queueSkills->isEmpty()) {
            return collect();
        }

        $activeEmployees = Employee::where('is_active', true)->pluck('id');

        $results = collect();

        foreach ($queueSkills->groupBy('queue_id') as $qId => $skills) {
            $queue = $skills->first()->queue;

            foreach ($skills as $qs) {
                $requiredCount = $this->countRequiredForQueue($qId, $qs->skill_id, $activeEmployees);
                $availableCount = $this->countAvailableForSkill($qs->skill_id, $qs->minimum_level, $activeEmployees);

                $results->push([
                    'queue_id' => $qId,
                    'queue_name' => $queue->name,
                    'skill_id' => $qs->skill_id,
                    'skill_name' => $qs->skill->name,
                    'minimum_level' => $qs->minimum_level,
                    'is_required' => $qs->is_required,
                    'required_count' => $requiredCount,
                    'available_count' => $availableCount,
                    'coverage' => $requiredCount > 0
                        ? round(($availableCount / $requiredCount) * 100, 2)
                        : 100.0,
                    'gap' => max(0, $requiredCount - $availableCount),
                ]);
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{queue_id: int, queue_name: string, total_skills: int, skills_covered: int, skills_with_gap: int, overall_coverage: float}>
     */
    public function executePerQueue(?int $queueId = null): array
    {
        $details = $this->execute($queueId);

        return $details->groupBy('queue_id')->map(function ($items, $qId) {
            $first = $items->first();
            $total = $items->count();
            $covered = $items->where('gap', 0)->count();

            return [
                'queue_id' => $qId,
                'queue_name' => $first['queue_name'],
                'total_skills' => $total,
                'skills_covered' => $covered,
                'skills_with_gap' => $total - $covered,
                'overall_coverage' => $total > 0
                    ? round(($covered / $total) * 100, 2)
                    : 100.0,
            ];
        })->values()->toArray();
    }

    private function countRequiredForQueue(int $queueId, int $skillId, Collection $activeEmployees): int
    {
        return DB::table('weekly_schedule_assignments')
            ->join('weekly_schedules', 'weekly_schedule_assignments.weekly_schedule_id', '=', 'weekly_schedules.id')
            ->where('weekly_schedules.status', 'published')
            ->whereIn('weekly_schedule_assignments.employee_id', $activeEmployees)
            ->whereExists(function ($q) use ($skillId) {
                $q->select(DB::raw(1))
                    ->from('employee_skills')
                    ->whereColumn('employee_skills.employee_id', 'weekly_schedule_assignments.employee_id')
                    ->where('employee_skills.skill_id', $skillId)
                    ->where('employee_skills.is_active', true);
            })
            ->distinct('weekly_schedule_assignments.employee_id')
            ->count('weekly_schedule_assignments.employee_id');
    }

    private function countAvailableForSkill(int $skillId, int $minimumLevel, Collection $activeEmployees): int
    {
        return EmployeeSkill::where('skill_id', $skillId)
            ->whereIn('employee_id', $activeEmployees)
            ->where('level', '>=', $minimumLevel)
            ->where('is_active', true)
            ->count();
    }
}
