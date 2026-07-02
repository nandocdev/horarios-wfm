<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Presentation\Livewire;

use App\Src\TimeAndAttendance\Application\Handlers\JustifyIncidentHandler;
use App\Src\TimeAndAttendance\Infrastructure\Persistence\EloquentAttendanceIncident;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Incidencias de Asistencia')]
class ListIncidents extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $justifyingId = null;
    public string $justifyComment = '';

    public function justify(int $incidentId): void
    {
        $this->justifyingId = $incidentId;
        $this->justifyComment = '';
    }

    public function cancelJustify(): void
    {
        $this->reset(['justifyingId', 'justifyComment']);
    }

    public function saveJustify(JustifyIncidentHandler $handler): void
    {
        $this->validate(['justifyComment' => ['required', 'string', 'min:10', 'max:1000']]);

        $handler->handle($this->justifyingId, $this->justifyComment, auth()->id());

        $this->cancelJustify();
        toast('Incidencia justificada correctamente.');
    }

    public function resolve(int $id): void
    {
        $handler = app(JustifyIncidentHandler::class);
        $handler->resolve($id, auth()->id(), 'Resuelto por administrador.');

        toast('Incidencia resuelta.');
    }

    public function render()
    {
        $query = EloquentAttendanceIncident::with('employee')
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($sub) => $sub
                ->where('first_name', 'ilike', "%{$this->search}%")
                ->orWhere('last_name', 'ilike', "%{$this->search}%")
            ));

        return view('ta::livewire.list-incidents', [
            'incidents' => $query->latest()->paginate(15),
        ]);
    }
}
