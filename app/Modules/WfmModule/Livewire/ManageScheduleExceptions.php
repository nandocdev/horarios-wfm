<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\GenerateFormPdfAction;
use App\Modules\WfmModule\DTOs\AbsenceReportDTO;
use App\Modules\WfmModule\Enums\AbsenceReasonType;
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

    public bool $showF1Preview = false;

    public array $f1Data = [
        'employee_name' => '',
        'employee_number' => '',
        'employee_position' => '',
        'cip_number' => '',
        'base_salary' => 0,
        'salary_supplement' => 0,
        'absence_start_date' => '',
        'absence_total_days' => 1,
        'is_justified' => true,
        'reason_type' => '',
        'medical_certificate_attached' => false,
        'has_witnesses' => false,
        'observations' => '',
        'department_head_name' => '',
        'executive_unit' => '',
    ];

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

    public function openF1Preview(int $id): void
    {
        $exception = ScheduleException::with(['employee.position', 'employee.manager', 'employee.team', 'reason'])->findOrFail($id);

        $employee = $exception->employee;
        $start = $exception->start_at;

        $this->f1Data = [
            'employee_name' => $employee?->full_name ?? '',
            'employee_number' => $employee?->employee_number ?? '',
            'employee_position' => $employee?->position?->name ?? '',
            'cip_number' => $employee?->metadata['cip'] ?? '',
            'base_salary' => (float) ($employee?->salary ?? 0),
            'salary_supplement' => 0.0,
            'absence_start_date' => $start->format('Y-m-d'),
            'absence_total_days' => $exception->is_full_day ? 1 : max(1, (int) ceil($start->diffInMinutes($exception->end_at) / 480)),
            'is_justified' => $exception->reason?->is_excused ?? false,
            'reason_type' => $this->resolveReasonType($exception)->value,
            'medical_certificate_attached' => in_array($exception->absence_reason_code_id, [5, 6]),
            'has_witnesses' => false,
            'observations' => $exception->remarks ?? '',
            'department_head_name' => $employee?->manager?->full_name ?? '',
            'executive_unit' => $employee?->team?->name ?? '',
        ];

        $this->showF1Preview = true;
    }

    public function generateF1(GenerateFormPdfAction $action)
    {
        $d = $this->f1Data;

        $reasonType = AbsenceReasonType::tryFrom($d['reason_type']) ?? AbsenceReasonType::Other;

        $report = new AbsenceReportDTO(
            employee_number: $d['employee_number'] ?: '__________',
            employee_name: $d['employee_name'] ?? '',
            absence_start_date: Carbon::parse($d['absence_start_date']),
            absence_total_days: (int) $d['absence_total_days'],
            employee_position: $d['employee_position'] ?: '__________',
            cip_number: $d['cip_number'] ?: '',
            base_salary: (float) ($d['base_salary'] ?? 0),
            salary_supplement: (float) ($d['salary_supplement'] ?? 0),
            is_justified: (bool) $d['is_justified'],
            reason_type: $reasonType,
            medical_certificate_attached: (bool) $d['medical_certificate_attached'],
            has_witnesses: (bool) $d['has_witnesses'],
            observations: $d['observations'] ?? '',
            department_head_name: $d['department_head_name'] ?: '____________________',
            executive_unit: $d['executive_unit'] ?: '____________________',
            discount_code: null,
            discount_description: null,
            discount_amount: null,
            discount_balance: null,
            accountant_name: '____________________',
            discount_biweekly_authorized: false,
        );

        $this->showF1Preview = false;

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
