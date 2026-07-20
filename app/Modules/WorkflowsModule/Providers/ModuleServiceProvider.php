<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Providers;

use App\Modules\WorkflowsModule\Livewire\PendingApprovals;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use App\Modules\WorkflowsModule\Policies\WorkflowRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(WorkflowRequest::class, WorkflowRequestPolicy::class);

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'workflows');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Livewire::component('workflows.pending-approvals', PendingApprovals::class);
    }

    public function register(): void
    {
        //
    }
}
