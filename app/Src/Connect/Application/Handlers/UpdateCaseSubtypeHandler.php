<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CaseSubtypeDTO;
use App\Src\Connect\Domain\Entities\CaseSubtype;
use App\Src\Connect\Domain\Repositories\CaseSubtypeRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class UpdateCaseSubtypeHandler
{
    public function __construct(
        private CaseSubtypeRepositoryInterface $repository,
    ) {}

    public function handle(int $id, CaseSubtypeDTO $dto): CaseSubtype
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new \InvalidArgumentException("Case subtype {$id} not found.");
        }

        $subtype = new CaseSubtype(
            id: $id,
            name: $dto->name,
            description: $dto->description,
            isActive: $dto->isActive,
        );

        $saved = $this->repository->save($subtype);

        Log::info('Case subtype updated.', [
            'subtype_id' => $id,
            'name' => $dto->name,
        ]);

        return $saved;
    }
}
