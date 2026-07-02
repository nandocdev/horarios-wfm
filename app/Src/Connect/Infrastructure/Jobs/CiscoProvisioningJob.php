<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Jobs;

use App\Src\Connect\Application\DTOs\SyncEmployeeDTO;
use App\Src\Connect\Application\Handlers\SyncEmployeeWithCiscoHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Facades\Log;

class CiscoProvisioningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;
    public int $maxExceptions = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly SyncEmployeeDTO $dto,
    ) {}

    public function handle(SyncEmployeeWithCiscoHandler $handler): void
    {
        $handler->handle($this->dto);

        Log::info("CiscoProvisioningJob completed for {$this->dto->loginId}.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("CiscoProvisioningJob failed for {$this->dto->loginId} after {$this->attempts()} attempts.", [
            'login_id' => $this->dto->loginId,
            'error' => $e->getMessage(),
        ]);
    }

    public function middleware(): array
    {
        return [
            (new ThrottlesExceptions(3, 5))->backoff(2),
        ];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }
}
