<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Providers;

use App\Modules\OrganizationModule\Livewire\CreateDepartment;
use App\Modules\OrganizationModule\Livewire\CreateDirectorate;
use App\Modules\OrganizationModule\Livewire\CreatePosition;
use App\Modules\OrganizationModule\Livewire\EditDepartment;
use App\Modules\OrganizationModule\Livewire\EditDirectorate;
use App\Modules\OrganizationModule\Livewire\EditPosition;
use App\Modules\OrganizationModule\Livewire\ListDepartments;
use App\Modules\OrganizationModule\Livewire\ListDirectorates;
use App\Modules\OrganizationModule\Livewire\ListPositions;
use App\Modules\OrganizationModule\Livewire\ShowDepartment;
use App\Modules\OrganizationModule\Livewire\ShowDirectorate;
use App\Modules\OrganizationModule\Livewire\ShowPosition;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\OrganizationModule\Observers\DepartmentObserver;
use App\Modules\OrganizationModule\Observers\DirectorateObserver;
use App\Modules\OrganizationModule\Observers\PositionObserver;
use App\Modules\OrganizationModule\Policies\DepartmentPolicy;
use App\Modules\OrganizationModule\Policies\DirectoratePolicy;
use App\Modules\OrganizationModule\Policies\PositionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Directorate::class => DirectoratePolicy::class,
        Department::class => DepartmentPolicy::class,
        Position::class => PositionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'organization');

        Livewire::component('organization.list-directorates', ListDirectorates::class);
        Livewire::component('organization.create-directorate', CreateDirectorate::class);
        Livewire::component('organization.show-directorate', ShowDirectorate::class);
        Livewire::component('organization.edit-directorate', EditDirectorate::class);
        Livewire::component('organization.list-departments', ListDepartments::class);
        Livewire::component('organization.create-department', CreateDepartment::class);
        Livewire::component('organization.show-department', ShowDepartment::class);
        Livewire::component('organization.edit-department', EditDepartment::class);
        Livewire::component('organization.list-positions', ListPositions::class);
        Livewire::component('organization.create-position', CreatePosition::class);
        Livewire::component('organization.show-position', ShowPosition::class);
        Livewire::component('organization.edit-position', EditPosition::class);

        Directorate::observe(DirectorateObserver::class);
        Department::observe(DepartmentObserver::class);
        Position::observe(PositionObserver::class);
    }

    public function register(): void
    {
        //
    }
}
