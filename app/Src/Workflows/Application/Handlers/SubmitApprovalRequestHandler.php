<?php

declare(strict_types=1);

namespace App\Src\Workflows\Application\Handlers;

use App\Src\Workflows\Application\DTOs\SubmitApprovalRequestDTO;
use App\Src\Workflows\Domain\Entities\ApprovalRequest;
use App\Src\Workflows\Domain\Events\ApprovalRequestSubmitted;
use App\Src\Workflows\Domain\Repositories\WorkflowRepositoryInterface;

final class SubmitApprovalRequestHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $repository,
    ) {}

    public function handle(SubmitApprovalRequestDTO $dto): ApprovalRequest
    {
        $request = ApprovalRequest::submit(
            type: $dto->type,
            requesterId: $dto->requesterId,
            payload: $dto->payload,
            reason: $dto->reason,
            requiredLevels: $dto->requiredLevels,
        );

        $saved = $this->repository->save($request);

        event(new ApprovalRequestSubmitted($saved));

        return $saved;
    }
}
