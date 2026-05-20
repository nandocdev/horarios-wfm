<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire para listar empleados con filtros.
 *
 * @module EmployeesModule
 *
 * @type Livewire
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class ListEmployees extends Component
{
    use WithPagination;

    // Filtros
    public ?string $search = null;

    public ?int $department_id = null;

    public ?int $position_id = null;

    public ?int $employment_status_id = null;

    public ?bool $is_active = null;

    public ?bool $is_manager = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    // Estado exportación
    public bool $exportAll = true;

    public bool $selectAll = false;

    public array $selected = [];

    // Configuración de paginación
    public int $perPage = 15;

    protected $queryString = [
        'search' => ['except' => null],
        'department_id' => ['except' => null],
        'position_id' => ['except' => null],
        'employment_status_id' => ['except' => null],
        'is_active' => ['except' => null],
        'is_manager' => ['except' => null],
        'date_from' => ['except' => null],
        'date_to' => ['except' => null],
    ];

    /**
     * Reinicia la paginación cuando cambian los filtros.
     */
    public function updated($property): void
    {
        if (in_array($property, ['search', 'department_id', 'position_id', 'employment_status_id', 'is_active', 'is_manager', 'date_from', 'date_to'])) {
            $this->resetPage();
        }

        if ($property === 'selectAll') {
            $this->toggleSelectAll();
        }
    }

    /**
     * Limpia todos los filtros.
     */
    public function clearFilters(): void
    {
        $this->search = null;
        $this->department_id = null;
        $this->position_id = null;
        $this->employment_status_id = null;
        $this->is_active = null;
        $this->is_manager = null;
        $this->date_from = null;
        $this->date_to = null;
        $this->selected = [];
        $this->selectAll = false;
        $this->exportAll = true;
        $this->resetPage();
    }

    /**
     * Obtiene los empleados con filtros aplicados.
     */
    public function getEmployeesProperty()
    {
        return Employee::query()
            ->with(['team', 'position', 'status', 'department', 'township'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('first_name', 'ilike', "%{$this->search}%")
                        ->orWhere('last_name', 'ilike', "%{$this->search}%")
                        ->orWhere('employee_number', 'ilike', "%{$this->search}%")
                        ->orWhere('email', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->department_id, fn (Builder $query) => $query->where('department_id', $this->department_id))
            ->when($this->position_id, fn (Builder $query) => $query->where('position_id', $this->position_id))
            ->when($this->employment_status_id, fn (Builder $query) => $query->where('employment_status_id', $this->employment_status_id))
            ->when($this->is_active !== null, fn (Builder $query) => $query->where('is_active', $this->is_active))
            ->when($this->is_manager !== null, fn (Builder $query) => $query->where('is_manager', $this->is_manager))
            ->when($this->date_from, fn (Builder $query) => $query->whereDate('hire_date', '>=', $this->date_from))
            ->when($this->date_to, fn (Builder $query) => $query->whereDate('hire_date', '<=', $this->date_to))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($this->perPage);
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->employees->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $this->exportAll = false;

            return;
        }

        $this->selected = [];
    }

    public function updatedExportAll(bool $value): void
    {
        if ($value) {
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function getExportQueryParams(string $format): array
    {
        return array_filter([
            'search' => $this->search,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'employment_status_id' => $this->employment_status_id,
            'is_active' => is_null($this->is_active) ? null : ((int) $this->is_active),
            'is_manager' => is_null($this->is_manager) ? null : ((int) $this->is_manager),
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'all' => $this->exportAll ? 1 : 0,
            'selected' => ! $this->exportAll ? $this->selected : null,
            'format' => $format,
        ], fn ($value) => ! is_null($value) && $value !== '' && $value !== []);
    }

    public function getCsvExportUrlProperty(): string
    {
        return route('employees.export', $this->getExportQueryParams('csv'));
    }

    public function getExcelExportUrlProperty(): string
    {
        return route('employees.export', $this->getExportQueryParams('excel'));
    }

    /**
     * Obtiene las opciones para los filtros.
     */
    public function getFilterOptionsProperty(): array
    {
        return [
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'positions' => Position::orderBy('name')->pluck('name', 'id'),
            'employment_statuses' => EmploymentStatus::orderBy('name')->pluck('name', 'id'),
        ];
    }

    /**
     * Sincroniza datos de empleados con Cisco UCCX.
     */
    public function syncWithCisco(SyncEmployeeDataWithCiscoAction $action): void
    {
        try {
            $stats = $action->execute();

            \Flux::toast(
                heading: __('Sincronización completada'),
                text: __('Procesados: :total, Actualizados: :updated, Desajustes: :mismatches', [
                    'total' => $stats['total_cisco_users'],
                    'updated' => $stats['updated_employees'],
                    'mismatches' => $stats['team_mismatches'],
                ]),
                variant: 'success'
            );

            $this->resetPage();
        } catch (\Exception $e) {
            \Flux::toast(
                heading: __('Error de sincronización'),
                text: $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        return view('personnel::livewire.list-employees', [
            'employees' => $this->employees,
            'filterOptions' => $this->filterOptions,
            'csvExportUrl' => $this->csvExportUrl,
            'excelExportUrl' => $this->excelExportUrl,
        ]);
    }
}
