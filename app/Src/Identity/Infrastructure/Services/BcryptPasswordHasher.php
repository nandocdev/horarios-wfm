<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Services;

use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use Illuminate\Support\Facades\Hash;

final class BcryptPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainText): string
    {
        return Hash::make($plainText);
    }

    public function verify(string $plainText, string $hashedValue): bool
    {
        return Hash::check($plainText, $hashedValue);
    }

    public function needsRehash(string $hashedValue): bool
    {
        return Hash::needsRehash($hashedValue);
    }
}
