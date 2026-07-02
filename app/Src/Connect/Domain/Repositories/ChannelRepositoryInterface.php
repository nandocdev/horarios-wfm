<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\Channel;

interface ChannelRepositoryInterface
{
    public function save(Channel $channel): Channel;
    public function findById(string $id): ?Channel;
    public function findByName(string $name): ?Channel;
    public function findAll(): array;
    public function findAllActive(): array;
    public function delete(string $id): void;
}
