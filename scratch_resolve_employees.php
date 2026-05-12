<?php

use App\Modules\ConnectModule\Models\CallRecord;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lookup = app(EmployeeLookupRepositoryInterface::class);
$lookup->warmup();

$calls = CallRecord::whereNull('employee_id')->whereNotNull('raw_agent_name')->get();
$found = 0;
$total = $calls->count();

echo "Procesando {$total} llamadas sin empleado...\n";

foreach ($calls as $call) {
    $id = $lookup->resolve(null, $call->raw_agent_name);
    if ($id) {
        $call->update(['employee_id' => $id]);
        $found++;
    }
}

echo "Finalizado. Se encontraron empleados para {$found} de {$total} llamadas.\n";
