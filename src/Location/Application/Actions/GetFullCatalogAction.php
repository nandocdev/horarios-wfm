<?php

declare(strict_types=1);

namespace Src\Location\Application\Actions;

use Illuminate\Support\Collection;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Infrastructure\Persistence\Models\Province;

final readonly class GetFullCatalogAction
{
    public function __construct(
        private LocationCatalogInterface $catalog,
    ) {}

    /**
     * @return Collection<int, Province>
     */
    public function execute(): Collection
    {
        return Province::with(['districts.townships'])
            ->orderBy('name')
            ->get();
    }
}
