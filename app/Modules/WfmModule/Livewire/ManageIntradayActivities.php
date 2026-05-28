<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\AssignIntradayActivityAction;
use App\Modules\WfmModule\Actions\CreateApprovedIntradayPeriodAction;
use App\Modules\WfmModule\DTOs\IntradayActivityDTO;
use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente de gestión de actividades intradía con flujo de dos roles:
 *
 * - WFM (wfm.intraday.manage): Define y gestiona los periodos aprobados globales,
 *   controlando qué equipo, cuándo y cuántos operadores pueden salir.
 *
 * - Coordinador (wfm.intraday.assign): Solo ve los periodos aprobados para su propio equipo
 *   y asigna a sus operadores en los slots disponibles.
 *
 * [RIESGOS]
 * - Un coordinador sin team_id asignado no verá ningún periodo → must tener employee con team.
 * - La vista de periodos y la asignación están separadas por permisos, no por roles, permitiendo
 *   escalabilidad futura (ej. un supervisor con wfm.intraday.assign también puede asignar).
 */
class ManageIntradayActivities extends Component
{
    use WithPagination;

    // --- Filtros compartidos ---
    public string $date = '';

    public ?int $selectedTeamId = null;

    // --- Modal de PERIODO APROBADO (solo WFM) ---
    public bool $showPeriodModal = false;

    public ?int $periodId = null; // Para edición

    public ?int $periodTeamId = null;

    public ?int $periodActivityDefinitionId = null;

    public string $periodDate = '';

    public string $periodStartTime = '';

    public string $periodEndTime = '';

    public int $periodMaxSlots = 1;

    public string $periodNotes = '';

    // --- Modal de ASIGNACIÓN A OPERADOR (coordinador y WFM) ---
    public bool $showAssignmentModal = false;

    public ?int $assigningPeriodId = null;

    public array $selectedEmployeeIds = [];

    public string $startTime = '';

    public string $endTime = '';

    public string $assignNotes = '';

    public string $minTimeLimit = '';

