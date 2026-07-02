<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\ChatRecord;

interface ChatRecordRepositoryInterface
{
    public function save(ChatRecord $record): ChatRecord;
    public function findById(int $id): ?ChatRecord;
    public function findByConversationId(string $conversationId): ?ChatRecord;
    public function findByEmployee(int $employeeId, string $dateFrom, string $dateTo): array;
    public function bulkInsert(array $records): void;
}
