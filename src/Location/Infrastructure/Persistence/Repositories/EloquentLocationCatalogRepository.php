<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Collection;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Application\DTOs\DistrictData;
use Src\Location\Application\DTOs\ProvinceData;
use Src\Location\Application\DTOs\TownshipData;
use Src\Location\Infrastructure\Persistence\Models\District;
use Src\Location\Infrastructure\Persistence\Models\Province;
use Src\Location\Infrastructure\Persistence\Models\Township;

final readonly class EloquentLocationCatalogRepository implements LocationCatalogInterface
{
    /**
     * @return Collection<int, ProvinceData>
     */
    public function listProvinces(): Collection
    {
        return Province::orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Province $m): ProvinceData => ProvinceData::fromModel($m));
    }

    /**
     * @return Collection<int, DistrictData>
     */
    public function listDistricts(int $provinceId): Collection
    {
        return District::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'province_id', 'name'])
            ->map(fn (District $m): DistrictData => DistrictData::fromModel($m));
    }

    /**
     * @return Collection<int, TownshipData>
     */
    public function listTownships(int $districtId): Collection
    {
        return Township::where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'district_id', 'name'])
            ->map(fn (Township $m): TownshipData => TownshipData::fromModel($m));
    }

    /**
     * @return Collection<int, ProvinceData>
     */
    public function getFullCatalog(): Collection
    {
        return Province::orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Province $m): ProvinceData => ProvinceData::fromModel($m));
    }

    public function findTownship(int $townshipId): ?TownshipData
    {
        $model = Township::find($townshipId, ['id', 'district_id', 'name']);

        if ($model === null) {
            return null;
        }

        return TownshipData::fromModel($model);
    }

    public function findDistrict(int $districtId): ?DistrictData
    {
        $model = District::find($districtId, ['id', 'province_id', 'name']);

        if ($model === null) {
            return null;
        }

        return DistrictData::fromModel($model);
    }

    public function findProvince(int $provinceId): ?ProvinceData
    {
        $model = Province::find($provinceId, ['id', 'name', 'code']);

        if ($model === null) {
            return null;
        }

        return ProvinceData::fromModel($model);
    }
}
