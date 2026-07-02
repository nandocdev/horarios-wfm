<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Entities;

final class ActivityType
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly string $color = '#f59e0b',
        private readonly bool $isProductive = false,
        private readonly bool $isPaid = true,
    ) {}

    public static function create(string $name, string $color = '#f59e0b', bool $isProductive = false, bool $isPaid = true): self
    {
        return new self(null, $name, $color, $isProductive, $isPaid);
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function color(): string { return $this->color; }
    public function isProductive(): bool { return $this->isProductive; }
    public function isPaid(): bool { return $this->isPaid; }
}
