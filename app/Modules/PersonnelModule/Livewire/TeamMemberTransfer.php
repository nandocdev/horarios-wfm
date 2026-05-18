<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Actions\AssignEmployeeToTeamAction;
use App\Modules\PersonnelModule\Actions\RemoveEmployeeFromTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO;
use App\Modules\PersonnelModule\DTOs\RemoveEmployeeFromTeamDTO;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Componente Livewire para transferir miembros entre equipos usando interfaz de shuttle box.
 * Implementa la propuesta de vista en docs/features/teams.md utilizando FluxUI.
 */
class TeamMemberTransfer extends Component {
    public Team $team;

    public string $leftFilter = 'all';

    public string $rightFilter = 'none';

    public string $leftSearch = '';

    public string $rightSearch = '';

    public array $leftSelected = [];

    public array $rightSelected = [];

    /** @var array<int, array> */
    public array $employees = [];

    /** @var array<int, string> */
    public array $availableTeamsNames = [];

    public function mount(Team $team): void {
        $this->authorize('update', $team);
        $this->team = $team;

        $this->loadEmployees();

        $this->availableTeamsNames = Team::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Establecer filtro derecho por defecto al equipo actual (usando ID)
        $this->rightFilter = (string) $this->team->id;
    }

    /**
     * Carga empleados con información de equipos actuales.
     */
    public function loadEmployees(): void {
        $employees = Employee::query()
            ->with('team')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $this->employees = $employees->map(fn(Employee $employee) => [
            'id' => $employee->id,
            'name' => "{$employee->first_name} {$employee->last_name}",
            'email' => $employee->email,
            'team' => $employee->team?->name,
            'team_id' => $employee->team_id,
            'avatar_url' => $employee->avatar_url,
        ])->toArray();
    }

    #[Computed]
    public function leftEmployees(): array {
        return $this->filterEmployees($this->leftFilter, $this->leftSearch);
    }

    #[Computed]
    public function rightEmployees(): array {
        return $this->filterEmployees($this->rightFilter, $this->rightSearch);
    }

    /**
     * Filtra empleados según el criterio seleccionado y búsqueda.
     */
    private function filterEmployees(string $filter, string $search): array {
        $filtered = match ($filter) {
            'all' => $this->employees,
            'none' => array_filter($this->employees, fn($emp) => !$emp['team_id']),
            default => array_filter($this->employees, fn($emp) => (string) $emp['team_id'] === $filter),
        };

        if ($search !== '') {
            $search = mb_strtolower($search);
            $filtered = array_filter($filtered, function ($emp) use ($search) {
                return str_contains(mb_strtolower($emp['name']), $search) ||
                    str_contains(mb_strtolower($emp['email'] ?? ''), $search);
            });
        }

        return $filtered;
    }

    /**
     * Determina si una transferencia entre dos filtros es válida.
     *
     * @param bool $bulk  True para movimientos masivos (moveAll), false para seleccionados.
     */
    private function isValidTransfer(string $from, string $to, bool $bulk = false): bool {
        if ($to === 'all') {
            return false;
        }

        if ($from === $to) {
            return false;
        }

        if ($bulk && $from === 'all') {
            return false;
        }

        return true;
    }

    #[Computed]
    public function canMoveSelectedRight(): bool {
        return count($this->leftSelected) > 0
            && $this->isValidTransfer($this->leftFilter, $this->rightFilter);
    }

    #[Computed]
    public function canMoveAllRight(): bool {
        return count($this->leftEmployees) > 0
            && $this->isValidTransfer($this->leftFilter, $this->rightFilter, bulk: true);
    }

    #[Computed]
    public function canMoveSelectedLeft(): bool {
        return count($this->rightSelected) > 0
            && $this->isValidTransfer($this->rightFilter, $this->leftFilter);
    }

    #[Computed]
    public function canMoveAllLeft(): bool {
        return count($this->rightEmployees) > 0
            && $this->isValidTransfer($this->rightFilter, $this->leftFilter, bulk: true);
    }

    public function updatedLeftFilter(): void {
        $this->leftSelected = [];
    }

    public function updatedRightFilter(): void {
        $this->rightSelected = [];
    }

