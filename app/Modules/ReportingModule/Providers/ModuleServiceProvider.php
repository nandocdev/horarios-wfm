<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Providers;

use App\Modules\CoreModule\Models\User;
use App\Modules\ReportingModule\Livewire\ReportGenerator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'reporting');
        }

        Livewire::component('reporting.generator', ReportGenerator::class);

        Gate::define('reports.export', fn (User $user): bool => $user->hasPermissionTo('reports.export'));
    }
}
