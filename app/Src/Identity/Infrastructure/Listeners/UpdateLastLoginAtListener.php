<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Listeners;

use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use Illuminate\Auth\Events\Login;

final class UpdateLastLoginAtListener
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof EloquentUser) {
            return;
        }

        $user = $this->userRepository->findById($event->user->id);

        if ($user === null) {
            return;
        }

        $user->markLastLogin(new \DateTimeImmutable());

        $this->userRepository->save($user);
    }
}
