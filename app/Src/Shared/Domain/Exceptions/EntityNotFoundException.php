<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\Exceptions;

class EntityNotFoundException extends DomainException {
    public function __construct(string $entityType, int|string $identifier) {
        parent::__construct("{$entityType} with identifier '{$identifier}' not found.");
    }
}
