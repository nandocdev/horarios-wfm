<?php

declare(strict_types=1);

namespace App\Src\Quality\Presentation\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Src\Quality\Application\DTOs\SubmitEvaluationDTO;
use App\Src\Quality\Application\Handlers\SubmitEvaluationHandler;
use App\Src\Quality\Infrastructure\Persistence\EloquentAgentEvaluation;
use App\Src\Quality\Infrastructure\Persistence\EloquentEvaluationForm;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Evaluación de Agente')]
class AgentEvaluationForm extends Component
{
    public Employee $agent;
    public int $selectedFormId;
    public array $scores = [];
    public string $comments = '';

    public function mount(int $agentId): void
    {
        $this->agent = Employee::with('user')->findOrFail($agentId);
    }

    public function submit(): void
    {
        $handler = app(SubmitEvaluationHandler::class);

        $result = $handler->handle(new SubmitEvaluationDTO(
            agentId: $this->agent->id,
            evaluatorId: auth()->user()->employee?->id ?? 0,
            formId: $this->selectedFormId,
            scores: $this->scores,
            comments: $this->comments,
        ));

        toast('Evaluación ' . ($result->status() === 'void' ? 'anulada por error crítico' : 'completada') . '. Score: ' . $result->totalScore());
    }

    public function render()
    {
        return view('quality::livewire.agent-evaluation-form', [
            'forms' => EloquentEvaluationForm::with('criteria')->where('is_active', true)->get(),
        ]);
    }
}
