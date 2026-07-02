<?php

declare(strict_types=1);

namespace App\Src\Shared\Infrastructure\Events;

use App\Src\Shared\Domain\Events\DomainEvent;

interface DomainEventDispatcher {
    public function dispatch(DomainEvent $event): void;

    public function dispatchMany(array $events): void;
}
