<?php

declare(strict_types=1);

namespace Src\Location\Application\Actions;

use Illuminate\Support\Collection;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Application\DTOs\ProvinceData;

final readonly class ListProvincesAction
{
    public function __construct(
        private LocationCatalogInterface $catalog,
    ) {}

    /**
     * @return Collection<int, ProvinceData>
     */
    public function execute(): Collection
    {
        return $this->catalog->listProvinces();
    }
}
