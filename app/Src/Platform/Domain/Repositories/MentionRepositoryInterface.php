<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Mention;

interface MentionRepositoryInterface {
    public function save(Mention $mention): Mention;

    public function findById(int $id): ?Mention;

    public function findByMentionedUser(int $userId, int $perPage = 25): array;

    public function findByMentioner(int $userId, int $perPage = 25): array;

    public function findUnreadByUser(int $userId, int $perPage = 25): array;

    public function findByMentionable(string $mentionableType, int $mentionableId): array;

    public function countUnreadByUser(int $userId): int;

    public function markAsRead(int $id): void;

    public function markAllAsRead(int $userId): void;

    public function delete(Mention $mention): void;
}
