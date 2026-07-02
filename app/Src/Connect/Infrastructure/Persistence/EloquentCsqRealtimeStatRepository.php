<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\CsqRealtimeStat;
use App\Src\Connect\Domain\Repositories\CsqRealtimeStatRepositoryInterface;

final class EloquentCsqRealtimeStatRepository implements CsqRealtimeStatRepositoryInterface
{
    public function save(CsqRealtimeStat $stat): CsqRealtimeStat
    {
        $data = ConnectMapper::csqRealtimeStatToEloquent($stat);

        $eloquent = EloquentCsqRealtimeStat::create($data);

        return ConnectMapper::csqRealtimeStatToDomain($eloquent->fresh());
    }

    public function findByCsqName(string $csqName): ?CsqRealtimeStat
    {
        $eloquent = EloquentCsqRealtimeStat::where('csq_name', $csqName)
            ->orderByDesc('created_at')
            ->first();

        return $eloquent ? ConnectMapper::csqRealtimeStatToDomain($eloquent) : null;
    }

    public function findAll(): array
    {
        return EloquentCsqRealtimeStat::orderByDesc('created_at')
            ->get()
            ->map(fn (EloquentCsqRealtimeStat $e) => ConnectMapper::csqRealtimeStatToDomain($e))
            ->toArray();
    }

    public function deleteOlderThan(string $date): int
    {
        return EloquentCsqRealtimeStat::where('created_at', '<', $date)->delete();
    }
}
