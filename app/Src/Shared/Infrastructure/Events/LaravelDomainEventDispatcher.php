<?php

declare(strict_types=1);

namespace App\Src\Shared\Infrastructure\Events;

use App\Src\Shared\Domain\Events\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

final class LaravelDomainEventDispatcher implements DomainEventDispatcher {
    public function __construct(
        private Dispatcher $laravelEvents
    ) {
    }

    public function dispatch(DomainEvent $event): void {
        $this->laravelEvents->dispatch($event);
    }

    public function dispatchMany(array $events): void {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
