<?php

declare(strict_types=1);

namespace App\Src\Workflows\Application\Handlers;

use App\Src\Workflows\Application\DTOs\ProcessApprovalDTO;
use App\Src\Workflows\Domain\Entities\ApprovalRequest;
use App\Src\Workflows\Domain\Events\ApprovalRequestProcessed;
use App\Src\Workflows\Domain\Repositories\WorkflowRepositoryInterface;

final class ProcessApprovalHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $repository,
    ) {}

    public function handle(ProcessApprovalDTO $dto): ApprovalRequest
    {
        $request = $this->repository->findById($dto->approvalRequestId);

        if ($request === null) {
            throw new \RuntimeException("Approval request #{$dto->approvalRequestId} not found.");
        }

        if ($dto->action === 'approved') {
            $request->approve($dto->approverId, $dto->comment);
        } elseif ($dto->action === 'rejected') {
            $request->reject($dto->approverId, $dto->comment ?? 'Sin motivo especificado.');
        } else {
            throw new \InvalidArgumentException("Invalid action: {$dto->action}");
        }

        $saved = $this->repository->save($request);

        event(new ApprovalRequestProcessed($saved, $dto->action, $dto->approverId));

        return $saved;
    }
}
