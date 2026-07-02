<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\ValueObjects;

final readonly class SnapshotData
{
    public function __construct(
        private ?array $data
    ) {}

    public function data(): ?array
    {
        return $this->data;
    }

    public function toJson(): ?string
    {
        return $this->data !== null ? json_encode($this->data, JSON_UNESCAPED_UNICODE) : null;
    }
}
