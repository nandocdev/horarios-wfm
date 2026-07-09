<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Providers;

use App\Modules\PersonnelModule\Livewire\CreateEmployee;
use App\Modules\PersonnelModule\Livewire\CreateTeam;
use App\Modules\PersonnelModule\Livewire\EditEmployee;
use App\Modules\PersonnelModule\Livewire\EditTeam;
use App\Modules\PersonnelModule\Livewire\ImportEmployees;
use App\Modules\PersonnelModule\Livewire\ListEmployees;
use App\Modules\PersonnelModule\Livewire\ListTeams;
use App\Modules\PersonnelModule\Livewire\ManageTeamAssignments;
use App\Modules\PersonnelModule\Livewire\ManageTeamMembers;
use App\Modules\PersonnelModule\Livewire\ShowTeam;
use App\Modules\PersonnelModule\Livewire\TeamMemberTransfer;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Observers\EmployeeObserver;
use App\Modules\PersonnelModule\Observers\EmploymentStatusObserver;
use App\Modules\PersonnelModule\Observers\TeamObserver;
use App\Modules\PersonnelModule\Policies\EmployeePolicy;
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
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'employees');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'location');
    }

    protected function registerInfrastructure(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('personnel.list-employees', ListEmployees::class);
        Livewire::component('personnel.create-employee', CreateEmployee::class);
        Livewire::component('personnel.edit-employee', EditEmployee::class);
        Livewire::component('personnel.manage-team-assignments', ManageTeamAssignments::class);
        Livewire::component('personnel.import-employees', ImportEmployees::class);

        Livewire::component('personnel.list-teams', ListTeams::class);
        Livewire::component('personnel.create-team', CreateTeam::class);
        Livewire::component('personnel.show-team', ShowTeam::class);
        Livewire::component('personnel.edit-team', EditTeam::class);
        Livewire::component('personnel.manage-team-members', ManageTeamMembers::class);
        Livewire::component('personnel.team-member-transfer', TeamMemberTransfer::class);

        Livewire::component('employees.list-employees', ListEmployees::class);
        Livewire::component('employees.create-employee', CreateEmployee::class);
        Livewire::component('employees.edit-employee', EditEmployee::class);
        Livewire::component('employees.manage-team-assignments', ManageTeamAssignments::class);
        Livewire::component('employees.import-employees', ImportEmployees::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
    }

    protected function registerObservers(): void
    {
        Employee::observe(EmployeeObserver::class);
        EmploymentStatus::observe(EmploymentStatusObserver::class);
        Team::observe(TeamObserver::class);
    }
}
