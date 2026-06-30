<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Providers;

use App\Modules\AuditModule\Console\Commands\AuditPruneCommand;
use App\Modules\AuditModule\Listeners\AuditLeaveRequestCreatedListener;
use App\Modules\AuditModule\Listeners\AuditLeaveRequestDecisionListener;
use App\Modules\AuditModule\Listeners\AuditShiftSwapApprovedListener;
use App\Modules\AuditModule\Listeners\AuditWeeklySchedulePublishedListener;
use App\Modules\AuditModule\Livewire\ListAuditLogs;
use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\AuditModule\Policies\AuditLogPolicy;
use App\Shared\Events\LeaveRequestCreated;
use App\Shared\Events\LeaveRequestDecision;
use App\Shared\Events\ShiftSwapApproved;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerLivewireComponents();
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'audit');

        Event::listen(WeeklySchedulePublished::class, AuditWeeklySchedulePublishedListener::class);
        Event::listen(LeaveRequestCreated::class, AuditLeaveRequestCreatedListener::class);
        Event::listen(LeaveRequestDecision::class, AuditLeaveRequestDecisionListener::class);
        Event::listen(ShiftSwapApproved::class, AuditShiftSwapApprovedListener::class);
    }

    public function register(): void
    {
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditPruneCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified'])
            ->prefix('admin/audit')
            ->name('audit.')
            ->group(__DIR__.'/../Routes/web.php');
    }

    private function registerPolicies(): void
    {
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('audit.list-audit-logs', ListAuditLogs::class);
    }
}
