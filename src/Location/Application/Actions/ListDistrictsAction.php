<?php

declare(strict_types=1);

namespace Src\Location\Application\Actions;

use Illuminate\Support\Collection;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Application\DTOs\DistrictData;

final readonly class ListDistrictsAction
{
    public function __construct(
        private LocationCatalogInterface $catalog,
    ) {}

    /**
     * @return Collection<int, DistrictData>
     */
    public function execute(int $provinceId): Collection
    {
        return $this->catalog->listDistricts($provinceId);
    }
}
