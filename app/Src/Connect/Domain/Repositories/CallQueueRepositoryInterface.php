<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\CallQueue;

interface CallQueueRepositoryInterface
{
    public function save(CallQueue $queue): CallQueue;
    public function findById(int $id): ?CallQueue;
    public function findByName(string $name): ?CallQueue;
    public function findAll(): array;
    public function findAllActive(): array;
    public function delete(int $id): void;
}
