<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Models\Evaluation;
use Livewire\Component;

class EvaluationDetail extends Component
{
    public Evaluation $evaluation;

    public function mount(Evaluation $evaluation): void
    {
        $this->evaluation = $evaluation->load([
            'queue', 'scores.criteriaVersion', 'redFlags.redFlagCriteria',
            'feedbacks.creator', 'calibrations.creator',
        ]);
    }

    public function render()
    {
        return view('quality::livewire.evaluation-detail', [
            'evaluation' => $this->evaluation,
        ])->layout('layouts.app', ['title' => 'Detalle de Evaluación']);
    }
}
