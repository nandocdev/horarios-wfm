<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Actions\CreateCriteriaAction;
use App\Modules\QualityModule\Actions\CreateCriteriaVersionAction;
use App\Modules\QualityModule\Livewire\Forms\CriteriaFormData;
use App\Modules\QualityModule\Models\Criteria;
use Livewire\Component;

class CriteriaForm extends Component
{
    public CriteriaFormData $form;

    public ?Criteria $criteria = null;

    public bool $isEditing = false;

    public function mount(?string $criteria = null): void
    {
        if ($criteria) {
            $this->criteria = Criteria::findOrFail($criteria);
            $this->form->fill([
                'name' => $this->criteria->currentVersion?->criterio_text ?? '',
                'description' => $this->criteria->currentVersion?->descripcion ?? '',
                'max_score' => $this->criteria->currentVersion?->puntaje ?? 10,
                'type' => 'evaluable',
            ]);
            $this->isEditing = true;
        }
    }

    public function submit(CreateCriteriaAction|CreateCriteriaVersionAction $action): void
    {
        $this->form->validate();

        if ($this->isEditing && $this->criteria) {
            $action->execute($this->criteria->id, [
                'criterio_text' => $this->form->name,
                'puntaje' => $this->form->max_score,
                'descripcion' => $this->form->description,
            ]);
            session()->flash('message', 'Criterio actualizado (nueva versión creada).');
        } else {
            app(CreateCriteriaAction::class)->execute([
                'code' => $this->form->name,
                'criterio_text' => $this->form->name,
                'puntaje' => $this->form->max_score,
                'descripcion' => $this->form->description,
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
