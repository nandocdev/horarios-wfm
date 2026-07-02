<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\InAppNotification;
use DateTimeImmutable;

interface InAppNotificationRepositoryInterface {
    public function save(InAppNotification $notification): InAppNotification;

    public function findById(string $id): ?InAppNotification;

    public function findByUser(int $userId, int $perPage = 25): array;

    public function findUnreadByUser(int $userId, int $perPage = 25): array;

    public function countUnreadByUser(int $userId): int;

    public function markAsRead(string $id): void;

    public function markAllAsRead(int $userId): void;

    public function findByNotifiable(string $notifiableType, int $notifiableId): array;

    public function deleteExpired(): int;

    public function delete(InAppNotification $notification): void;

    public function findUserIdsByTypeAndDate(string $type, DateTimeImmutable $date): array;
}
