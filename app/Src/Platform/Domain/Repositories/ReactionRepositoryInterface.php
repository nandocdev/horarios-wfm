<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Reaction;
use App\Src\Platform\Domain\ValueObjects\ReactionType;

interface ReactionRepositoryInterface {
    public function save(Reaction $reaction): Reaction;

    public function findById(int $id): ?Reaction;

    public function findByShoutout(int $shoutoutId, int $perPage = 25): array;

    public function findByUserAndShoutout(int $userId, int $shoutoutId): ?Reaction;

    public function countByShoutout(int $shoutoutId): int;

    public function countByType(int $shoutoutId, ReactionType $type): int;

    public function delete(Reaction $reaction): void;
}
