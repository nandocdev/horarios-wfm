<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\PersonnelModule\Actions\EvaluateSkillCoverageAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmployeeSkill;
use App\Modules\PersonnelModule\Models\Skill;
use Livewire\Component;

class SkillsHeatmap extends Component
{
    public ?string $queueFilter = null;

    public string $view = 'coverage';

    public function render()
    {
        $queues = CallQueue::active()->orderBy('name')->get(['id', 'name']);

        $coverageDetails = collect();
        $queueSummary = [];
        $employeeCount = Employee::where('is_active', true)->count();
        $skillCount = Skill::count();

        if ($this->view === 'coverage') {
            $action = app(EvaluateSkillCoverageAction::class);
            $queueId = $this->queueFilter ? (int) $this->queueFilter : null;

            $coverageDetails = $action->execute($queueId);
            $queueSummary = $action->executePerQueue($queueId);
        }

        $allSkills = Skill::orderBy('name')->get(['id', 'name']);
        $skillDistribution = EmployeeSkill::where('is_active', true)
            ->selectRaw('skill_id, COUNT(DISTINCT employee_id) as count, AVG(level) as avg_level')
            ->groupBy('skill_id')
            ->get()
            ->keyBy('skill_id');

        return view('operations::livewire.skills-heatmap', [
            'queues' => $queues,
            'coverageDetails' => $coverageDetails,
            'queueSummary' => $queueSummary,
            'allSkills' => $allSkills,
            'skillDistribution' => $skillDistribution,
            'employeeCount' => $employeeCount,
            'skillCount' => $skillCount,
        ])->layout('layouts.app', ['title' => 'Cobertura de Skills']);
    }
}
