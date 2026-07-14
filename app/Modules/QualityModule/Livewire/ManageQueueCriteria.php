<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use App\Modules\QualityModule\Models\Queue;
use App\Modules\QualityModule\Models\QueueCriteria;
use Illuminate\Support\Collection;
use Livewire\Component;

class ManageQueueCriteria extends Component
{
    public ?string $selectedQueueId = null;

    public string $newCriteriaId = '';

    public string $editCriteriaVersionId = '';

    public string $editCriterioText = '';

    public int $editPuntaje = 0;

    public string $editDescripcion = '';

    public bool $showEditModal = false;

    protected $rules = [
        'newCriteriaId' => 'required|string|exists:quality_criteria,id',
    ];

    public function mount(): void
    {
        $this->selectedQueueId = request('queue', Queue::first()?->id);
    }

    public function selectQueue(string $queueId): void
    {
        $this->selectedQueueId = $queueId;
        $this->newCriteriaId = '';
    }

    public function addCriteria(): void
    {
        $this->validate();

        if (! $this->selectedQueueId) {
            return;
        }

        $criteria = Criteria::findOrFail($this->newCriteriaId);
        $currentVersion = $criteria->currentVersion();

        if (! $currentVersion) {
            session()->flash('error', 'El criterio seleccionado no tiene una versión activa.');

            return;
        }

        $exists = QueueCriteria::where('queue_id', $this->selectedQueueId)
            ->where('criteria_version_id', $currentVersion->id)
            ->exists();

        if ($exists) {
            session()->flash('error', 'Este criterio ya está asignado a la cola.');

            return;
        }

        $maxOrden = QueueCriteria::where('queue_id', $this->selectedQueueId)->max('orden') ?? 0;

        QueueCriteria::create([
            'queue_id' => $this->selectedQueueId,
            'criteria_version_id' => $currentVersion->id,
            'orden' => $maxOrden + 1,
            'is_active' => true,
        ]);

        $this->newCriteriaId = '';
        session()->flash('message', 'Criterio asignado a la cola.');
    }

    public function removeCriteria(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $qc->delete();

        session()->flash('message', 'Criterio removido de la cola.');
    }

    public function toggleActive(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $qc->update(['is_active' => ! $qc->is_active]);
    }

    public function moveUp(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $prev = QueueCriteria::where('queue_id', $qc->queue_id)
            ->where('orden', '<', $qc->orden)
            ->orderByDesc('orden')
            ->first();

        if ($prev) {
            $temp = $qc->orden;
            $qc->update(['orden' => $prev->orden]);
            $prev->update(['orden' => $temp]);
        }
    }

    public function moveDown(string $queueCriteriaId): void
    {
        $qc = QueueCriteria::findOrFail($queueCriteriaId);
        $next = QueueCriteria::where('queue_id', $qc->queue_id)
            ->where('orden', '>', $qc->orden)
            ->orderBy('orden')
            ->first();

        if ($next) {
            $temp = $qc->orden;
            $qc->update(['orden' => $next->orden]);
            $next->update(['orden' => $temp]);
        }
    }

    public function editVersion(string $criteriaVersionId): void
    {
        $version = CriteriaVersion::with('criteria')->findOrFail($criteriaVersionId);
        $this->editCriteriaVersionId = $version->id;
        $this->editCriterioText = $version->criterio_text;
        $this->editPuntaje = $version->puntaje;
        $this->editDescripcion = $version->descripcion ?? '';
        $this->showEditModal = true;
    }

    public function saveVersion(): void
    {
        $this->validate([
            'editCriterioText' => 'required|string|max:500',
            'editPuntaje' => 'required|integer|min:1|max:100',
            'editDescripcion' => 'nullable|string|max:1000',
        ]);

        $currentVersion = CriteriaVersion::with('criteria')->findOrFail($this->editCriteriaVersionId);
        $criteria = $currentVersion->criteria;

        $nextVersion = $criteria->versions()->max('version') + 1;

        $currentVersion->update([
            'valid_to' => now()->subDay()->toDateString(),
        ]);

        $newVersion = CriteriaVersion::create([
            'criteria_id' => $criteria->id,
            'version' => $nextVersion,
            'criterio_text' => $this->editCriterioText,
            'puntaje' => $this->editPuntaje,
            'descripcion' => $this->editDescripcion ?: null,
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
        ]);

        QueueCriteria::where('criteria_version_id', $currentVersion->id)
            ->where('queue_id', $this->selectedQueueId)
            ->update(['criteria_version_id' => $newVersion->id]);

        $this->showEditModal = false;
        $this->reset(['editCriteriaVersionId', 'editCriterioText', 'editPuntaje', 'editDescripcion']);

        session()->flash('message', 'Criterio actualizado. Nueva versión #'.$nextVersion.' creada.');
    }

    public function render()
    {
        $queues = Queue::orderBy('code')->get();

        /** @var Collection<int, array> $assignedCriteria */
        $assignedCriteria = collect();

        if ($this->selectedQueueId) {
            $assignedCriteria = QueueCriteria::with(['criteriaVersion.criteria'])
                ->where('queue_id', $this->selectedQueueId)
                ->orderBy('orden')
                ->get()
                ->map(fn (QueueCriteria $qc) => [
                    'id' => $qc->id,
                    'criteria_version_id' => $qc->criteria_version_id,
                    'codigo' => $qc->criteriaVersion?->criteria?->code ?? '—',
                    'criterio_text' => $qc->criteriaVersion?->criterio_text ?? '—',
                    'version' => $qc->criteriaVersion?->version ?? '—',
                    'puntaje' => $qc->criteriaVersion?->puntaje ?? 0,
                    'orden' => $qc->orden,
                    'is_active' => $qc->is_active,
                ]);
        }

        $availableCriteria = Criteria::with('currentVersion')
            ->get()
            ->filter(fn (Criteria $c) => $c->currentVersion() !== null)
            ->values();

        return view('quality::livewire.manage-queue-criteria', [
            'queues' => $queues,
            'assignedCriteria' => $assignedCriteria,
            'availableCriteria' => $availableCriteria,
        ])->layout('layouts.app', ['title' => 'Administrar Criterios por Cola']);
    }
}
