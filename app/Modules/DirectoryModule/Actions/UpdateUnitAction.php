<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Actions;

use App\Modules\DirectoryModule\DTOs\UnitDTO;
use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza una unidad del directorio, sus servicios y contactos.
 * La jerarquía administrativa pertenece al edificio y solo se crea
 * cuando el edificio es nuevo; nunca se sobrescribe sobre un edificio existente.
 */
class UpdateUnitAction
{
    public function execute(Unit $unit, UnitDTO $dto): Unit
    {
        return DB::transaction(function () use ($unit, $dto) {
            $unit = Unit::whereKey($unit->id)->lockForUpdate()->firstOrFail();

            $unit->update([
                'building_id' => $this->resolveBuildingId($unit, $dto),
                'sector' => $dto->sector,
                'level' => $dto->new_level ?? $dto->level,
                'door_range' => $dto->door_range,
                'wing_sector' => $dto->wing_sector,
                'attention_schedule' => $dto->attention_schedule,
                'is_active' => $dto->is_active,
            ]);

            // phones tienen FK cascade, al borrar services se borran
            $unit->services()->delete();
            foreach ($dto->services as $svc) {
                $phones = $svc['phones'] ?? [];
                unset($svc['phones']);
                if (! empty($svc['contact_extension']) && empty($phones)) {
                    $phones[] = ['number' => $svc['contact_extension'], 'type' => 'CISCO', 'description' => null];
                }
                $service = $unit->services()->create($svc);
                if (! empty($phones)) {
                    $service->phones()->createMany($phones);
                }
            }

            // Actualizar metadatos de edificio si vienen
            if ($dto->color_identifier !== null || $dto->description !== null) {
                $building = $unit->building;
                $updates = [];
                if ($dto->color_identifier !== null && $building->color_identifier === null) {
                    $updates['color_identifier'] = $dto->color_identifier;
                }
                if ($dto->description !== null && $building->description === null) {
                    $updates['description'] = $dto->description;
                }
                if (! empty($updates)) {
                    $building->update($updates);
                }
            }

            return $unit;
        });
    }

    protected function resolveBuildingId(Unit $unit, UnitDTO $dto): int
    {
        if ($dto->building_id !== null) {
            return $dto->building_id;
        }

        if ($dto->new_building !== null) {
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

            return Building::firstOrCreate(
                ['name' => $dto->new_building],
                $attrs
            )->id;
        }

        return $unit->building_id;
    }
}
