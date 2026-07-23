<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Enums\AbsenceReasonType;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\GenerateFormPdfAction;
use App\Modules\WfmModule\DTOs\AbsenceReportDTO;
use App\Modules\WfmModule\Livewire\Forms\ExceptionForm;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ManageScheduleExceptions extends Component
{
    use WithPagination;

    public ExceptionForm $form;

    public bool $showCreateModal = false;

    public ?int $selectedExceptionId = null;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $reasonFilter = '';

    public ?int $teamFilter = null;

    /** @var string[] */
    public array $statusFilter = ['active', 'pending'];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->endOfMonth()->toDateString();

        $user = Auth::user();
        $employee = $user->employee;
        if ($employee && $employee->team_id) {
            $this->teamFilter = $employee->team_id;
        }
    }

    public function create(): void
    {
        $this->form->reset();
        $this->selectedExceptionId = null;
        $this->showCreateModal = true;
    }

    public function edit(int $id): void
    {
        $exception = ScheduleException::findOrFail($id);
        $this->selectedExceptionId = $id;
        $this->form->setException($exception);
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->form->validate();

        if ($this->selectedExceptionId) {
            $exception = ScheduleException::findOrFail($this->selectedExceptionId);
            $exception->update($this->form->toArray());
            \Flux::toast(__('Excepción actualizada exitosamente.'));
        } else {
            ScheduleException::create($this->form->toArray());
            \Flux::toast(__('Excepción registrada exitosamente.'));
        }

        $this->showCreateModal = false;
    }

    public function downloadF1(int $id, GenerateFormPdfAction $action)
    {
        $exception = ScheduleException::with(['employee.reason', 'reason'])->findOrFail($id);

        $employee = $exception->employee;
        $start = $exception->start_at;

        $report = new AbsenceReportDTO(
            employee_number: $employee?->employee_number ?? '__________',
            absence_start_date: Carbon::parse($start),
            absence_total_days: $exception->is_full_day ? 1 : max(1, (int) ceil($start->diffInMinutes($exception->end_at) / 480)),
            employee_position: $employee?->position?->name ?? '__________',
            cip_number: $employee?->metadata['cip'] ?? '',
            base_salary: (float) ($employee?->salary ?? 0),
            salary_supplement: 0.0,
            is_justified: $exception->reason?->is_excused ?? false,
            reason_type: $this->resolveReasonType($exception),
            medical_certificate_attached: in_array($exception->absence_reason_code_id, [5, 6]),
            has_witnesses: false,
            observations: $exception->remarks ?? '',
            department_head_name: $employee?->manager?->full_name ?? '____________________',
            executive_unit: $employee?->team?->name ?? '____________________',
            discount_code: null,
            discount_description: null,
            discount_amount: null,
            discount_balance: null,
            accountant_name: '____________________',
            discount_biweekly_authorized: false,
        );

        return $action->execute(
            data: ['report' => $report],
            view: 'pdf::forms.leave-request',
            title: 'Reporte de Inasistencia - '.$report->employee_number,
        );
    }

    private function resolveReasonType(ScheduleException $exception): AbsenceReasonType
    {
        $shortCode = $exception->reason?->short_code ?? '';

        return match ($shortCode) {
            'C.M.', 'S.C.' => AbsenceReasonType::CommonIllness,
            'R.P.' => AbsenceReasonType::OccupationalRisk,
            'D.' => AbsenceReasonType::Bereavement,
            default => AbsenceReasonType::Other,
        };
    }

    public static function isAbsenceReason(string $shortCode): bool
    {
        return ! in_array($shortCode, ['T.I.', 'T.J.', 'V.', 'L.', 'S.D', 'R']);
    }

    public function delete(int $id): void
    {
        ScheduleException::destroy($id);
        \Flux::toast(__('Excepción eliminada.'));
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $employee = $user->employee;
        $now = now();

        $exceptions = ScheduleException::query()
            ->with(['employee', 'reason', 'creator'])
            ->when($this->search, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('first_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('last_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('username', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->teamFilter, fn ($q) => $q->whereHas('employee', fn ($sq) => $sq->where('team_id', $this->teamFilter)))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('end_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('start_at', '<=', $this->dateTo))
            ->when($this->reasonFilter, fn ($q) => $q->where('absence_reason_code_id', $this->reasonFilter))
            ->when(count($this->statusFilter) < 3, function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    if (in_array('active', $this->statusFilter)) {
                        $q->orWhere(function ($sq) use ($now) {
                            $sq->whereDate('start_at', '<=', $now->toDateString())
                                ->whereDate('end_at', '>=', $now->toDateString());
                        });
                    }
                    if (in_array('pending', $this->statusFilter)) {
                        $q->orWhereDate('start_at', '>', $now->toDateString());
                    }
                    if (in_array('completed', $this->statusFilter)) {
                        $q->orWhereDate('end_at', '<', $now->toDateString());
                    }
                });
            })
            ->orderBy('start_at', 'desc')
            ->paginate(15);

        $managedTeams = $user->hasRole(['admin', 'wfm', 'director'])
            ? Team::active()->orderBy('name')->get()
            : Team::whereIn('id', $employee?->getManagedTeamIds() ?? [])->active()->orderBy('name')->get();

        return view('wfm::livewire.manage-schedule-exceptions', [
            'exceptions' => $exceptions,
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'reasons' => AbsenceReasonCode::all(),
            'managedTeams' => $managedTeams,
            'now' => $now,
        ]);
    }
}
