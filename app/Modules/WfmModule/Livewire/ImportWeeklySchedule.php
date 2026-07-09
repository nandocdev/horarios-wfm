<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Actions\ImportTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente para importar horarios semanales desde archivos CSV.
 */
class ImportWeeklySchedule extends Component
{
    use WithFileUploads;

    public WeeklySchedule $week;

    public $csvFile;

    public array $importedData = [];

    public array $importSelectedDays = [];

    public array $availableSchedules = [];

    /**
     * Inicializar el componente y verificar permisos.
     */
    public function mount(WeeklySchedule $week): void
    {
        $this->authorize('schedules.manage');
        $this->week = $week;
        $this->availableSchedules = Schedule::where('is_active', true)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'start_time' => Carbon::parse($s->start_time)->format('H:i'),
                'end_time' => Carbon::parse($s->end_time)->format('H:i'),
            ])
            ->toArray();
    }

    /**
     * Disparado automáticamente al subir un archivo.
     */
    public function updatedCsvFile(): void
    {
        $this->processCsv();
    }

    /**
     * Procesa el archivo CSV subido y parsea su contenido.
     */
    public function processCsv(): void
    {
        $this->validate([
            'csvFile' => 'required|file|max:2048',
        ]);

        $path = $this->csvFile->getRealPath();

        $data = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
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

        $required = ['usuario', 'centro', 'jornada', 'horario', 'almuerzo', 'descanso'];
        foreach ($required as $req) {
            if (! isset($headerMap[$req])) {
                \Flux::toast("Falta la columna requerida en CSV: {$req}", variant: 'danger');

                return;
            }
        }

        // Cargar configuraciones operativas
        $settings = DB::table('operational_settings')->pluck('value', 'key');
        $shiftMinutes = (int) ($settings['default_shift_minutes'] ?? 28800) / 60; // 8 horas = 480 min
        $lunchMinutes = (int) ($settings['default_lunch_minutes'] ?? 2700) / 60;   // 45 min
        $breakMinutes = (int) ($settings['default_break_minutes'] ?? 900) / 60;   // 15 min

        // Pre-validación de usuarios
        $usernamesInCsv = array_unique(array_map(fn ($row) => strtolower(trim($row[$headerMap['usuario']] ?? '')), $data));
        $existingUsers = Employee::whereIn(DB::raw('LOWER(username)'), $usernamesInCsv)
            ->orWhereIn(DB::raw('LOWER(email)'), $usernamesInCsv)
            ->get()
            ->map(fn ($e) => [strtolower($e->username), strtolower($e->email)])
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

            $entrada = $row[$headerMap['horario']] ?? null;
            $almuerzo = $row[$headerMap['almuerzo']] ?? null;
            $descanso = $row[$headerMap['descanso']] ?? null;

            $entradaCarbon = null;
            if ($entrada && $entrada !== 'NULL' && trim($entrada) !== '') {
                try {
                    $entradaCarbon = Carbon::parse(trim($entrada));
                } catch (\Exception $e) {
                    $entradaCarbon = null;
                }
            }

            $salida = $entradaCarbon ? $entradaCarbon->copy()->addMinutes((int) $shiftMinutes)->format('H:i') : null;

            $ini_almuerzo = null;
            if ($almuerzo && $almuerzo !== 'NULL' && trim($almuerzo) !== '') {
                try {
                    $ini_almuerzo = Carbon::parse(trim($almuerzo))->format('H:i');
                } catch (\Exception $e) {
                }
            }
            $fin_almuerzo = $ini_almuerzo ? Carbon::parse($ini_almuerzo)->addMinutes((int) $lunchMinutes)->format('H:i') : null;

            $ini_descanso = null;
            if ($descanso && $descanso !== 'NULL' && trim($descanso) !== '') {
                try {
                    $ini_descanso = Carbon::parse(trim($descanso))->format('H:i');
                } catch (\Exception $e) {
                }
            }
            $fin_descanso = $ini_descanso ? Carbon::parse($ini_descanso)->addMinutes((int) $breakMinutes)->format('H:i') : null;

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

    /**
     * Hook reactivo para actualizar la hora de salida al cambiar la entrada.
     */
    public function updatedImportedData($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'entrada') {
            $index = (int) $parts[0];
            if ($value) {
                try {
                    $entradaCarbon = Carbon::parse($value);
                    $settings = DB::table('operational_settings')->pluck('value', 'key');
                    $shiftMinutes = (int) ($settings['default_shift_minutes'] ?? 28800) / 60;

                    $this->importedData[$index]['salida'] = $entradaCarbon->copy()->addMinutes($shiftMinutes)->format('H:i');
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     * Remueve una fila de los datos previsualizados.
     *
     * @param  int  $index
     */
    public function removeImportedRow($index): void
    {
        unset($this->importedData[$index]);
        $this->importedData = array_values($this->importedData);
    }

    /**
     * Aplica la importación y guarda la información en la base de datos.
     */
    public function applyImport(ImportTeamWeeklyScheduleAction $action): void
    {
        $this->validate([
            'importSelectedDays' => 'required|array|min:1',
            'importedData' => 'required|array|min:1',
        ]);

        $action->execute($this->week->id, 0, $this->importSelectedDays, $this->importedData);

        $this->importedData = [];
        $this->csvFile = null;
        $this->importSelectedDays = [];

        \Flux::toast('Horario importado y aplicado a los días seleccionados.');

        $this->redirectRoute('schedules.planning.teams', ['week' => $this->week->id], navigate: true);
    }

    public function render()
    {
        return view('wfm::livewire.import-weekly-schedule', [
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

/**
 * [RIESGOS]
 * - Carga de archivos CSV muy pesados que consuman memoria de ejecución → mitigado limitando a max:2048 KB de tamaño de archivo.
 * - Intentos de importar usuarios inexistentes → mitigado marcando en la interfaz visual a los agentes no coincidentes.
 * - Sobreescritura accidental de horarios previos → mitigado requiriendo selección manual de días específicos del periodo antes de la aplicación final.
 */
