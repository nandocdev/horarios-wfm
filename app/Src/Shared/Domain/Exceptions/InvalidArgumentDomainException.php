<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\Exceptions;

class InvalidArgumentDomainException extends DomainException {
    public function __construct(string $message = '') {
        parent::__construct($message);
    }
}
