<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Providers;

use App\Modules\PersonnelModule\Livewire\CreateDepartment;
use App\Modules\PersonnelModule\Livewire\CreateDirectorate;
use App\Modules\PersonnelModule\Livewire\CreateEmployee;
use App\Modules\PersonnelModule\Livewire\CreatePosition;
use App\Modules\PersonnelModule\Livewire\CreateTeam;
use App\Modules\PersonnelModule\Livewire\EditDepartment;
use App\Modules\PersonnelModule\Livewire\EditDirectorate;
use App\Modules\PersonnelModule\Livewire\EditEmployee;
use App\Modules\PersonnelModule\Livewire\EditPosition;
use App\Modules\PersonnelModule\Livewire\EditTeam;
use App\Modules\PersonnelModule\Livewire\ImportEmployees;
use App\Modules\PersonnelModule\Livewire\ListDepartments;
use App\Modules\PersonnelModule\Livewire\ListDirectorates;
use App\Modules\PersonnelModule\Livewire\ListEmployees;
use App\Modules\PersonnelModule\Livewire\ListPositions;
use App\Modules\PersonnelModule\Livewire\ListTeams;
use App\Modules\PersonnelModule\Livewire\ManageTeamAssignments;
use App\Modules\PersonnelModule\Livewire\ManageTeamMembers;
use App\Modules\PersonnelModule\Livewire\ShowDepartment;
use App\Modules\PersonnelModule\Livewire\ShowDirectorate;
use App\Modules\PersonnelModule\Livewire\ShowPosition;
use App\Modules\PersonnelModule\Livewire\ShowTeam;
use App\Modules\PersonnelModule\Livewire\TeamMemberTransfer;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Directorate;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Position;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Observers\DepartmentObserver;
use App\Modules\PersonnelModule\Observers\DirectorateObserver;
use App\Modules\PersonnelModule\Observers\EmployeeObserver;
use App\Modules\PersonnelModule\Observers\EmploymentStatusObserver;
use App\Modules\PersonnelModule\Observers\PositionObserver;
use App\Modules\PersonnelModule\Observers\TeamObserver;
use App\Modules\PersonnelModule\Policies\DepartmentPolicy;
use App\Modules\PersonnelModule\Policies\DirectoratePolicy;
use App\Modules\PersonnelModule\Policies\EmployeePolicy;
use App\Modules\PersonnelModule\Policies\PositionPolicy;
use App\Modules\PersonnelModule\Policies\TeamPolicy;
use App\Modules\PersonnelModule\Repositories\EloquentEmployeeLookupRepository;
use App\Modules\PersonnelModule\Repositories\EloquentEmployeeRepository;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Contrato de lookup de empleados para comunicación inter-módulo.
        $this->app->singleton(
            EmployeeLookupRepositoryInterface::class,
            EloquentEmployeeLookupRepository::class
        );

        $this->app->singleton(
            EmployeeRepositoryInterface::class,
            EloquentEmployeeRepository::class
        );
    }

    public function boot(): void
    {
        $this->registerInfrastructure();
        $this->registerPolicies();
        $this->registerObservers();

        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'personnel');

        // Mantener namespaces de vistas antiguos para compatibilidad temporal si es necesario
        // Pero preferimos actualizar todo.
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'employees');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'organization');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'location');
    }

    protected function registerInfrastructure(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        // Registro de Componentes Livewire
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        // Namespace Personnel
        Livewire::component('personnel.list-employees', ListEmployees::class);
        Livewire::component('personnel.create-employee', CreateEmployee::class);
        Livewire::component('personnel.edit-employee', EditEmployee::class);
        Livewire::component('personnel.manage-team-assignments', ManageTeamAssignments::class);
        Livewire::component('personnel.import-employees', ImportEmployees::class);

        Livewire::component('personnel.list-directorates', ListDirectorates::class);
        Livewire::component('personnel.create-directorate', CreateDirectorate::class);
        Livewire::component('personnel.show-directorate', ShowDirectorate::class);
        Livewire::component('personnel.edit-directorate', EditDirectorate::class);

        Livewire::component('personnel.list-departments', ListDepartments::class);
        Livewire::component('personnel.create-department', CreateDepartment::class);
        Livewire::component('personnel.show-department', ShowDepartment::class);
        Livewire::component('personnel.edit-department', EditDepartment::class);

        Livewire::component('personnel.list-positions', ListPositions::class);
        Livewire::component('personnel.create-position', CreatePosition::class);
        Livewire::component('personnel.show-position', ShowPosition::class);
        Livewire::component('personnel.edit-position', EditPosition::class);

        Livewire::component('personnel.list-teams', ListTeams::class);
        Livewire::component('personnel.create-team', CreateTeam::class);
        Livewire::component('personnel.show-team', ShowTeam::class);
        Livewire::component('personnel.edit-team', EditTeam::class);
        Livewire::component('personnel.manage-team-members', ManageTeamMembers::class);
        Livewire::component('personnel.team-member-transfer', TeamMemberTransfer::class);

        // Aliases para compatibilidad con vistas existentes que usen el prefijo antiguo
        Livewire::component('employees.list-employees', ListEmployees::class);
        Livewire::component('employees.create-employee', CreateEmployee::class);
        Livewire::component('employees.edit-employee', EditEmployee::class);
        Livewire::component('employees.manage-team-assignments', ManageTeamAssignments::class);
        Livewire::component('employees.import-employees', ImportEmployees::class);

        Livewire::component('organization.list-directorates', ListDirectorates::class);
        Livewire::component('organization.create-directorate', CreateDirectorate::class);
        Livewire::component('organization.list-departments', ListDepartments::class);
        Livewire::component('organization.create-department', CreateDepartment::class);
        Livewire::component('organization.list-positions', ListPositions::class);
        Livewire::component('organization.create-position', CreatePosition::class);
        Livewire::component('organization.list-teams', ListTeams::class);
        Livewire::component('organization.create-team', CreateTeam::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Directorate::class, DirectoratePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
    }

    protected function registerObservers(): void
    {
        Employee::observe(EmployeeObserver::class);
        EmploymentStatus::observe(EmploymentStatusObserver::class);
        Directorate::observe(DirectorateObserver::class);
        Department::observe(DepartmentObserver::class);
        Position::observe(PositionObserver::class);
        Team::observe(TeamObserver::class);
    }
}
