<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\CaseSubtype;

interface CaseSubtypeRepositoryInterface
{
    public function save(CaseSubtype $subtype): CaseSubtype;
    public function findById(int $id): ?CaseSubtype;
    public function findAll(): array;
    public function findAllActive(): array;
    public function delete(int $id): void;
}
