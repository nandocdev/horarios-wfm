<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Actions;

use App\Modules\DirectoryModule\DTOs\UnitDTO;
use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Crea una unidad del directorio junto a su edificio (si es nuevo),
 * servicios y puntos de contacto en una transacción.
 */
class CreateUnitAction
{
    public function execute(UnitDTO $dto): Unit
    {
        return DB::transaction(function () use ($dto) {
            $buildingId = $this->resolveBuilding($dto);

            $unit = Unit::create([
                'building_id' => $buildingId,
                'sector' => $dto->sector,
                'level' => $dto->new_level ?? $dto->level,
                'door_range' => $dto->door_range,
                'wing_sector' => $dto->wing_sector,
                'attention_schedule' => $dto->attention_schedule,
                'is_active' => $dto->is_active,
            ]);

            foreach ($dto->services as $svc) {
                $phones = $svc['phones'] ?? [];
                unset($svc['phones']);
                // compat: mantener contact_extension como primer teléfono si existe
                if (! empty($svc['contact_extension']) && empty($phones)) {
                    $phones[] = ['number' => $svc['contact_extension'], 'type' => 'CISCO', 'description' => null];
                }
                $service = $unit->services()->create($svc);
                if (! empty($phones)) {
                    $service->phones()->createMany($phones);
                }
            }

            return $unit;
        });
    }

    /**
     * Devuelve el edificio existente o crea uno nuevo con su jerarquía administrativa.
     */
    protected function resolveBuilding(UnitDTO $dto): int
    {
        if ($dto->building_id !== null) {
            return $dto->building_id;
        }

        $attrs = [
            'director_name' => $dto->director_name,
            'subdirector_name' => $dto->subdirector_name,
            'administrator_name' => $dto->administrator_name,
            'is_active' => $dto->is_active,
        ];
        if ($dto->color_identifier !== null) {
            $attrs['color_identifier'] = $dto->color_identifier;
        }
        if ($dto->description !== null) {
            $attrs['description'] = $dto->description;
        }

        $building = Building::firstOrCreate(
            ['name' => $dto->new_building],
            $attrs
        );

        // Si edificio ya existía pero vienen nuevos metadatos, actualizarlos
        if ($dto->color_identifier !== null && $building->color_identifier === null) {
            $building->update(['color_identifier' => $dto->color_identifier]);
        }
        if ($dto->description !== null && $building->description === null) {
            $building->update(['description' => $dto->description]);
        }

        return $building->id;
    }
}
