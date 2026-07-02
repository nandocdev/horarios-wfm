<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Domain\Repositories\CaseSubtypeRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class DeleteCaseSubtypeHandler
{
    public function __construct(
        private CaseSubtypeRepositoryInterface $repository,
    ) {}

    public function handle(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new \InvalidArgumentException("Case subtype {$id} not found.");
        }

        $this->repository->delete($id);

        Log::info('Case subtype deleted.', [
            'subtype_id' => $id,
        ]);
    }
}