    /**
     * Mueve empleados seleccionados del panel izquierdo al derecho.
     */
    public function moveSelectedToRight(AssignEmployeeToTeamAction $assignAction, RemoveEmployeeFromTeamAction $removeAction): void {
        if (empty($this->leftSelected) || $this->rightFilter === 'all') {
            return;
        }

        $targetTeamId = $this->resolveTargetTeamId($this->rightFilter);

        foreach ($this->leftSelected as $employeeId) {
            $this->processAssignment((int) $employeeId, $targetTeamId, $assignAction, $removeAction);
        }

        $this->postTransferCleanUp('leftSelected', 'Empleados movidos exitosamente.');
    }

    /**
     * Mueve todos los empleados visibles del panel izquierdo al derecho.
     */
    public function moveAllToRight(AssignEmployeeToTeamAction $assignAction, RemoveEmployeeFromTeamAction $removeAction): void {
        if ($this->rightFilter === 'all') {
            return;
        }

        $targetTeamId = $this->resolveTargetTeamId($this->rightFilter);
        $toMove = array_column($this->leftEmployees, 'id');

        foreach ($toMove as $employeeId) {
            $this->processAssignment((int) $employeeId, $targetTeamId, $assignAction, $removeAction);
        }

        $this->postTransferCleanUp('leftSelected', 'Todos los empleados del origen han sido transferidos.');
    }

    /**
     * Mueve empleados seleccionados del panel derecho al izquierdo.
     */
    public function moveSelectedToLeft(AssignEmployeeToTeamAction $assignAction, RemoveEmployeeFromTeamAction $removeAction): void {
        if (empty($this->rightSelected) || $this->leftFilter === 'all') {
            return;
        }

        $targetTeamId = $this->resolveTargetTeamId($this->leftFilter);

        foreach ($this->rightSelected as $employeeId) {
            $this->processAssignment((int) $employeeId, $targetTeamId, $assignAction, $removeAction);
        }

        $this->postTransferCleanUp('rightSelected', 'Empleados movidos exitosamente.');
    }

    /**
     * Mueve todos los empleados visibles del panel derecho al izquierdo.
     */
    public function moveAllToLeft(AssignEmployeeToTeamAction $assignAction, RemoveEmployeeFromTeamAction $removeAction): void {
        if ($this->leftFilter === 'all') {
            return;
        }

        $targetTeamId = $this->resolveTargetTeamId($this->leftFilter);
        $toMove = array_column($this->rightEmployees, 'id');

        foreach ($toMove as $employeeId) {
            $this->processAssignment((int) $employeeId, $targetTeamId, $assignAction, $removeAction);
        }

        $this->postTransferCleanUp('rightSelected', 'Todos los empleados del destino han sido transferidos.');
    }

    private function resolveTargetTeamId(string $filter): ?int {
        if ($filter === 'all' || $filter === 'none') {
            return null;
        }

        return (int) $filter;
    }

    private function processAssignment(int $employeeId, ?int $teamId, AssignEmployeeToTeamAction $assignAction, RemoveEmployeeFromTeamAction $removeAction): void {
        if ($teamId) {
            $assignAction->execute(new AssignEmployeeToTeamDTO(
                employee_id: $employeeId,
                team_id: $teamId,
                joined_at: now()->format('Y-m-d')
            ));
        } else {
            $currentMembership = TeamMember::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->first();

            if ($currentMembership) {
                $removeAction->execute(new RemoveEmployeeFromTeamDTO(
                    employee_id: $employeeId,
                    team_id: $currentMembership->team_id,
                    left_at: now()->format('Y-m-d')
                ));
            }
        }
    }

    private function postTransferCleanUp(string $selectionProperty, string $message): void {
        $this->$selectionProperty = [];
        $this->loadEmployees();
        \Flux::toast($message);
    }

    public function toggleSelection(string $panel, int $employeeId): void {
        $property = $panel === 'left' ? 'leftSelected' : 'rightSelected';

        if (in_array($employeeId, $this->$property)) {
            $this->$property = array_filter($this->$property, fn($id) => $id !== $employeeId);
        } else {
            $this->$property[] = $employeeId;
        }
    }

    public function getInitials(string $name): string {
        return collect(explode(' ', $name))
            ->map(fn($n) => mb_substr($n, 0, 1))
            ->take(2)
            ->implode('');
    }

    public function render() {
        return view('personnel::livewire.team-member-transfer');
    }
}
