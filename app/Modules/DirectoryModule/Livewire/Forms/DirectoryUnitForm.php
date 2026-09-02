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

    public ?string $color_identifier = null;

    public ?string $description = null;

    public ?string $door_range = null;

    public ?string $wing_sector = null;

    public ?string $attention_schedule = null;

    public bool $is_active = true;

    /** @var array<int, array<string, mixed>> */
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
            'color_identifier' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'sector' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'new_level' => [
                Rule::requiredIf($this->level === null),
                'nullable',
                'string',
                'max:255',
            ],
            'door_range' => 'nullable|string|max:50',
            'wing_sector' => 'nullable|string|max:100',
            'attention_schedule' => 'nullable|string|max:150',
            'is_active' => 'boolean',
            'services' => 'required|array|min:1',
            'services.*.name' => 'required|string|max:255',
            'services.*.is_person' => 'nullable|boolean',
            'services.*.office_number' => 'nullable|string|max:50',
            'services.*.door_id' => 'nullable|string|max:255',
            'services.*.attention_hours' => 'nullable|string|max:255',
            'services.*.results_hours' => 'nullable|string|max:255',
            'services.*.contact_role' => 'nullable|string|max:255',
            'services.*.contact_extension' => 'nullable|string|max:30',
            'services.*.contact_email' => 'nullable|email|max:255',
            // 1:N phones por servicio (CISCO/DIRECTO/MOVIL/WHATSAPP_CHAT)
            'services.*.phones' => 'nullable|array',
            'services.*.phones.*.number' => 'required_with:services.*.phones|string|max:30',
            'services.*.phones.*.type' => 'nullable|string|in:CISCO,DIRECTO,MOVIL,WHATSAPP_CHAT',
            'services.*.phones.*.description' => 'nullable|string|max:100',
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
            'color_identifier' => 'color identificador',
            'description' => 'descripción del edificio',
            'sector' => 'nodo / sector',
            'level' => 'nivel / piso',
            'new_level' => 'nivel / piso',
            'door_range' => 'rango de puertas',
            'wing_sector' => 'ala / sector',
            'attention_schedule' => 'horario del piso',
            'services' => 'servicios',
            'services.*.name' => 'nombre del servicio',
            'services.*.is_person' => 'tipo de entidad',
            'services.*.office_number' => 'oficina',
            'services.*.door_id' => 'puerta / consultorio',
            'services.*.attention_hours' => 'horario de atención',
            'services.*.results_hours' => 'horario de entrega de resultados',
            'services.*.contact_role' => 'rol del contacto',
            'services.*.contact_extension' => 'extensión telefónica',
            'services.*.contact_email' => 'correo del departamento',
            'services.*.phones.*.number' => 'número telefónico',
            'services.*.phones.*.type' => 'tipo de teléfono',
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
        $unit->loadMissing(['building', 'services.phones']);
        $this->unit = $unit;
        $this->building_id = $unit->building_id;
        $this->director_name = $unit->building->director_name;
        $this->subdirector_name = $unit->building->subdirector_name;
        $this->administrator_name = $unit->building->administrator_name;
        $this->color_identifier = $unit->building->color_identifier;
        $this->description = $unit->building->description;
        $this->sector = $unit->sector;
        $this->level = $unit->level;
        $this->new_level = null;
        $this->door_range = $unit->door_range;
        $this->wing_sector = $unit->wing_sector;
        $this->attention_schedule = $unit->attention_schedule;
        $this->is_active = $unit->is_active;
        $this->services = $unit->services->map(fn ($service) => [
            'name' => $service->name,
            'is_person' => $service->is_person,
            'office_number' => $service->office_number,
            'door_id' => $service->door_id,
            'attention_hours' => $service->attention_hours,
            'results_hours' => $service->results_hours,
            'contact_role' => $service->contact_role,
            'contact_extension' => $service->contact_extension,
            'contact_email' => $service->contact_email,
            'phones' => $service->phones->map(fn ($p) => [
                'number' => $p->number,
                'type' => $p->type,
                'description' => $p->description,
            ])->all(),
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
            function (array $row): bool {
                if (filled($row['name'] ?? null)) {
                    return true;
                }
                // si tiene teléfonos también cuenta
                if (! empty($row['phones']) && is_array($row['phones'])) {
                    foreach ($row['phones'] as $ph) {
                        if (filled($ph['number'] ?? null)) {
                            return true;
                        }
                    }
                }

                return filled($row['door_id'] ?? null)
                    || filled($row['attention_hours'] ?? null)
                    || filled($row['results_hours'] ?? null)
                    || filled($row['contact_role'] ?? null)
                    || filled($row['contact_extension'] ?? null)
                    || filled($row['contact_email'] ?? null)
                    || filled($row['office_number'] ?? null);
            }
        ));

        // Normalizar phones vacíos
        foreach ($this->services as &$svc) {
            if (isset($svc['phones']) && is_array($svc['phones'])) {
                $svc['phones'] = array_values(array_filter(
                    $svc['phones'],
                    fn (array $p) => filled($p['number'] ?? null)
                ));
            }
        }
        unset($svc);
    }

    public function toDTO(): UnitDTO
    {
        return UnitDTO::fromArray([
            'building_id' => $this->building_id,
            'new_building' => $this->new_building,
            'director_name' => $this->director_name,
            'subdirector_name' => $this->subdirector_name,
            'administrator_name' => $this->administrator_name,
            'color_identifier' => $this->color_identifier,
            'description' => $this->description,
            'sector' => $this->sector,
            'level' => $this->level,
            'new_level' => $this->new_level,
            'door_range' => $this->door_range,
            'wing_sector' => $this->wing_sector,
            'attention_schedule' => $this->attention_schedule,
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
        $this->color_identifier = null;
        $this->description = null;
        $this->sector = null;
        $this->level = null;
        $this->new_level = null;
        $this->door_range = null;
        $this->wing_sector = null;
        $this->attention_schedule = null;
        $this->is_active = true;
        $this->services = [];
    }
}
