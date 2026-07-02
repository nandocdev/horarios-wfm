<?php

declare(strict_types=1);

namespace App\Src\Workflows\Infrastructure\Persistence;

use App\Src\Workflows\Application\Mappers\WorkflowMapper;
use App\Src\Workflows\Domain\Entities\ApprovalRequest;
use App\Src\Workflows\Domain\Repositories\WorkflowRepositoryInterface;
use App\Src\Workflows\Domain\ValueObjects\WorkflowState;

final class EloquentWorkflowRepository implements WorkflowRepositoryInterface
{
    public function save(ApprovalRequest $request): ApprovalRequest
    {
        $eloquent = EloquentApprovalRequest::updateOrCreate(
            ['id' => $request->id()],
            WorkflowMapper::toEloquent($request),
        );

        if (! empty($request->signatures())) {
            $eloquent->signatures()->delete();

            foreach ($request->signatures() as $signature) {
                $eloquent->signatures()->create(
                    WorkflowMapper::signatureToEloquent($signature),
                );
            }
        }

        return WorkflowMapper::toDomain($eloquent->load('signatures'));
    }

    public function findById(int $id): ?ApprovalRequest
    {
        $eloquent = EloquentApprovalRequest::with('signatures')->find($id);
        return $eloquent ? WorkflowMapper::toDomain($eloquent) : null;
    }

    public function findPendingByApprover(int $approverId): array
    {
        return EloquentApprovalRequest::with('signatures')
            ->whereIn('state', [WorkflowState::PENDING, WorkflowState::L1_APPROVED, WorkflowState::L2_APPROVED])
            ->latest()
            ->get()
            ->map(fn (EloquentApprovalRequest $e) => WorkflowMapper::toDomain($e))
            ->toArray();
    }

    public function findByRequester(int $requesterId): array
    {
        return EloquentApprovalRequest::with('signatures')
            ->where('requester_id', $requesterId)
            ->latest()
            ->get()
            ->map(fn (EloquentApprovalRequest $e) => WorkflowMapper::toDomain($e))
            ->toArray();
    }

    public function findByType(string $type): array
    {
        return EloquentApprovalRequest::with('signatures')
            ->where('type', $type)
            ->latest()
            ->get()
            ->map(fn (EloquentApprovalRequest $e) => WorkflowMapper::toDomain($e))
            ->toArray();
    }
}
