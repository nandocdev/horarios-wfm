<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Actions\CreateCriteriaAction;
use App\Modules\QualityModule\Actions\CreateCriteriaVersionAction;
use App\Modules\QualityModule\Models\Criteria;
use Livewire\Component;

class CriteriaForm extends Component
{
    public ?Criteria $criteria = null;

    public string $code = '';

    public string $criterio_text = '';

    public int $puntaje = 10;

    public ?string $descripcion = null;

    public bool $isEditing = false;

    public function mount(?string $criteria = null): void
    {
        if ($criteria) {
            $this->criteria = Criteria::findOrFail($criteria);
            $this->code = $this->criteria->code;
            $this->criterio_text = $this->criteria->currentVersion?->criterio_text ?? '';
            $this->puntaje = $this->criteria->currentVersion?->puntaje ?? 10;
            $this->descripcion = $this->criteria->currentVersion?->descripcion ?? '';
            $this->isEditing = true;
        }
    }

    public function submit(CreateCriteriaAction|CreateCriteriaVersionAction $action): void
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:quality_criteria,code'.($this->isEditing ? ','.$this->criteria?->id : ''),
            'criterio_text' => 'required|string|max:500',
            'puntaje' => 'required|integer|min:1|max:100',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        if ($this->isEditing && $this->criteria) {
            $action->execute($this->criteria->id, [
                'criterio_text' => $this->criterio_text,
                'puntaje' => $this->puntaje,
                'descripcion' => $this->descripcion,
            ]);
            session()->flash('message', 'Criterio actualizado (nueva versión creada).');
        } else {
            app(CreateCriteriaAction::class)->execute([
                'code' => $this->code,
                'criterio_text' => $this->criterio_text,
                'puntaje' => $this->puntaje,
                'descripcion' => $this->descripcion,
            ]);
            session()->flash('message', 'Criterio creado correctamente.');
        }

        $this->redirectRoute('quality.criteria.index');
    }

    public function render()
    {
        return view('quality::livewire.criteria-form')
            ->layout('layouts.app', ['title' => $this->isEditing ? 'Editar Criterio' : 'Nuevo Criterio']);
    }
}
