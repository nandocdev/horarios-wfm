<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

final class Channel
{
    public function __construct(
        private readonly ?string $id,
        private readonly string $name,
        private readonly string $type,
        private readonly bool $isActive,
    ) {}

    public function id(): ?string { return $this->id; }
    public function name(): string { return $this->name; }
    public function type(): string { return $this->type; }
    public function isActive(): bool { return $this->isActive; }
}
