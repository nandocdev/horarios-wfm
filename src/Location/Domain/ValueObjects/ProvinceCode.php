<?php

declare(strict_types=1);

namespace Src\Location\Domain\ValueObjects;

use Src\Location\Domain\Exceptions\InvalidProvinceCodeException;

final readonly class ProvinceCode
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(?string $code): ?self
    {
        if ($code === null || $code === '') {
            return null;
        }

        $normalized = strtoupper(trim($code));

        if (! preg_match('/^[A-Z]{2}$/', $normalized)) {
            throw InvalidProvinceCodeException::invalid($code);
        }

        return new self($normalized);
    }

    public static function tryFromString(?string $code): ?self
    {
        try {
            return self::fromString($code);
        } catch (InvalidProvinceCodeException) {
            return null;
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
