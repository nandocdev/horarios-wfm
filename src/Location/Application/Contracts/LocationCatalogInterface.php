<?php

declare(strict_types=1);

namespace Src\Location\Application\Contracts;

use Illuminate\Support\Collection;
use Src\Location\Application\DTOs\DistrictData;
use Src\Location\Application\DTOs\ProvinceData;
use Src\Location\Application\DTOs\TownshipData;

interface LocationCatalogInterface
{
    /**
     * @return Collection<int, ProvinceData>
     */
    public function listProvinces(): Collection;

    /**
     * @return Collection<int, DistrictData>
     */
    public function listDistricts(int $provinceId): Collection;

    /**
     * @return Collection<int, TownshipData>
     */
    public function listTownships(int $districtId): Collection;

    /**
     * @return Collection<int, ProvinceData> provincias con districts.townships cargados como DTOs anidados no, colección plana para index
     */
    public function getFullCatalog(): Collection;

    public function findTownship(int $townshipId): ?TownshipData;

    public function findDistrict(int $districtId): ?DistrictData;

    public function findProvince(int $provinceId): ?ProvinceData;
}
