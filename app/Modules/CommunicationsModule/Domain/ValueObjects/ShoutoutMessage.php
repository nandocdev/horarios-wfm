<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class ShoutoutMessage
{
    public function __construct(
        private ContentBody $message,
        private PersonId $employeeId,
    ) {}

    public function message(): ContentBody
    {
        return $this->message;
    }

    public function employeeId(): PersonId
    {
        return $this->employeeId;
    }

    public function extractUsernames(): array
    {
        return $this->message->extractUsernames();
    }
}
