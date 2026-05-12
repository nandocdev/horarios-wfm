<?php

use App\Modules\ConnectModule\Actions\GetEmployeePerformanceAction;
use App\Modules\EmployeesModule\Models\Employee;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employee = Employee::where('username', 'avelez')->first();
if (!$employee) {
    echo "Employee not found\n";
    exit;
}
$date = Carbon::parse('2026-01-07');

$action = new GetEmployeePerformanceAction();
$result = $action->execute($employee, $date);

print_r($result->toArray());
