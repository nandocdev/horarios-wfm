<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Entities;

use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;

final class PollResponse
{
    private ?int $id = null;

    public function __construct(
        private ?int $pollId,
        private PersonId $userId,
        private string $answer,
    ) {}

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function setPollId(int $pollId): void
    {
        $this->pollId = $pollId;
    }

    public function userId(): PersonId
    {
        return $this->userId;
    }

    public function answer(): string
    {
        return $this->answer;
    }
}
