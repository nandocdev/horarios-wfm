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
                'is_active' => $dto->is_active,
            ]);

            $unit->services()->delete();
            $unit->services()->createMany($dto->services);

            return $unit;
        });
    }

    protected function resolveBuildingId(Unit $unit, UnitDTO $dto): int
    {
        if ($dto->building_id !== null) {
            return $dto->building_id;
        }

        if ($dto->new_building !== null) {
            return Building::firstOrCreate(
                ['name' => $dto->new_building],
                [
                    'director_name' => $dto->director_name,
                    'subdirector_name' => $dto->subdirector_name,
                    'administrator_name' => $dto->administrator_name,
                    'is_active' => $dto->is_active,
                ]
            )->id;
        }

        return $unit->building_id;
    }
}
