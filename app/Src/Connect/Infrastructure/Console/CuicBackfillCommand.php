<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\DTOs\CuicBackfillDTO;
use App\Src\Connect\Application\Handlers\SyncCuicDataForDateRangeHandler;
use Illuminate\Console\Command;

final class CuicBackfillCommand extends Command
{
    protected $signature = 'connect:cuic:backfill {--from=} {--to=}';
    protected $description = 'Realiza backfill de datos CUIC para un rango de fechas';

    private const DEFAULT_REPORT_TYPES = [
        'inbound_calls',
        'agent_state_transitions',
        'agent_call_performance',
    ];

    public function __construct(
        private readonly SyncCuicDataForDateRangeHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (! $from || ! $to) {
            $this->error('Los parámetros --from y --to son requeridos (formato Y-m-d).');
            return self::FAILURE;
        }

        $dto = new CuicBackfillDTO(
            dateFrom: $from,
            dateTo: $to,
            reportTypes: self::DEFAULT_REPORT_TYPES,
        );

        $this->info("Iniciando backfill desde {$from} hasta {$to}...");

        try {
            $results = $this->handler->handle($dto);

            $this->info('Backfill completed:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Days processed', $results['total_days']],
                    ['Total records', $results['total_records']],
                    ['Errors', $results['errors']],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backfill failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
