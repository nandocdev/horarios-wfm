<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Src\Platform\Application\DTOs\PollDTO;
use App\Src\Platform\Application\Handlers\CreatePollHandler;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Crear Encuesta')]
class CreatePoll extends Component
{
    public string $mode = 'create';

    public array $form = [
        'question' => '',
        'options' => [
            ['text' => '', 'color' => 'blue'],
            ['text' => '', 'color' => 'green'],
        ],
        'scheduled_at' => '',
        'expires_at' => '',
        'archive_at' => '',
        'is_active' => true,
        'workflow_action' => 'save_draft',
    ];

    public function addOption(): void
    {
        if (count($this->form['options']) >= 10) {
            return;
        }

        $this->form['options'][] = ['text' => '', 'color' => 'gray'];
    }

    public function removeOption(int $index): void
    {
        if (count($this->form['options']) <= 2) {
            return;
        }

        unset($this->form['options'][$index]);
        $this->form['options'] = array_values($this->form['options']);
    }

    public function save(): void
    {
        $this->validate([
            'form.question' => ['required', 'string', 'max:255'],
            'form.options' => ['required', 'array', 'min:2', 'max:10'],
            'form.options.*.text' => ['required', 'string', 'max:255'],
            'form.options.*.color' => ['required', 'string', 'max:50'],
            'form.scheduled_at' => ['nullable', 'date'],
            'form.expires_at' => ['nullable', 'date', 'after_or_equal:form.scheduled_at'],
            'form.archive_at' => ['nullable', 'date'],
            'form.is_active' => ['nullable', 'boolean'],
        ]);

        $handler = app(CreatePollHandler::class);
        $dto = PollDTO::fromArray($this->form);

        $handler->execute($dto);

        $this->dispatch('poll-created');
    }

    public function render()
    {
        return view('platform::livewire.poll-form');
    }
}
