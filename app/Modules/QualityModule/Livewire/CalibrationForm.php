<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Actions\StoreCalibrationAction;
use App\Modules\QualityModule\DTOs\CreateCalibrationDTO;
use App\Modules\QualityModule\Models\CalibrationLog;
use App\Modules\QualityModule\Models\Evaluation;
use Livewire\Component;

class CalibrationForm extends Component
{
    public Evaluation $evaluation;

    public int $score_nuevo = 0;

    public ?string $obs = null;

    public function mount(Evaluation $evaluation): void
    {
        $this->evaluation = $evaluation;
        $this->score_nuevo = $this->evaluation->score ?? 0;
    }

    public function submit(StoreCalibrationAction $action): void
    {
        $this->authorize('create', [CalibrationLog::class, $this->evaluation]);

        $this->validate([
            'score_nuevo' => 'required|integer|min:0|max:100',
            'obs' => 'nullable|string|max:2500',
        ]);

        $action->execute(new CreateCalibrationDTO(
            evaluation_id: $this->evaluation->id,
            score_nuevo: $this->score_nuevo,
            created_by: (int) auth()->id(),
            obs: $this->obs,
        ));

        session()->flash('message', 'Calibración registrada correctamente.');

        $this->redirectRoute('quality.evaluations.show', $this->evaluation->id);
    }

    public function render()
    {
        return view('quality::livewire.calibration-form')
            ->layout('layouts.app', ['title' => 'Calibrar Evaluación']);
    }
}
