<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\AssignTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Actions\ImportTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Illuminate\Support\Facades\DB;
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
            $this->teamStart[$assignment->team_id] = $this->formatTime($assignment->start_time);
            $this->teamEnd[$assignment->team_id] = $this->formatTime($assignment->end_time);
            $this->teamLunch[$assignment->team_id] = $this->formatTime($assignment->lunch_start_time);
            $this->teamBreak[$assignment->team_id] = $this->formatTime($assignment->break_start_time);
        }
    }

    private function formatTime($time): ?string
    {
        if (! $time) {
            return null;
        }

        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return substr((string) $time, 0, 5);
    }

    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'teamSchedules.') && $value) {
            $teamId = explode('.', $property)[1];
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->teamStart[$teamId] = $this->formatTime($schedule->start_time);
                $this->teamEnd[$teamId] = $this->formatTime($schedule->end_time);
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

        // Cargar configuraciones operativas
        $settings = DB::table('operational_settings')->pluck('value', 'key');
        $shiftMinutes = (int) ($settings['default_shift_minutes'] ?? 28800) / 60;
        $lunchMinutes = (int) ($settings['default_lunch_minutes'] ?? 2700) / 60;
        $breakMinutes = (int) ($settings['default_break_minutes'] ?? 900) / 60;

        // Pre-validación de usuarios
        $usernamesInCsv = array_unique(array_map(fn($row) => strtolower(trim($row[$headerMap['usuario']] ?? '')), $data));
        $existingUsers = Employee::whereIn(DB::raw('LOWER(username)'), $usernamesInCsv)
            ->orWhereIn(DB::raw('LOWER(email)'), $usernamesInCsv)
            ->get()
            ->map(fn($e) => [strtolower($e->username), strtolower($e->email)])
            ->flatten()
            ->unique()
            ->toArray();

        $this->importedData = [];
        foreach ($data as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $usuario = trim($row[$headerMap['usuario']] ?? '');
            $userExists = in_array(strtolower($usuario), $existingUsers);

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
            
            $salida = $entradaCarbon ? $entradaCarbon->copy()->addMinutes((int) $shiftMinutes)->format('H:i') : null;
            
            $ini_almuerzo = null;
            if ($almuerzo && $almuerzo !== 'NULL' && trim($almuerzo) !== '') {
                try {
                    $ini_almuerzo = \Carbon\Carbon::parse(trim($almuerzo))->format('H:i');
                } catch (\Exception $e) {}
            }
            $fin_almuerzo = $ini_almuerzo ? \Carbon\Carbon::parse($ini_almuerzo)->addMinutes((int) $lunchMinutes)->format('H:i') : null;

            $ini_descanso = null;
            if ($descanso && $descanso !== 'NULL' && trim($descanso) !== '') {
                try {
                    $ini_descanso = \Carbon\Carbon::parse(trim($descanso))->format('H:i');
                } catch (\Exception $e) {}
            }
            $fin_descanso = $ini_descanso ? \Carbon\Carbon::parse($ini_descanso)->addMinutes((int) $breakMinutes)->format('H:i') : null;

            $this->importedData[] = [
                'id' => uniqid(),
                'usuario' => $usuario,
                'user_exists' => $userExists,
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
