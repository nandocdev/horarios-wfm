<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\AssignTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Actions\ImportTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Livewire\Component;
use Livewire\WithFileUploads;

class WeeklyPlanningTeams extends Component
{
    use WithFileUploads;
    public WeeklySchedule $week;

    public array $teamSchedules = [];

    public array $teamStart = [];

    public array $teamEnd = [];

    public array $teamLunch = [];

    public array $teamBreak = [];

    // Estado para importación de CSV
    public bool $showImportModal = false;
    public $csvFile;
    public array $importedData = [];
    public array $importSelectedDays = [];

    public function mount(WeeklySchedule $week): void
    {
        $this->week = $week;
        $this->loadTeamSchedules();
    }

    public function loadTeamSchedules(): void
    {
        $this->teamSchedules = [];
        $this->teamStart = [];
        $this->teamEnd = [];
        $this->teamLunch = [];
        $this->teamBreak = [];

        $assignments = WeeklyTeamAssignment::where('weekly_schedule_id', $this->week->id)
            ->where('day_of_week', 1) // Usamos el lunes como referencia
            ->get();

        foreach ($assignments as $assignment) {
            $this->teamSchedules[$assignment->team_id] = $assignment->schedule_id;
            $this->teamStart[$assignment->team_id] = $assignment->start_time ? substr($assignment->start_time, 0, 5) : null;
            $this->teamEnd[$assignment->team_id] = $assignment->end_time ? substr($assignment->end_time, 0, 5) : null;
            $this->teamLunch[$assignment->team_id] = $assignment->lunch_start_time ? substr($assignment->lunch_start_time, 0, 5) : null;
            $this->teamBreak[$assignment->team_id] = $assignment->break_start_time ? substr($assignment->break_start_time, 0, 5) : null;
        }
    }

    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'teamSchedules.') && $value) {
            $teamId = explode('.', $property)[1];
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->teamStart[$teamId] = substr($schedule->start_time, 0, 5);
                $this->teamEnd[$teamId] = substr($schedule->end_time, 0, 5);
            }
        }
    }

    public function assignToTeam(int $teamId, AssignTeamWeeklyScheduleAction $action): void
    {
        $this->authorize('schedules.manage');

        $scheduleId = $this->teamSchedules[$teamId] ?? null;
        $startTime = $this->teamStart[$teamId] ?? null;
        $endTime = $this->teamEnd[$teamId] ?? null;
        $lunchStart = $this->teamLunch[$teamId] ?? null;
        $breakStart = $this->teamBreak[$teamId] ?? null;

        if (! $scheduleId) {
            \Flux::toast('Seleccione un turno.', variant: 'danger');

            return;
        }

        $action->execute($this->week->id, $teamId, (int) $scheduleId, $lunchStart, $breakStart, $startTime, $endTime);

        $this->loadTeamSchedules();

        \Flux::toast('Horario asignado al equipo y miembros.');
    }

    public function render()
    {
        $user = auth()->user();
        $employee = $user->employee;

        $query = Team::with('supervisor')->where('is_active', true);

        // Si no es admin/wfm, filtrar por subordinación
        if (! $user->hasRole(['admin', 'wfm', 'director']) && $employee) {
            $teamIds = $employee->getManagedTeamIds(); // Asumiendo que este helper existe en Employee
            $query->whereIn('id', $teamIds);
        }

        return view('wfm::livewire.weekly-planning-teams', [
            'teams' => $query->orderBy('name')->get(),
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

    public function updatedCsvFile(): void
    {
        $this->processCsv();
    }

    public function processCsv(): void
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

    public function removeImportedRow($index): void
    {
        unset($this->importedData[$index]);
        $this->importedData = array_values($this->importedData);
    }

    public function applyImport(ImportTeamWeeklyScheduleAction $action): void
    {
        $this->validate([
            'importSelectedDays' => 'required|array|min:1',
            'importedData' => 'required|array|min:1',
        ]);

        $action->execute($this->week->id, 0, $this->importSelectedDays, $this->importedData);

        $this->showImportModal = false;
        $this->importedData = [];
        $this->csvFile = null;
        $this->importSelectedDays = [];

        \Flux::toast('Horario importado y aplicado a los días seleccionados.');
    }
}
