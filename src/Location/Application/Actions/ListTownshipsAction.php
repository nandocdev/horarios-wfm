<?php

declare(strict_types=1);

namespace Src\Location\Application\Actions;

use Illuminate\Support\Collection;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Application\DTOs\TownshipData;

final readonly class ListTownshipsAction
{
    public function __construct(
        private LocationCatalogInterface $catalog,
    ) {}

    /**
     * @return Collection<int, TownshipData>
     */
    public function execute(int $districtId): Collection
    {
        return $this->catalog->listTownships($districtId);
    }
}
