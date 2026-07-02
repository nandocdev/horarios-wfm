<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class Slug
{
    public function __construct(
        private string $value
    ) {
        if (preg_match('/^[a-z0-9-]+$/', $this->value) !== 1) {
            throw new \InvalidArgumentException("Invalid slug: {$this->value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
