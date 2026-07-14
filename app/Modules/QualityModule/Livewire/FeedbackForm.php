<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Actions\StoreFeedbackAction;
use App\Modules\QualityModule\DTOs\CreateFeedbackDTO;
use App\Modules\QualityModule\Models\Evaluation;
use Livewire\Component;

class FeedbackForm extends Component
{
    public Evaluation $evaluation;

    public string $obsfeed = '';

    public function mount(string $evaluation): void
    {
        $this->evaluation = Evaluation::findOrFail($evaluation);
    }

    public function submit(StoreFeedbackAction $action): void
    {
        $this->authorize('create', [\App\Modules\QualityModule\Models\Feedback::class, $this->evaluation]);

        $this->validate(['obsfeed' => 'required|string|max:2500']);

        $action->execute(new CreateFeedbackDTO(
            evaluation_id: $this->evaluation->id,
            obsfeed: $this->obsfeed,
            created_by: (int) auth()->id(),
        ));

        session()->flash('message', 'Feedback agregado correctamente.');

        $this->redirectRoute('quality.evaluations.show', $this->evaluation->id);
    }

    public function render()
    {
        return view('quality::livewire.feedback-form')
            ->layout('layouts.app', ['title' => 'Agregar Feedback']);
    }
}
