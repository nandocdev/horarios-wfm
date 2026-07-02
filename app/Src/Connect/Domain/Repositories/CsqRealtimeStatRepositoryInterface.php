<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\CsqRealtimeStat;

interface CsqRealtimeStatRepositoryInterface
{
    public function save(CsqRealtimeStat $stat): CsqRealtimeStat;
    public function findByCsqName(string $csqName): ?CsqRealtimeStat;
    public function findAll(): array;
    public function deleteOlderThan(string $date): int;
}
