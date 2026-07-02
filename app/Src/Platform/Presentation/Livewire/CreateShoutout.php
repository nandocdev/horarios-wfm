<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\Employee;
use App\Src\Platform\Application\DTOs\ShoutoutDTO;
use App\Src\Platform\Application\Handlers\CreateShoutoutHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Crear Reconocimiento')]
class CreateShoutout extends Component
{
    use WithFileUploads;

    public string $mode = 'create';

    public array $form = [
        'employee_id' => null,
        'message' => '',
        'scheduled_at' => '',
        'archive_at' => '',
        'is_active' => true,
        'workflow_action' => 'save_draft',
    ];

    public $image;

    public function save(): void
    {
        $this->validate([
            'form.employee_id' => ['required', 'integer', 'exists:personnel_employees,id'],
            'form.message' => ['required', 'string', 'max:200'],
            'form.scheduled_at' => ['nullable', 'date'],
            'form.archive_at' => ['nullable', 'date'],
            'form.is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $handler = app(CreateShoutoutHandler::class);
        $dto = ShoutoutDTO::fromArray($this->form);

        $shoutout = $handler->execute($dto, auth()->id());

        if ($this->image) {
            $shoutout->addMedia($this->image)->toMediaCollection('banner');
        }

        $this->dispatch('shoutout-created');
    }

    public function render()
    {
        return view('platform::livewire.shoutout-form', [
            'employees' => Employee::with('position')->get(),
        ]);
    }
}
