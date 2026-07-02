<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\Events;

use DateTimeImmutable;

abstract class DomainEvent {
    private string $eventId;
    private DateTimeImmutable $occurredOn;

    public function __construct() {
        $this->eventId = bin2hex(random_bytes(16));
        $this->occurredOn = new DateTimeImmutable();
    }

    public function eventId(): string {
        return $this->eventId;
    }

    public function occurredOn(): DateTimeImmutable {
        return $this->occurredOn;
    }

    abstract public function eventName(): string;
}
