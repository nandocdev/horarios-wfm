<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\Employee;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Src\Platform\Application\DTOs\ShoutoutDTO;
use App\Src\Platform\Application\Handlers\UpdateShoutoutHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Editar Reconocimiento')]
class EditShoutout extends Component
{
    use WithFileUploads;

    public Shoutout $shoutout;
    public string $mode = 'edit';

    public array $form = [
        'employee_id' => null,
        'message' => '',
        'scheduled_at' => '',
        'archive_at' => '',
        'is_active' => true,
    ];

    public $image;

    public function mount(Shoutout $shoutout): void
    {
        $this->shoutout = $shoutout;

        $this->form = [
            'employee_id' => $shoutout->employee_id,
            'message' => $shoutout->message,
            'scheduled_at' => $shoutout->scheduled_at?->format('Y-m-d\TH:i'),
            'archive_at' => $shoutout->archive_at?->format('Y-m-d\TH:i'),
            'is_active' => $shoutout->is_active,
        ];
    }

    public function update(): void
    {
        $this->validate([
            'form.employee_id' => ['required', 'integer', 'exists:personnel_employees,id'],
            'form.message' => ['required', 'string', 'max:200'],
            'form.scheduled_at' => ['nullable', 'date'],
            'form.archive_at' => ['nullable', 'date'],
            'form.is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $handler = app(UpdateShoutoutHandler::class);
        $dto = ShoutoutDTO::fromArray($this->form);

        $handler->execute($this->shoutout->id, $dto);

        if ($this->image) {
            $this->shoutout->addMedia($this->image)->toMediaCollection('banner');
        }

        $this->dispatch('shoutout-updated');
    }

    public function render()
    {
        return view('platform::livewire.shoutout-form', [
            'employees' => Employee::with('position')->get(),
        ]);
    }
}
