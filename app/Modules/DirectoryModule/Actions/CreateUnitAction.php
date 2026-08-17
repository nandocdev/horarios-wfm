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
                'is_active' => $dto->is_active,
            ]);

            $unit->services()->createMany($dto->services);

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
}