    public string $maxTimeLimit = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->periodDate = now()->format('Y-m-d');
    }

    // ======================================================
    // GESTIÓN DE PERIODOS APROBADOS (solo WFM)
    // ======================================================

    public function openPeriodModal(?int $id = null): void
    {
        $this->authorize('wfm.intraday.periods.manage');

        if ($id) {
            $period = ApprovedIntradayPeriod::findOrFail($id);
            $this->periodId = $period->id;
            $this->periodTeamId = $period->team_id;
            $this->periodActivityDefinitionId = $period->activity_definition_id;
            $this->periodDate = $period->date->format('Y-m-d');
            $this->periodStartTime = $period->start_time;
            $this->periodEndTime = $period->end_time;
            $this->periodMaxSlots = $period->max_slots;
            $this->periodNotes = $period->notes ?? '';
        } else {
            $this->reset(['periodId', 'periodTeamId', 'periodActivityDefinitionId',
                'periodStartTime', 'periodEndTime', 'periodNotes']);
            $this->periodDate = $this->date;
            $this->periodMaxSlots = 1;
        }

        $this->showPeriodModal = true;
    }

    public function savePeriod(CreateApprovedIntradayPeriodAction $action): void
    {
        $this->authorize('wfm.intraday.periods.manage');

        $this->validate([
            'periodTeamId'               => 'required|exists:teams,id',
            'periodActivityDefinitionId' => 'required|exists:scheduled_activity_definitions,id',
            'periodDate'                 => 'required|date',
            'periodStartTime'            => 'required',
            'periodEndTime'              => 'required|after:periodStartTime',
            'periodMaxSlots'             => 'required|integer|min:1|max:100',
        ], [], [
            'periodTeamId'               => 'Equipo',
            'periodActivityDefinitionId' => 'Actividad',
            'periodDate'                 => 'Fecha',
            'periodStartTime'            => 'Hora de inicio',
            'periodEndTime'              => 'Hora de fin',
            'periodMaxSlots'             => 'Nº de slots',
        ]);

        try {
            if ($this->periodId) {
                // Edición
                ApprovedIntradayPeriod::findOrFail($this->periodId)->update([
                    'team_id'               => $this->periodTeamId,
                    'activity_definition_id' => $this->periodActivityDefinitionId,
                    'date'                  => $this->periodDate,
                    'start_time'            => $this->periodStartTime,
                    'end_time'              => $this->periodEndTime,
                    'max_slots'             => $this->periodMaxSlots,
                    'notes'                 => $this->periodNotes ?: null,
                ]);
            } else {
                $action->execute([
                    'team_id'               => $this->periodTeamId,
                    'activity_definition_id' => $this->periodActivityDefinitionId,
                    'date'                  => $this->periodDate,
                    'start_time'            => $this->periodStartTime,
                    'end_time'              => $this->periodEndTime,
                    'max_slots'             => $this->periodMaxSlots,
                    'notes'                 => $this->periodNotes ?: null,
                ]);
            }
            // Align list filters to show the newly created/edited period immediately
            $this->date = $this->periodDate;
            $this->selectedTeamId = $this->periodTeamId;

            \Flux::toast('Periodo aprobado guardado correctamente.');
            $this->showPeriodModal = false;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
        } catch (\Exception $e) {
            \Flux::toast('Error al guardar el periodo: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function deletePeriod(int $id): void
    {
        $this->authorize('wfm.intraday.periods.manage');
        ApprovedIntradayPeriod::findOrFail($id)->delete();
        \Flux::toast('Periodo aprobado eliminado.');
    }

    // ======================================================
    // ASIGNACIÓN DE OPERADORES A SLOTS (Coordinador / WFM)
    // ======================================================

    public function openAssignmentModal(int $periodId): void
    {
        $this->authorize('wfm.intraday.assign');

        $period = ApprovedIntradayPeriod::findOrFail($periodId);
        $this->assigningPeriodId = $period->id;
        $this->startTime = Carbon::parse($period->start_time)->format('H:i');
        $this->endTime = Carbon::parse($period->end_time)->format('H:i');
        $this->minTimeLimit = $this->startTime;
        $this->maxTimeLimit = $this->endTime;
        $this->reset(['selectedEmployeeIds', 'assignNotes']);
        $this->showAssignmentModal = true;
    }

    public function assignActivity(AssignIntradayActivityAction $action): void
    {
        $this->authorize('wfm.intraday.assign');

        $period = ApprovedIntradayPeriod::findOrFail($this->assigningPeriodId);

        $this->validate([
            'selectedEmployeeIds'   => 'required|array|min:1',
            'startTime'             => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value < $this->minTimeLimit || $value > $this->maxTimeLimit) {
                        $fail("La hora de inicio debe estar entre {$this->minTimeLimit} y {$this->maxTimeLimit}.");
                    }
                }
            ],
            'endTime'               => [
                'required',
                'after:startTime',
                function ($attribute, $value, $fail) {
                    if ($value < $this->minTimeLimit || $value > $this->maxTimeLimit) {
                        $fail("La hora de fin debe estar entre {$this->minTimeLimit} y {$this->maxTimeLimit}.");
                    }
                }
            ],
        ], [], [
            'selectedEmployeeIds' => 'Empleados',
            'startTime'           => 'Hora inicio',
            'endTime'             => 'Hora fin',
        ]);

        try {
            $dto = IntradayActivityDTO::fromArray([
                'activity_definition_id' => $period->activity_definition_id,
                'employee_ids'           => array_map('intval', $this->selectedEmployeeIds),
                'date'                   => $period->date->toDateString(),
                'start_time'             => $this->startTime,
                'end_time'               => $this->endTime,
                'notes'                  => $this->assignNotes ?: null,
                'approved_period_id'     => $period->id,
            ]);

            $action->execute($dto);

            \Flux::toast('Actividad asignada exitosamente.');
            $this->showAssignmentModal = false;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
        } catch (\Exception $e) {
            \Flux::toast('Error: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function deleteActivity(int $id): void
    {
        IntradayActivity::findOrFail($id)->delete();
        \Flux::toast('Actividad eliminada.');
    }

    // ======================================================
    // HELPERS
    // ======================================================

    /**
     * Determina si el usuario actual puede gestionar periodos (es WFM).
     */
    public function getIsWfmProperty(): bool
    {
        return auth()->user()?->can('wfm.intraday.periods.manage') ?? false;
    }

    /**
     * Retorna el team_id del coordinador autenticado (si aplica).
     */
    public function getCoordinatorTeamIdProperty(): ?int
    {
        return auth()->user()?->employee?->team_id;
    }

    // ======================================================
    // RENDER
    // ======================================================

    public function render()
    {
        $isWfm = $this->isWfm;
        $coordinatorTeamId = $this->coordinatorTeamId;

        // WFM ve todos los periodos; coordinador solo los de su equipo
        $periodsQuery = ApprovedIntradayPeriod::with(['team', 'activityDefinition'])
            ->withCount('assignments')
            ->whereDate('date', $this->date ?: now()->toDateString())
            ->orderBy('start_time');

        if (! $isWfm && $coordinatorTeamId) {
            $periodsQuery->where('team_id', $coordinatorTeamId);
        } elseif ($this->selectedTeamId) {
            $periodsQuery->where('team_id', $this->selectedTeamId);
        }

        $periods = $periodsQuery->get();

        // Actividades ya asignadas del día
        $activitiesQuery = IntradayActivity::with(['employee', 'employee.team', 'activityType', 'approvedPeriod'])
            ->whereDate('created_at', $this->date ?: now()->toDateString());

        if (DB::getDriverName() === 'pgsql') {
            $activitiesQuery = IntradayActivity::with(['employee', 'employee.team', 'activityType', 'approvedPeriod'])
                ->whereRaw('time_range && tstzrange(?, ?)', [
                    Carbon::parse(($this->date ?: now()->toDateString()).' 00:00:00')->toIso8601String(),
                    Carbon::parse(($this->date ?: now()->toDateString()).' 23:59:59')->toIso8601String(),
                ]);
        }

        if (! $isWfm && $coordinatorTeamId) {
            $activitiesQuery->whereHas('employee', fn ($q) => $q->where('team_id', $coordinatorTeamId));
        } elseif ($this->selectedTeamId) {
            $activitiesQuery->whereHas('employee', fn ($q) => $q->where('team_id', $this->selectedTeamId));
        }

        $activities = $activitiesQuery->get();

        // Empleados disponibles para asignar (filtrados al equipo del periodo seleccionado)
        $availableEmployees = collect();
        if ($this->assigningPeriodId) {
            $assigningPeriod = ApprovedIntradayPeriod::find($this->assigningPeriodId);
            if ($assigningPeriod) {
                $alreadyAssignedIds = $assigningPeriod->assignments()->pluck('employee_id')->toArray();
                $availableEmployees = Employee::with('team')
                    ->where('team_id', $assigningPeriod->team_id)
                    ->where('is_active', true)
                    ->whereNotIn('id', $alreadyAssignedIds)
                    ->orderBy('last_name')
                    ->get();
            }
        }

        return view('wfm::livewire.manage-intraday-activities', [
            'periods'            => $periods,
            'activities'         => $activities,
            'definitions'        => ScheduledActivityDefinition::where('is_active', true)->get(),
            'teams'              => $isWfm ? Team::where('is_active', true)->orderBy('name')->get() : collect(),
            'availableEmployees' => $availableEmployees,
            'isWfm'              => $isWfm,
            'coordinatorTeamId'  => $coordinatorTeamId,
        ])->layout('layouts.app');
    }
}
