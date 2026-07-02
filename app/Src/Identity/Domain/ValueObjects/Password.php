<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\ValueObjects;

use App\Src\Identity\Domain\Services\PasswordHasherInterface;

final class Password
{
    private function __construct(
        private string $hashedValue,
    ) {}

    public static function fromPlainText(string $plain, PasswordHasherInterface $hasher): self
    {
        if (strlen($plain) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long.');
        }

        if (! preg_match('/[A-Z]/', $plain)) {
            throw new \InvalidArgumentException('Password must contain at least one uppercase letter.');
        }

        if (! preg_match('/[a-z]/', $plain)) {
            throw new \InvalidArgumentException('Password must contain at least one lowercase letter.');
        }

        if (! preg_match('/[0-9]/', $plain)) {
            throw new \InvalidArgumentException('Password must contain at least one number.');
        }

        return new self($hasher->hash($plain));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(string $plain, PasswordHasherInterface $hasher): bool
    {
        return $hasher->verify($plain, $this->hashedValue);
    }

    public function hashedValue(): string
    {
        return $this->hashedValue;
    }

    public function needsRehash(PasswordHasherInterface $hasher): bool
    {
        return $hasher->needsRehash($this->hashedValue);
    }
}
