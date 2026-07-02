<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Console;

use App\Src\Connect\Application\Handlers\FetchAgentDetailHandler;
use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class TestCuicAgentDetailCommand extends Command
{
    protected $signature = 'connect:cuic:test-agent-detail {employeeId}';
    protected $description = 'Comando de prueba para depurar el detalle de agente CUIC';

    public function __construct(
        private readonly CuicIntegrationInterface $cuic,
        private readonly FetchAgentDetailHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $employeeId = $this->argument('employeeId');

        $this->info("Probando detalle de agente para employee ID: {$employeeId}");

        // Test connection
        $connected = $this->cuic->testConnection();
        $this->line("Conexión CUIC: " . ($connected ? '<info>OK</info>' : '<error>FALLÓ</error>'));

        if (! $connected) {
            return self::FAILURE;
        }

        // List reports
        $reports = $this->cuic->listReports();
        $this->info('Reportes disponibles:');
        foreach ($reports as $key => $id) {
            $this->line("  {$key}: {$id}");
        }

        // Test agent detail
        $dateFrom = CarbonImmutable::now()->subHour()->format('Y-m-d H:i:s');
        $dateTo = CarbonImmutable::now()->format('Y-m-d H:i:s');

        $this->info("Consultando detalle de agente desde {$dateFrom} hasta {$dateTo}...");

        try {
            $data = $this->cuic->executeAgentDetailReport((string) $employeeId, $dateFrom, $dateTo);
            $this->info("Registros obtenidos: " . count($data));

            if (! empty($data)) {
                $this->table(array_keys($data[0]), array_slice($data, 0, 5));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
