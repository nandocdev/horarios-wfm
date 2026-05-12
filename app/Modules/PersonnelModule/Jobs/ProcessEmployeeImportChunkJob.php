<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Jobs;

use App\Modules\PersonnelModule\Actions\ProcessEmployeeImportChunkAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmployeeImportChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, string|null>>  $rows
     */
    public function __construct(
        public string $importBatchId,
        public array $rows,
        public int $startRow,
    ) {}

    public function handle(ProcessEmployeeImportChunkAction $action): void
    {
        $action->execute($this->importBatchId, $this->rows, $this->startRow);
    }
}
