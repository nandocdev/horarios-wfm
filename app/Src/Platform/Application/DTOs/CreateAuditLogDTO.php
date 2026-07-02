<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final class CreateAuditLogDTO {
    public function __construct(
        public readonly string $entityType,
        public readonly int|string|null $entityId,
        public readonly string $action,
        public readonly ?array $before = null,
        public readonly ?array $after = null,
        public readonly ?string $ipAddress = null,
        public readonly ?int $userId = null,
    ) {
    }
}
