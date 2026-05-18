<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\AssignTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Actions\ImportTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Actions\UpdateEmployeeDayAssignmentAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Carbon\CarbonInterface;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TeamWeeklyPlanning extends Component
{
    use WithPagination, WithFileUploads;

    public WeeklySchedule $week;

    public Team $team;

    // Estado del modal de edición individual
    public bool $showEditModal = false;

    public ?int $selectedAssignmentId = null;

    public ?int $selectedDayOfWeek = null;

    public array $editForm = [
        'schedule_id' => null,
        'start_time' => null,
        'end_time' => null,
        'lunch_start_time' => null,
        'break_start_time' => null,
    ];

    // Estado del modal de asignación masiva
    public bool $showBulkAssignModal = false;

    public array $bulkForm = [
        'schedule_id' => null,
        'start_time' => null,
        'end_time' => null,
        'lunch_start_time' => null,
        'break_start_time' => null,
    ];

    // Importación de CSV
    public bool $showImportModal = false;
    public $csvFile;
    public array $importedData = [];
    public array $importSelectedDays = [];

    public function mount(WeeklySchedule $week, Team $team): void
    {
        $this->week = $week;
        $this->team = $team;

        // Pre-cargar valores para la asignación masiva desde la asignación de equipo (Lunes como base)
        $teamAssignment = WeeklyTeamAssignment::where([
            'weekly_schedule_id' => $this->week->id,
            'team_id' => $this->team->id,
            'day_of_week' => 1,
        ])->first();

        if ($teamAssignment) {
            $this->bulkForm = [
                'schedule_id' => $teamAssignment->schedule_id,
                'start_time' => $this->formatTime($teamAssignment->start_time),
                'end_time' => $this->formatTime($teamAssignment->end_time),
                'lunch_start_time' => $this->formatTime($teamAssignment->lunch_start_time),
                'break_start_time' => $this->formatTime($teamAssignment->break_start_time),
            ];
        }
    }

    /**
     * Formatea un tiempo (Carbon o string) a H:i.
     */
    private function formatTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }

        if (is_string($value)) {
            return substr($value, 0, 5);
        }

        return null;
    }

    public function editAssignment(int $assignmentId): void
    {
        $assignment = WeeklyScheduleAssignment::with('schedule')->findOrFail($assignmentId);
        $this->selectedAssignmentId = $assignmentId;
        $this->selectedDayOfWeek = $assignment->day_of_week;
        $this->editForm = [
            'schedule_id' => $assignment->schedule_id,
            'start_time' => $this->formatTime($assignment->start_time) ?? ($assignment->schedule ? $this->formatTime($assignment->schedule->start_time) : null),
            'end_time' => $this->formatTime($assignment->end_time) ?? ($assignment->schedule ? $this->formatTime($assignment->schedule->end_time) : null),
            'lunch_start_time' => $this->formatTime($assignment->lunch_start_time),
            'break_start_time' => $this->formatTime($assignment->break_start_time),
        ];
        $this->showEditModal = true;
    }

    /**
     * Hook reactivo para actualizar entrada/salida al cambiar el turno.
     */
    public function updatedEditFormScheduleId($value): void
    {
        if ($value) {
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->editForm['start_time'] = $this->formatTime($schedule->start_time);
                $this->editForm['end_time'] = $this->formatTime($schedule->end_time);
            }
        }
    }

    public function saveAssignment(UpdateEmployeeDayAssignmentAction $action): void
    {
        if (! $this->selectedAssignmentId) {
            return;
        }

        $action->execute($this->selectedAssignmentId, $this->editForm);

        $this->showEditModal = false;
        \Flux::toast('Asignación individual actualizada.');
    }

    /**
     * Hook reactivo para actualizar entrada/salida al cambiar el turno en asignación masiva.
     */
    public function updatedBulkFormScheduleId($value): void
    {
        if ($value) {
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->bulkForm['start_time'] = $this->formatTime($schedule->start_time);
                $this->bulkForm['end_time'] = $this->formatTime($schedule->end_time);
            }
        }
    }

    public function bulkAssign(AssignTeamWeeklyScheduleAction $action): void
    {
        if (! $this->bulkForm['schedule_id']) {
            \Flux::toast('Debe seleccionar un turno.', variant: 'danger');

            return;
        }

        $action->execute(
            $this->week->id,
            $this->team->id,
            (int) $this->bulkForm['schedule_id'],
            $this->bulkForm['lunch_start_time'],
            $this->bulkForm['break_start_time'],
            $this->bulkForm['start_time'],
            $this->bulkForm['end_time']
        );

        $this->showBulkAssignModal = false;
        \Flux::toast('Asignación masiva completada.');
    }

    public function updatedCsvFile()
    {
        $this->processCsv();
    }

    public function processCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|max:2048',
        ]);

        $path = $this->csvFile->getRealPath();
        
        $data = [];
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        if (count($data) < 2) {
            \Flux::toast('El archivo está vacío o no es válido.', variant: 'danger');
            return;
        }

        $header = array_shift($data);
        $headerMap = [];
        foreach ($header as $index => $col) {
            $headerMap[trim(strtolower($col))] = $index;
        }

        $required = ['usuario', 'jornada', 'entrada', 'almuerzo', 'descanso'];
        foreach ($required as $req) {
            if (!isset($headerMap[$req])) {
                \Flux::toast("Falta la columna requerida en CSV: {$req}", variant: 'danger');
                return;
            }
        }

        $this->importedData = [];
        foreach ($data as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $entrada = $row[$headerMap['entrada']] ?? null;
            $almuerzo = $row[$headerMap['almuerzo']] ?? null;
            $descanso = $row[$headerMap['descanso']] ?? null;
            
            $entradaCarbon = null;
            if ($entrada && $entrada !== 'NULL' && trim($entrada) !== '') {
                try {
                    $entradaCarbon = \Carbon\Carbon::parse(trim($entrada));
                } catch (\Exception $e) {
                    $entradaCarbon = null;
                }
            }
            
            // Asumiendo turno de 8 horas + 45m almuerzo + 15m descanso = 9 horas total? 
            // O 8 horas total de jornada. La instrucción indica "turno corresponde a 8 horas".
            $salida = $entradaCarbon ? $entradaCarbon->copy()->addHours(8)->format('H:i') : null;
            
            $ini_almuerzo = null;
            if ($almuerzo && $almuerzo !== 'NULL' && trim($almuerzo) !== '') {
                try {
                    $ini_almuerzo = \Carbon\Carbon::parse(trim($almuerzo))->format('H:i');
                } catch (\Exception $e) {}
            }
            $fin_almuerzo = $ini_almuerzo ? \Carbon\Carbon::parse($ini_almuerzo)->addMinutes(45)->format('H:i') : null;

            $ini_descanso = null;
            if ($descanso && $descanso !== 'NULL' && trim($descanso) !== '') {
                try {
                    $ini_descanso = \Carbon\Carbon::parse(trim($descanso))->format('H:i');
                } catch (\Exception $e) {}
            }
            $fin_descanso = $ini_descanso ? \Carbon\Carbon::parse($ini_descanso)->addMinutes(15)->format('H:i') : null;

            $this->importedData[] = [
                'id' => uniqid(),
                'usuario' => trim($row[$headerMap['usuario']] ?? ''),
                'jornada' => trim($row[$headerMap['jornada']] ?? ''),
                'entrada' => $entradaCarbon ? $entradaCarbon->format('H:i') : null,
                'salida' => $salida,
                'ini_almuerzo' => $ini_almuerzo,
                'fin_almuerzo' => $fin_almuerzo,
                'ini_descanso' => $ini_descanso,
                'fin_descanso' => $fin_descanso,
            ];
        }
    }

    public function removeImportedRow($index)
    {
        unset($this->importedData[$index]);
        $this->importedData = array_values($this->importedData);
    }

    public function applyImport(ImportTeamWeeklyScheduleAction $action)
    {
        $this->validate([
            'importSelectedDays' => 'required|array|min:1',
            'importedData' => 'required|array|min:1',
        ]);

        $action->execute($this->week->id, $this->team->id, $this->importSelectedDays, $this->importedData);

        $this->showImportModal = false;
        $this->importedData = [];
        $this->csvFile = null;
        $this->importSelectedDays = [];

        \Flux::toast('Horario importado y aplicado a los días seleccionados.');
    }

    public function render()
    {
        // Obtener empleados del equipo con su posición cargada
        $employees = $this->team->users()->with('position')->orderBy('employees.first_name')->paginate(20);

        // Obtener todas las asignaciones de la semana para estos empleados
        $assignments = WeeklyScheduleAssignment::where('weekly_schedule_id', $this->week->id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->with('schedule')
            ->get()
            ->groupBy('employee_id');

        // Obtener excepciones para el rango de la semana
        $exceptions = ScheduleException::whereIn('employee_id', $employees->pluck('id'))
            ->where(function ($query) {
                $query->whereBetween('start_at', [$this->week->week_start_date, $this->week->week_end_date])
                    ->orWhereBetween('end_at', [$this->week->week_start_date, $this->week->week_end_date])
                    ->orWhere(function ($q) {
                        $q->where('start_at', '<=', $this->week->week_start_date)
                            ->where('end_at', '>=', $this->week->week_end_date);
                    });
            })
            ->with('reason')
            ->get()
            ->groupBy('employee_id');

        return view('wfm::livewire.team-weekly-planning', [
            'employees' => $employees,
            'assignmentsByEmployee' => $assignments,
            'exceptionsByEmployee' => $exceptions,
            'schedules' => Schedule::where('is_active', true)->orderBy('name')->get(),
            'days' => [
                1 => __('Lunes'),
                2 => __('Martes'),
                3 => __('Miércoles'),
                4 => __('Jueves'),
                5 => __('Viernes'),
                6 => __('Sábado'),
                7 => __('Domingo'),
            ],
        ]);
    }
}

