<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\CallRecord;

interface CallRecordRepositoryInterface
{
    public function save(CallRecord $record): CallRecord;
    public function findById(int $id): ?CallRecord;
    public function findByCiscoCallId(string $ciscoCallId): ?CallRecord;
    public function findOpenByEmployee(int $employeeId): array;
    public function findOpenByQueue(int $queueId): array;
    public function findCallsByDate(string $date, ?int $queueId = null): array;
    public function update(int $id, array $data): CallRecord;
    public function delete(int $id): void;
}
