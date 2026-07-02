<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Events;

use App\Src\Identity\Domain\Entities\Role;
use App\Src\Shared\Domain\Events\DomainEvent;

final class RoleCreated extends DomainEvent
{
    public function __construct(
        public readonly Role $role,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'identity.role.created';
    }
}
