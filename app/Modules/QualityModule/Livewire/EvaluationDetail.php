<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Models\Evaluation;
use Livewire\Component;

class EvaluationDetail extends Component
{
    public Evaluation $evaluation;

    public function mount(string $evaluation): void
    {
        $this->evaluation = Evaluation::with([
            'queue', 'scores.criteriaVersion', 'redFlags.redFlagCriteria',
            'feedbacks.creator', 'calibrations.creator',
        ])->findOrFail($evaluation);
    }

    public function render()
    {
        return view('quality::livewire.evaluation-detail', [
            'evaluation' => $this->evaluation,
        ])->layout('layouts.app', ['title' => 'Detalle de Evaluación']);
    }
}
