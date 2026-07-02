<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Events;

use App\Src\Identity\Domain\Entities\User;
use App\Src\Shared\Domain\Events\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        public readonly User $user,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'identity.user.created';
    }
}
