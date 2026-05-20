<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Console\Commands;

use App\Modules\OperationsModule\Actions\ReconcileEmployeeAttendanceAction;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcileAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'operations:reconcile-attendance {date? : Fecha a reconciliar (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcilia la asistencia de los empleados contra la programación y genera incidentes.';

    /**
     * Execute the console command.
     */
    public function handle(ReconcileEmployeeAttendanceAction $reconcileAction): int
    {
        $dateStr = $this->argument('date') ?: now()->subDay()->toDateString();
        $date = Carbon::parse($dateStr);

        $this->info("Iniciando reconciliación de asistencia para el día: {$date->toDateString()}");

        $employees = Employee::where('is_active', true)->get();
        $bar = $this->output->createProgressBar($employees->count());

        $stats = ['LATE' => 0, 'ABSENT' => 0];

        foreach ($employees as $employee) {
            try {
                $incidents = $reconcileAction->execute($employee, $date);
                foreach ($incidents as $type) {
                    $stats[$type]++;
                }
            } catch (\Exception $e) {
                $this->error("\nError procesando empleado {$employee->full_name}: ".$e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(['Tipo', 'Cantidad'], [
            ['Tardanzas', $stats['LATE']],
            ['Ausencias', $stats['ABSENT']],
        ]);

        return self::SUCCESS;
    }
}
