<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Services;

interface PasswordHasherInterface
{
    public function hash(string $plainText): string;

    public function verify(string $plainText, string $hashedValue): bool;

    public function needsRehash(string $hashedValue): bool;
}
