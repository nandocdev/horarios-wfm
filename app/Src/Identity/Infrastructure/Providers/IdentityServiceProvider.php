<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Providers;

use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use App\Src\Identity\Infrastructure\Services\BcryptPasswordHasher;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PasswordHasherInterface::class, BcryptPasswordHasher::class);

        $this->app->bind(UserRepositoryInterface::class, function () {
            throw new \RuntimeException(
                'UserRepositoryInterface binding not yet implemented. ' .
                'Expected to be bound in Sprint 3 with EloquentUserRepository.'
            );
        });
    }
}
