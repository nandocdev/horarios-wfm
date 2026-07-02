<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Domain\Entities;

final class Category
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly ?string $description,
    ) {}

    public static function create(string $name, ?string $description = null): self
    {
        return new self(null, $name, $description);
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
}
