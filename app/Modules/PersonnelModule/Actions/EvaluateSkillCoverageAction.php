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

        // Query 1: Get required counts for all queue/skill combinations in one query
        $requiredCounts = $this->getRequiredCounts($queueSkills, $activeEmployees);

        // Query 2: Get available counts for all skills in one query
        $skillIds = $queueSkills->pluck('skill_id')->unique()->toArray();
        $availableCounts = $this->getAvailableCounts($skillIds, $activeEmployees);

        $results = collect();

        foreach ($queueSkills->groupBy('queue_id') as $qId => $skills) {
            $queue = $skills->first()->queue;

            foreach ($skills as $qs) {
                $requiredKey = "{$qId}_{$qs->skill_id}";
                $requiredCount = $requiredCounts[$requiredKey] ?? 0;
                $availableCount = $availableCounts[$qs->skill_id][$qs->minimum_level] ?? 0;

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

    /**
     * Get required counts for all queue/skill combinations in a single aggregated query.
     *
     * @return array<string, int> Key format: "queueId_skillId" => count
     */
    private function getRequiredCounts(Collection $queueSkills, Collection $activeEmployees): array
    {
        $queueSkillPairs = $queueSkills->map(fn ($qs) => "{$qs->queue_id}_{$qs->skill_id}")->toArray();

        $results = DB::table('weekly_schedule_assignments as wsa')
            ->join('weekly_schedules as ws', 'wsa.weekly_schedule_id', '=', 'ws.id')
            ->join('employee_skills as es', function ($join) {
                $join->on('es.employee_id', '=', 'wsa.employee_id')
                    ->where('es.is_active', '=', true);
            })
            ->where('ws.status', 'published')
            ->whereIn('wsa.employee_id', $activeEmployees)
            ->whereIn('es.skill_id', $queueSkills->pluck('skill_id')->unique()->toArray())
            ->select(
                DB::raw('wsa.employee_id'),
                DB::raw('es.skill_id'),
                DB::raw('COUNT(DISTINCT wsa.employee_id) as required_count')
            )
            ->groupBy('es.skill_id')
            ->get()
            ->keyBy(fn ($row) => "{$row->queue_id}_{$row->skill_id}");

        $output = [];
        foreach ($queueSkills as $qs) {
            $key = "{$qs->queue_id}_{$qs->skill_id}";
            $output[$key] = $results[$key]->required_count ?? 0;
        }

        return $output;
    }

    /**
     * Get available counts for all skill/minimum_level combinations in a single query.
     *
     * @return array<int, array<int, int>> [$skillId][$minimumLevel] => count
     */
    private function getAvailableCounts(array $skillIds, Collection $activeEmployees): array
    {
        $results = EmployeeSkill::whereIn('skill_id', $skillIds)
            ->whereIn('employee_id', $activeEmployees)
            ->where('is_active', true)
            ->select('skill_id', 'level', DB::raw('COUNT(*) as count'))
            ->groupBy('skill_id', 'level')
            ->get()
            ->groupBy('skill_id');

        $output = [];
        foreach ($results as $skillId => $levels) {
            $output[$skillId] = [];
            foreach ($levels as $level) {
                $output[$skillId][$level->level] = $level->count;
            }
        }

        // Ensure all skill IDs have an entry even if no employees have that skill
        foreach ($skillIds as $skillId) {
            $output[$skillId] = $output[$skillId] ?? [];
        }

        return $output;
    }
}
