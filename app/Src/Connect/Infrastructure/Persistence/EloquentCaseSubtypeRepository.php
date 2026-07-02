<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\CaseSubtype;
use App\Src\Connect\Domain\Repositories\CaseSubtypeRepositoryInterface;

final class EloquentCaseSubtypeRepository implements CaseSubtypeRepositoryInterface
{
    public function save(CaseSubtype $subtype): CaseSubtype
    {
        $data = ConnectMapper::caseSubtypeToEloquent($subtype);

        if ($subtype->id() !== null) {
            $eloquent = EloquentCaseSubtype::findOrFail($subtype->id());
            $eloquent->update($data);
        } else {
            $eloquent = EloquentCaseSubtype::create($data);
        }

        return ConnectMapper::caseSubtypeToDomain($eloquent->fresh());
    }

    public function findById(int $id): ?CaseSubtype
    {
        $eloquent = EloquentCaseSubtype::find($id);
        return $eloquent ? ConnectMapper::caseSubtypeToDomain($eloquent) : null;
    }

    public function findAll(): array
    {
        return EloquentCaseSubtype::all()
            ->map(fn (EloquentCaseSubtype $e) => ConnectMapper::caseSubtypeToDomain($e))
            ->toArray();
    }

    public function findAllActive(): array
    {
        return EloquentCaseSubtype::where('is_active', true)
            ->get()
            ->map(fn (EloquentCaseSubtype $e) => ConnectMapper::caseSubtypeToDomain($e))
            ->toArray();
    }

    public function delete(int $id): void
    {
        EloquentCaseSubtype::destroy($id);
    }
}
