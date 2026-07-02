<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\ValueObjects;

final readonly class IpAddress
{
    public function __construct(
        private ?string $value
    ) {
        if ($this->value !== null && filter_var($this->value, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException("Invalid IP address: {$this->value}");
        }
    }

    public function value(): ?string
    {
        return $this->value;
    }
}
