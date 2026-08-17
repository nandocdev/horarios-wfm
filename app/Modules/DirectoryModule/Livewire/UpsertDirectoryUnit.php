<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Livewire;

use App\Modules\DirectoryModule\Actions\CreateUnitAction;
use App\Modules\DirectoryModule\Actions\UpdateUnitAction;
use App\Modules\DirectoryModule\Livewire\Forms\DirectoryUnitForm;
use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\DirectoryService;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Formulario jerárquico para crear o editar una unidad (piso) del directorio:
 * edificio + jerarquía administrativa + sector + piso + servicios + contactos.
 */
class UpsertDirectoryUnit extends Component
{
    use AuthorizesRequests;

    public DirectoryUnitForm $form;

    public ?Unit $unit = null;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->unit = Unit::with(['building', 'services'])->findOrFail($id);
            $this->authorize('update', $this->unit);
            $this->form->setUnit($this->unit);
        } else {
            $this->authorize('create', Unit::class);
            $this->form->resetForm();
        }
    }

    /**
     * Al cambiar el edificio se autocompletan los responsables y se reinicia
     * el piso (los pisos dependen del edificio y sector).
     */
    public function onBuildingChange(): void
    {
        $this->form->selectBuilding($this->form->building_id > 0 ? (int) $this->form->building_id : null);
        $this->unit = null;
    }

    /**
     * Al seleccionar un piso existente se cargan sus servicios y contactos;
     * si se elige uno nuevo se limpian las dependencias.
     */
    public function onLevelChange(): void
    {
        $value = $this->form->level;
        $this->form->selectLevel($value && $value !== '0' ? $value : null);
        $this->loadLevelDependents();
    }

    public function addService(): void
    {
        array_unshift($this->form->services, [
            'name' => '',
            'door_id' => '',
            'attention_hours' => '',
            'results_hours' => '',
            'contact_role' => '',
            'contact_extension' => '',
            'contact_email' => '',
        ]);
    }

    public function removeService(int $index): void
    {
        unset($this->form->services[$index]);
        $this->form->services = array_values($this->form->services);
    }

    public function save(CreateUnitAction $createAction, UpdateUnitAction $updateAction)
    {
        $this->form->pruneEmptyRows();
        $this->form->validate();

        if ($this->duplicateUnitExists()) {
            $this->addError('form.level', 'Ya existe una unidad con este edificio, sector y piso.');

            return;
        }

        $dto = $this->form->toDTO();

        if ($this->unit) {
            $this->authorize('update', $this->unit);
            $updateAction->execute($this->unit, $dto);
            \Flux::toast('Unidad actualizada correctamente.');
        } else {
            $this->authorize('create', Unit::class);
            $createAction->execute($dto);
            \Flux::toast('Unidad creada correctamente.');
        }

        return $this->redirectRoute('directory.index', navigate: true);
    }

    /**
     * Evita pisos duplicados dentro del mismo edificio y sector.
     */
    protected function duplicateUnitExists(): bool
    {
        $buildingId = $this->form->building_id
            ?? Building::where('name', $this->form->new_building)->value('id');

        if ($buildingId === null) {
            return false;
        }

        $level = $this->form->new_level ?? $this->form->level;

        return Unit::where('building_id', $buildingId)
            ->where('sector', $this->form->sector)
            ->where('level', $level)
            ->when($this->unit, fn ($q) => $q->where('id', '!=', $this->unit->id))
            ->exists();
    }

    protected function loadLevelDependents(): void
    {
        $level = $this->form->level;

        if ($level === null || $this->form->building_id === null) {
            $this->unit = null;
            $this->form->services = [];

            return;
        }

        $existing = Unit::where('building_id', $this->form->building_id)
            ->where('sector', $this->form->sector)
            ->where('level', $level)
            ->first();

        if ($existing) {
            $this->unit = $existing;
            $this->form->services = $existing->services->map(fn ($service) => [
                'name' => $service->name,
                'door_id' => $service->door_id,
                'attention_hours' => $service->attention_hours,
                'results_hours' => $service->results_hours,
                'contact_role' => $service->contact_role,
                'contact_extension' => $service->contact_extension,
                'contact_email' => $service->contact_email,
            ])->all();
        } else {
            $this->unit = null;
            $this->form->services = [];
        }
    }

    public function render()
    {
        $buildings = Building::orderBy('name')->get(['id', 'name']);

        $buildingId = $this->form->building_id
            ?? Building::where('name', $this->form->new_building)->value('id');

        $levelQuery = Unit::where('building_id', $buildingId)
            ->when($this->form->sector, fn ($q) => $q->where('sector', $this->form->sector));

        return view('directory::livewire.upsert-directory-unit', [
            'buildings' => $buildings,
            'sectorSuggestions' => Unit::where('building_id', $buildingId)
                ->pluck('sector')
                ->filter()
                ->unique()
                ->values(),
            'levelSuggestions' => $levelQuery->clone()->pluck('level')->filter()->unique()->values(),
            'doorSuggestions' => DirectoryService::query()->distinct()->pluck('door_id')->filter()->unique()->values(),
            'serviceSuggestions' => DirectoryService::query()->distinct()->pluck('name')->filter()->unique()->values(),
            'roleSuggestions' => DirectoryService::query()->distinct()->pluck('contact_role')->filter()->unique()->values(),
        ])->layout('layouts.app');
    }
}
