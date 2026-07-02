<?php

declare(strict_types=1);

namespace App\Src\Shared\Domain\Repositories;

interface RepositoryInterface {
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;
}
