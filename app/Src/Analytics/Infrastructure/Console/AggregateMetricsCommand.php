<?php

declare(strict_types=1);

namespace App\Src\Analytics\Infrastructure\Console;

use App\Src\Analytics\Application\Handlers\BatchAggregateHandler;
use DateTimeImmutable;
use Illuminate\Console\Command;

final class AggregateMetricsCommand extends Command
{
    protected $signature = 'analytics:aggregate {date? : Fecha (Y-m-d)} {--from=} {--all}';
    protected $description = 'Calcula y persiste métricas diarias de productividad para todos los agentes activos';

    public function handle(BatchAggregateHandler $handler): int
    {
        $date = new DateTimeImmutable($this->argument('date') ?? date('Y-m-d'));

        $this->info("Agregando métricas para fecha: {$date->format('Y-m-d')}");

        $results = $handler->handle($date);

        $ok = count(array_filter($results, fn ($r) => $r['status'] === 'ok'));
        $errors = count($results) - $ok;

        $this->info("Procesados: {$ok} ok, {$errors} errores.");

        return self::SUCCESS;
    }
}
