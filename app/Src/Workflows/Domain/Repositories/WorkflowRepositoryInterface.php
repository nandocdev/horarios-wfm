<?php

declare(strict_types=1);

namespace App\Src\Workflows\Domain\Repositories;

use App\Src\Workflows\Domain\Entities\ApprovalRequest;

interface WorkflowRepositoryInterface
{
    public function save(ApprovalRequest $request): ApprovalRequest;
    public function findById(int $id): ?ApprovalRequest;
    public function findPendingByApprover(int $approverId): array;
    public function findByRequester(int $requesterId): array;
    public function findByType(string $type): array;
}
