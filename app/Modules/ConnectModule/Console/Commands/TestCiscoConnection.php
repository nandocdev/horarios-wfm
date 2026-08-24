<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Console\Command;

class TestCiscoConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cisco:test {agentId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la conexión con el API de Cisco Finesse UCCX';

    /**
     * Execute the console command.
     */
    public function handle(CiscoFinesseClient $client)
    {
        $agentId = $this->argument('agentId') ?? env('UCCX_USERNAME');

        $this->info("Iniciando prueba de conexión con UCCX para el agente: {$agentId}...");

        try {
            $data = $client->getAgentInfo($agentId);

            $this->success('Conexión exitosa!');
            $this->line('Datos recibidos:');
            $this->table(['Campo', 'Valor'], $this->formatTableData($data));

        } catch (\Exception $e) {
            $this->error('Fallo en la conexión: '.$e->getMessage());
        }
    }

    protected function formatTableData(array $data): array
    {
        $rows = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $rows[] = [$key, json_encode($value)];
            } else {
                $rows[] = [$key, $value];
            }
        }

        return $rows;
    }

    protected function success($message)
    {
        $this->line("<fg=green;options=bold>{$message}</>");
    }
}
