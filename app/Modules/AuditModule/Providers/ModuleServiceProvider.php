<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Providers;

use App\Modules\AuditModule\Listeners\AuditLeaveRequestCreatedListener;
use App\Modules\AuditModule\Listeners\AuditLeaveRequestDecisionListener;
use App\Modules\AuditModule\Listeners\AuditShiftSwapApprovedListener;
use App\Modules\AuditModule\Listeners\AuditWeeklySchedulePublishedListener;
use App\Modules\AuditModule\Livewire\ListAuditLogs;
use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\AuditModule\Policies\AuditLogPolicy;
use App\Modules\WfmModule\Events\LeaveRequestCreated;
use App\Modules\WfmModule\Events\LeaveRequestDecision;
use App\Modules\WfmModule\Events\ShiftSwapApproved;
use App\Modules\WfmModule\Events\WeeklySchedulePublished;
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

        // Register audit listeners for schedule domain events
        Event::listen(WeeklySchedulePublished::class, AuditWeeklySchedulePublishedListener::class);
        Event::listen(LeaveRequestCreated::class, AuditLeaveRequestCreatedListener::class);
        Event::listen(LeaveRequestDecision::class, AuditLeaveRequestDecisionListener::class);
        Event::listen(ShiftSwapApproved::class, AuditShiftSwapApprovedListener::class);
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
