<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\ValueObjects;

final class MedicalNotes
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromEncrypted(string $encrypted): self
    {
        return new self($encrypted);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
