<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Providers;

use App\Modules\AuditModule\Domain\Repositories\AuditLogRepository;
use App\Modules\AuditModule\Infrastructure\Console\Commands\AuditPruneCommand;
use App\Modules\AuditModule\Infrastructure\Listeners\LogLeaveRequestCreatedListener;
use App\Modules\AuditModule\Infrastructure\Listeners\LogLeaveRequestDecisionListener;
use App\Modules\AuditModule\Infrastructure\Listeners\LogShiftSwapApprovedListener;
use App\Modules\AuditModule\Infrastructure\Listeners\LogWeeklySchedulePublishedListener;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogEloquentRepository;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Policies\AuditLogPolicy;
use App\Modules\AuditModule\Presentation\Livewire\ListAuditLogs;
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
    public function register(): void
    {
        $this->app->bind(AuditLogRepository::class, AuditLogEloquentRepository::class);

        $this->registerCommands();
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerLivewireComponents();
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'audit');

        Event::listen(WeeklySchedulePublished::class, LogWeeklySchedulePublishedListener::class);
        Event::listen(LeaveRequestCreated::class, LogLeaveRequestCreatedListener::class);
        Event::listen(LeaveRequestDecision::class, LogLeaveRequestDecisionListener::class);
        Event::listen(ShiftSwapApproved::class, LogShiftSwapApprovedListener::class);
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
            ->group(__DIR__.'/../Presentation/Routes/web.php');
    }

    private function registerPolicies(): void
    {
        Gate::policy(AuditLogModel::class, AuditLogPolicy::class);
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('audit.list-audit-logs', ListAuditLogs::class);
    }
}
