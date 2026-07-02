<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class HexColor
{
    public function __construct(
        private string $value
    ) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $this->value) !== 1) {
            throw new \InvalidArgumentException("Invalid hex color: {$this->value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
