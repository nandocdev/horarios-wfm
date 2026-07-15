<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Actions\StoreEvaluationAction;
use App\Modules\QualityModule\DTOs\CreateEvaluationDTO;
use App\Modules\QualityModule\Livewire\Forms\EvaluationFormData;
use App\Modules\QualityModule\Models\Queue;
use App\Modules\QualityModule\Models\RedFlagCriteria;
use App\Shared\Contracts\Quality\CriteriaRepositoryInterface;
use Illuminate\Support\Collection;
use Livewire\Component;

class EvaluationForm extends Component
{
    public EvaluationFormData $form;

    public Collection $criterios;

    public Employee $employee;

    public function mount(Employee $employee, CriteriaRepositoryInterface $repo): void
    {
        $this->employee = $employee;
        $this->form->employee_id = $employee->id;
        $this->form->queue_id = request('queue', '');
        $this->criterios = collect();

        if ($this->form->queue_id) {
            $this->loadCriterios($repo);
        }
    }

    public function updatedFormQueueId(CriteriaRepositoryInterface $repo): void
    {
        $this->form->scores = [];
        $this->form->red_flags = [];
        $this->loadCriterios($repo);
    }

    private function loadCriterios(CriteriaRepositoryInterface $repo): void
    {
        $this->criterios = $this->form->queue_id
            ? $repo->getActiveByQueue($this->form->queue_id)
            : collect();

        foreach ($this->criterios as $criterio) {
            $this->form->scores[] = [
                'criteria_version_id' => $criterio->id,
                'cumple' => false,
            ];
        }
    }

    public function submit(StoreEvaluationAction $action): void
    {
        $this->form->validate();

        $scoresDTO = [];
        foreach ($this->form->scores as $i => $score) {
            $criteriaVersion = $this->criterios[$i] ?? null;
            $scoresDTO[] = [
                'criteria_version_id' => $score['criteria_version_id'],
                'puntaje' => ($score['cumple'] ?? false) ? ($criteriaVersion?->puntaje ?? 0) : 0,
            ];
        }

        $redFlags = [];
        foreach ($this->form->red_flags as $rfId => $selected) {
            if ($selected) {
                $redFlags[] = ['red_flag_criteria_id' => $rfId];
            }
        }

        $dto = new CreateEvaluationDTO(
            queue_id: $this->form->queue_id,
            employee_id: $this->form->employee_id,
            evaluator_id: (int) auth()->id(),
            clip_id: $this->form->clip_id,
            dtcall: $this->form->dtcall,
            tmcall: $this->form->tmcall,
            callobs: $this->form->callobs,
            scores: $scoresDTO,
            red_flags: $redFlags,
        );

        $evaluation = $action->execute($dto);

        session()->flash('message', 'Evaluación creada correctamente.');

        $this->redirectRoute('quality.evaluations.index');
    }

    public function render()
    {
        return view('quality::livewire.evaluation-form', [
            'queues' => Queue::orderBy('code')->get(),
            'redFlagCriteria' => RedFlagCriteria::where('is_active', true)->get(),
        ])->layout('layouts.app', ['title' => 'Nueva Evaluación']);
    }
}
