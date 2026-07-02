<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\DTOs;

final readonly class OrganizationStructureDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            type: $data['type'],
        );
    }
}
