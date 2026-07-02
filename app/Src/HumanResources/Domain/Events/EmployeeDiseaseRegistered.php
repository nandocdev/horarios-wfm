<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\Events;

use App\Src\HumanResources\Domain\Entities\EmployeeDisease;
use App\Src\Shared\Domain\Events\DomainEvent;

final class EmployeeDiseaseRegistered extends DomainEvent
{
    public function __construct(
        public readonly EmployeeDisease $disease,
        public readonly int $registeredByUserId,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'human_resources.employee.disease_registered';
    }
}
