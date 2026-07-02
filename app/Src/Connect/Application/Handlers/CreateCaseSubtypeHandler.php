<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CaseSubtypeDTO;
use App\Src\Connect\Domain\Entities\CaseSubtype;
use App\Src\Connect\Domain\Repositories\CaseSubtypeRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class CreateCaseSubtypeHandler
{
    public function __construct(
        private CaseSubtypeRepositoryInterface $repository,
    ) {}

    public function handle(CaseSubtypeDTO $dto): CaseSubtype
    {
        $subtype = new CaseSubtype(
            id: null,
            name: $dto->name,
            description: $dto->description,
            isActive: $dto->isActive,
        );

        $saved = $this->repository->save($subtype);

        Log::info('Case subtype created.', [
            'subtype_id' => $saved->id(),
            'name' => $dto->name,
        ]);

        return $saved;
    }
}
