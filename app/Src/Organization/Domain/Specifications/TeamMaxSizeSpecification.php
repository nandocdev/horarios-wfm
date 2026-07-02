<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Specifications;

use App\Src\Organization\Domain\Entities\Team;

final class TeamMaxSizeSpecification
{
    private int $maxSize;

    public function __construct(int $maxSize = 20)
    {
        $this->maxSize = $maxSize;
    }

    public function isSatisfiedBy(Team $team): bool
    {
        return $team->memberCount() < $this->maxSize;
    }

    public function message(): string
    {
        return "El equipo no puede tener más de {$this->maxSize} agentes.";
    }
}
