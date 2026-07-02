<?php

declare(strict_types=1);

namespace App\Src\Workflows\Application\Mappers;

use App\Src\Workflows\Domain\Entities\ApprovalRequest;
use App\Src\Workflows\Domain\Entities\ApprovalSignature;
use App\Src\Workflows\Domain\ValueObjects\WorkflowState;
use App\Src\Workflows\Infrastructure\Persistence\EloquentApprovalRequest;
use App\Src\Workflows\Infrastructure\Persistence\EloquentApprovalSignature;
use DateTimeImmutable;

final class WorkflowMapper
{
    public static function toDomain(EloquentApprovalRequest $e, array $signatures = []): ApprovalRequest
    {
        return new ApprovalRequest(
            id: $e->id,
            type: $e->type,
            requesterId: $e->requester_id,
            payload: $e->payload ?? [],
            state: new WorkflowState($e->state ?? WorkflowState::PENDING),
            reason: $e->reason,
            rejectionReason: $e->rejection_reason,
            requiredLevels: (int) ($e->required_levels ?? 1),
            signatures: $signatures ?: self::signaturesToDomain($e),
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    public static function toEloquent(ApprovalRequest $r): array
    {
        return [
            'type' => $r->type(),
            'requester_id' => $r->requesterId(),
            'payload' => $r->payload(),
            'state' => $r->state()->value(),
            'reason' => $r->reason(),
            'rejection_reason' => $r->rejectionReason(),
            'required_levels' => $r->requiredLevels(),
        ];
    }

    public static function signatureToDomain(EloquentApprovalSignature $e): ApprovalSignature
    {
        return new ApprovalSignature(
            id: $e->id,
            approvalRequestId: $e->approval_request_id,
            approverId: $e->approver_id,
            action: $e->action,
            comment: $e->comment,
            level: (int) ($e->level ?? 1),
            signedAt: new DateTimeImmutable($e->created_at ?? 'now'),
        );
    }

    public static function signatureToEloquent(ApprovalSignature $s): array
    {
        return [
            'approval_request_id' => $s->approvalRequestId(),
            'approver_id' => $s->approverId(),
            'action' => $s->action(),
            'comment' => $s->comment(),
            'level' => $s->level(),
        ];
    }

    private static function signaturesToDomain(EloquentApprovalRequest $e): array
    {
        if (! $e->relationLoaded('signatures')) return [];

        return $e->signatures
            ->map(fn (EloquentApprovalSignature $s) => self::signatureToDomain($s))
            ->toArray();
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
