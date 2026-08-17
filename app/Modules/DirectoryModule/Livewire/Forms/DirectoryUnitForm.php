<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Livewire\Forms;

use App\Modules\DirectoryModule\DTOs\UnitDTO;
use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Formulario de Livewire para capturar una unidad (piso) del directorio:
 * edificio + jerarquía administrativa + sector + piso + servicios + contactos.
 */
class DirectoryUnitForm extends Form
{
    public ?Unit $unit = null;

    public ?int $building_id = null;

    public ?string $new_building = null;

    public ?string $director_name = null;

    public ?string $subdirector_name = null;

    public ?string $administrator_name = null;

    public ?string $sector = null;

    public ?string $level = null;

    public ?string $new_level = null;

    public bool $is_active = true;

    /** @var array<int, array<string, string|null>> */
    public array $services = [];

    private const HOURS_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d\s*-\s*([01]\d|2[0-3]):[0-5]\d$/';

    public function rules(): array
    {
        return [
            'building_id' => 'nullable|integer|exists:directory_buildings,id',
            'new_building' => [
                Rule::requiredIf($this->building_id === null),
                'nullable',
                'string',
                'max:255',
            ],
            'director_name' => [
                Rule::requiredIf($this->building_id === null),
                'nullable',
                'string',
                'max:255',
            ],
            'subdirector_name' => 'nullable|string|max:255',
            'administrator_name' => [
                Rule::requiredIf($this->building_id === null),
                'nullable',
                'string',
                'max:255',
            ],
            'sector' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'new_level' => [
                Rule::requiredIf($this->level === null),
                'nullable',
                'string',
                'max:255',
            ],
            'is_active' => 'boolean',
            'services' => 'required|array|min:1',
            'services.*.name' => 'required|string|max:255',
            'services.*.door_id' => 'nullable|string|max:255',
            'services.*.attention_hours' => ['required', 'regex:'.self::HOURS_PATTERN],
            'services.*.results_hours' => ['nullable', 'regex:'.self::HOURS_PATTERN],
            'services.*.contact_role' => 'required|string|max:255',
            'services.*.contact_extension' => ['required', 'regex:/^[0-9]{1,10}$/'],
            'services.*.contact_email' => 'nullable|email|max:255',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'building_id' => 'edificio',
            'new_building' => 'nombre del edificio',
            'director_name' => 'director médico',
            'subdirector_name' => 'sub-director médico',
            'administrator_name' => 'administrador / encargado',
            'sector' => 'nodo / sector',
            'level' => 'nivel / piso',
            'new_level' => 'nivel / piso',
            'services' => 'servicios',
            'services.*.name' => 'nombre del servicio',
            'services.*.door_id' => 'puerta / consultorio',
            'services.*.attention_hours' => 'horario de atención',
            'services.*.results_hours' => 'horario de entrega de resultados',
            'services.*.contact_role' => 'rol del contacto',
            'services.*.contact_extension' => 'extensión telefónica',
            'services.*.contact_email' => 'correo del departamento',
        ];
    }

    public function usesExistingBuilding(): bool
    {
        return $this->building_id !== null;
    }

    public function usesExistingLevel(): bool
    {
        return $this->level !== null;
    }

    /**
     * Llena el formulario con los datos de una unidad (piso) existente.
     */
    public function setUnit(Unit $unit): void
    {
        $this->unit = $unit;
        $this->building_id = $unit->building_id;
        $this->director_name = $unit->building->director_name;
        $this->subdirector_name = $unit->building->subdirector_name;
        $this->administrator_name = $unit->building->administrator_name;
        $this->sector = $unit->sector;
        $this->level = $unit->level;
        $this->new_level = null;
        $this->is_active = $unit->is_active;
        $this->services = $unit->services->map(fn ($service) => [
            'name' => $service->name,
            'door_id' => $service->door_id,
            'attention_hours' => $service->attention_hours,
            'results_hours' => $service->results_hours,
            'contact_role' => $service->contact_role,
            'contact_extension' => $service->contact_extension,
            'contact_email' => $service->contact_email,
        ])->all();
    }

    /**
     * Al seleccionar un edificio existente se autocompletan los responsables
     * administrativos (una sola vez por edificio) y se reinicia el piso,
     * que depende del edificio y sector elegidos.
     */
    public function selectBuilding(?int $buildingId): void
    {
        $this->building_id = $buildingId;
        $this->new_building = null;

        $this->resetLevel();

        if ($buildingId === null) {
            $this->director_name = null;
            $this->subdirector_name = null;
            $this->administrator_name = null;

            return;
        }

        $building = Building::find($buildingId);
        $this->director_name = $building?->director_name;
        $this->subdirector_name = $building?->subdirector_name;
        $this->administrator_name = $building?->administrator_name;
    }

    /**
     * Selecciona un piso existente o limpia la selección para crear uno nuevo.
     */
    public function selectLevel(?string $level): void
    {
        $this->level = $level ? trim($level) : null;

        if ($this->level !== null) {
            $this->new_level = null;
        }
    }

    /**
     * Reinicia el piso y sus dependencias (servicios).
     */
    public function resetLevel(): void
    {
        $this->level = null;
        $this->new_level = null;
        $this->unit = null;
        $this->services = [];
    }

    /**
     * Elimina filas de servicios que quedaron completamente vacías.
     */
    public function pruneEmptyRows(): void
    {
        $this->services = array_values(array_filter(
            $this->services,
            fn (array $row) => filled($row['name'] ?? null)
                || filled($row['door_id'] ?? null)
                || filled($row['attention_hours'] ?? null)
                || filled($row['results_hours'] ?? null)
                || filled($row['contact_role'] ?? null)
                || filled($row['contact_extension'] ?? null)
                || filled($row['contact_email'] ?? null)
        ));
    }

    public function toDTO(): UnitDTO
    {
        return UnitDTO::fromArray([
            'building_id' => $this->building_id,
            'new_building' => $this->new_building,
            'director_name' => $this->director_name,
            'subdirector_name' => $this->subdirector_name,
            'administrator_name' => $this->administrator_name,
            'sector' => $this->sector,
            'level' => $this->level,
            'new_level' => $this->new_level,
            'is_active' => $this->is_active,
            'services' => $this->services,
        ]);
    }

    public function resetForm(): void
    {
        $this->unit = null;
        $this->building_id = null;
        $this->new_building = null;
        $this->director_name = null;
        $this->subdirector_name = null;
        $this->administrator_name = null;
        $this->sector = null;
        $this->level = null;
        $this->new_level = null;
        $this->is_active = true;
        $this->services = [];
    }
}
