<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\ConnectModule\Services\CuicReportService;
use Carbon\Carbon;

$cuic = app(CuicReportService::class);
$start = Carbon::now()->subMinutes(60);
$end = Carbon::now();

echo "Consultando agent_csq_detail de $start a $end...\n";
try {
    $rows = $cuic->executeReportWithFilter('agent_csq_detail', $start, $end);
    if ($rows->isEmpty()) {
        echo "No hay datos.\n";
    } else {
        foreach ($rows as $index => $row) {
            if (isset($row['resource_name']) || isset($row['agent_name'])) {
                echo "Fila con agente encontrada (Index $index):\n";
                print_r($row);
                break;
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
