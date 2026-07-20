<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Actions\AssignCriteriaToQueueAction;
use App\Modules\QualityModule\Actions\CreateCriteriaVersionAction;
use App\Modules\QualityModule\Actions\RemoveCriteriaFromQueueAction;
use App\Modules\QualityModule\Actions\ReorderQueueCriteriaAction;
use App\Modules\QualityModule\Actions\ToggleQueueCriteriaAction;
use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use App\Modules\QualityModule\Models\Queue;
use App\Modules\QualityModule\Models\QueueCriteria;
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

    public function mount(): void
    {
        $this->selectedQueueId = request('queue', Queue::first()?->id);
    }

    public function selectQueue(string $queueId): void
    {
        $this->selectedQueueId = $queueId;
        $this->newCriteriaId = '';
    }

    public function addCriteria(AssignCriteriaToQueueAction $action): void
    {
        $this->validate(['newCriteriaId' => 'required|string|exists:quality_criteria,id']);

        if (! $this->selectedQueueId) {
            return;
        }

        try {
            $action->execute($this->selectedQueueId, $this->newCriteriaId);
            $this->newCriteriaId = '';
            session()->flash('message', 'Criterio asignado a la cola.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function removeCriteria(string $queueCriteriaId, RemoveCriteriaFromQueueAction $action): void
    {
        $action->execute($queueCriteriaId);
        session()->flash('message', 'Criterio removido de la cola.');
    }

    public function toggleActive(string $queueCriteriaId, ToggleQueueCriteriaAction $action): void
    {
        $action->execute($queueCriteriaId);
    }

    public function moveUp(string $queueCriteriaId, ReorderQueueCriteriaAction $action): void
    {
        $action->moveUp($queueCriteriaId);
    }

    public function moveDown(string $queueCriteriaId, ReorderQueueCriteriaAction $action): void
    {
        $action->moveDown($queueCriteriaId);
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

    public function saveVersion(CreateCriteriaVersionAction $action): void
    {
        $this->validate([
            'editCriterioText' => 'required|string|max:500',
            'editPuntaje' => 'required|integer|min:1|max:100',
            'editDescripcion' => 'nullable|string|max:1000',
        ]);

        $currentVersion = CriteriaVersion::with('criteria')->findOrFail($this->editCriteriaVersionId);

        $newVersion = $action->execute($currentVersion->criteria_id, [
            'criterio_text' => $this->editCriterioText,
            'puntaje' => $this->editPuntaje,
            'descripcion' => $this->editDescripcion ?: null,
        ]);

        QueueCriteria::where('criteria_version_id', $currentVersion->id)
            ->where('queue_id', $this->selectedQueueId)
            ->update(['criteria_version_id' => $newVersion->id]);

        $this->showEditModal = false;
        $this->reset(['editCriteriaVersionId', 'editCriterioText', 'editPuntaje', 'editDescripcion']);

        session()->flash('message', 'Criterio actualizado. Nueva versión #'.$newVersion->version.' creada.');
    }

    public function render()
    {
        $queues = Queue::orderBy('code')->get();

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
            ->filter(fn (Criteria $c) => $c->currentVersion !== null)
            ->values();

        return view('quality::livewire.manage-queue-criteria', [
            'queues' => $queues,
            'assignedCriteria' => $assignedCriteria,
            'availableCriteria' => $availableCriteria,
        ])->layout('layouts.app', ['title' => 'Administrar Criterios por Cola']);
    }
}
