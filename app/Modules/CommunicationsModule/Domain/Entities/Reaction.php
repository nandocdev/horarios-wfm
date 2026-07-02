<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Entities;

use App\Modules\CommunicationsModule\Domain\Enums\ReactionType;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;

final class Reaction
{
    private ?int $id = null;

    public function __construct(
        private ?int $shoutoutId,
        private PersonId $userId,
        private ReactionType $type,
        private bool $isActive = true,
    ) {}

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function setShoutoutId(int $shoutoutId): void
    {
        $this->shoutoutId = $shoutoutId;
    }

    public function userId(): PersonId
    {
        return $this->userId;
    }

    public function type(): ReactionType
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
