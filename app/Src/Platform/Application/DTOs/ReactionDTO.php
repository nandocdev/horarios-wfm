<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

use App\Src\Platform\Domain\ValueObjects\ReactionType;

final readonly class ReactionDTO
{
    public function __construct(
        public ReactionType $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: ReactionType::from($data['type']),
        );
    }
}
