<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\DTOs\SyncCuicFilterDTO;
use App\Src\Connect\Application\Handlers\SyncCuicDataHandler;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class CuicSyncCommand extends Command
{
    protected $signature = 'connect:cuic:sync {--minutes=60} {--from=} {--to=}';
    protected $description = 'Sincroniza datos de CUIC (llamadas, chats, transiciones, rendimiento)';

    public function __construct(
        private readonly SyncCuicDataHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $from = $this->option('from');
        $to = $this->option('to');

        $dateFrom = $from ? CarbonImmutable::parse($from) : CarbonImmutable::now()->subMinutes($minutes);
        $dateTo = $to ? CarbonImmutable::parse($to) : CarbonImmutable::now();

        $dto = new SyncCuicFilterDTO(
            reportType: 'inbound_calls',
            dateFrom: $dateFrom->format('Y-m-d H:i:s'),
            dateTo: $dateTo->format('Y-m-d H:i:s'),
            minutes: $minutes,
        );

        try {
            $results = $this->handler->handle($dto);

            $this->info('CUIC sync completed:');
            $this->table(
                ['Type', 'Count'],
                [
                    ['Calls', $results['calls']],
                    ['Chats', $results['chats']],
                    ['Transitions', $results['transitions']],
                    ['Performances', $results['performances']],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("CUIC sync failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
