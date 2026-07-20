<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Providers;

use App\Modules\WfmModule\Actions\Realtime\GetExpectedAgentStateAction;
use App\Modules\WfmModule\Console\Commands\CleanExpiredTemporalAssignments;
use App\Modules\WfmModule\Listeners\ApplyShiftSwapToSchedule;
use App\Modules\WfmModule\Listeners\NotifyShiftSwapApproved;
use App\Modules\WfmModule\Livewire\EmployeeWeeklyPlanning;
use App\Modules\WfmModule\Livewire\ManageAbsenceReasons;
use App\Modules\WfmModule\Livewire\ManageActivityTypes;
use App\Modules\WfmModule\Livewire\ManageAgentStates;
use App\Modules\WfmModule\Livewire\ManageScheduledActivities;
use App\Modules\WfmModule\Livewire\ManageSchedules;
use App\Modules\WfmModule\Livewire\MyDay;
use App\Modules\WfmModule\Livewire\MyMetrics;
use App\Modules\WfmModule\Livewire\MySchedule;
use App\Modules\WfmModule\Livewire\TeamWeeklyPlanning;
use App\Modules\WfmModule\Livewire\WeeklyPlanning;
use App\Modules\WfmModule\Livewire\WeeklyPlanningTeams;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\AgentState;
use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\OperationalSetting;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Observers\LeaveRequestObserver;
use App\Modules\WfmModule\Policies\AbsenceReasonCodePolicy;
use App\Modules\WfmModule\Policies\ActivityTypePolicy;
use App\Modules\WfmModule\Policies\AgentStatePolicy;
use App\Modules\WfmModule\Policies\ApprovedIntradayPeriodPolicy;
use App\Modules\WfmModule\Policies\LeaveRequestPolicy;
use App\Modules\WfmModule\Policies\OperationalSettingPolicy;
use App\Modules\WfmModule\Policies\ScheduledActivityDefinitionPolicy;
use App\Modules\WfmModule\Policies\ScheduleExceptionPolicy;
use App\Modules\WfmModule\Policies\SchedulePolicy;
use App\Modules\WfmModule\Policies\ShiftSwapRequestPolicy;
use App\Modules\WfmModule\Policies\WeeklyScheduleAssignmentPolicy;
use App\Modules\WfmModule\Policies\WeeklySchedulePolicy;
use App\Modules\WfmModule\Repositories\EloquentDashboardScheduleQueries;
use App\Modules\WfmModule\Repositories\EloquentScheduleRepository;
use App\Modules\WfmModule\Services\LeaveRequestService;
use App\Modules\WfmModule\Services\ScheduleService;
use App\Modules\WfmModule\Services\ScheduleValidationService;
use App\Modules\WfmModule\Services\ShiftSwapService;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Schedules\LeaveRequestServiceInterface;
use App\Shared\Contracts\Schedules\ScheduleRepositoryInterface;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Contracts\Schedules\ShiftSwapServiceInterface;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    protected array $policies = [
        OperationalSetting::class => OperationalSettingPolicy::class,
        Schedule::class => SchedulePolicy::class,
        ActivityType::class => ActivityTypePolicy::class,
        AbsenceReasonCode::class => AbsenceReasonCodePolicy::class,
        AgentState::class => AgentStatePolicy::class,
        ScheduledActivityDefinition::class => ScheduledActivityDefinitionPolicy::class,
        WeeklySchedule::class => WeeklySchedulePolicy::class,
        WeeklyScheduleAssignment::class => WeeklyScheduleAssignmentPolicy::class,
        LeaveRequest::class => LeaveRequestPolicy::class,
        ShiftSwapRequest::class => ShiftSwapRequestPolicy::class,
        ScheduleException::class => ScheduleExceptionPolicy::class,
        ApprovedIntradayPeriod::class => ApprovedIntradayPeriodPolicy::class,
    ];

    /**
     * Boot the module services.
     */
    public function boot(): void
    {
        // Registro de Policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Rutas
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (file_exists(__DIR__.'/../Routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        }

        // Vistas
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'wfm');

        // Componentes Livewire
        Livewire::component('wfm.manage-schedules', ManageSchedules::class);
        Livewire::component('wfm.manage-activity-types', ManageActivityTypes::class);
        Livewire::component('wfm.manage-absence-reasons', ManageAbsenceReasons::class);
        Livewire::component('wfm.manage-agent-states', ManageAgentStates::class);
        Livewire::component('wfm.manage-scheduled-activities', ManageScheduledActivities::class);
        Livewire::component('wfm.weekly-planning', WeeklyPlanning::class);
        Livewire::component('wfm.weekly-planning-teams', WeeklyPlanningTeams::class);
        Livewire::component('wfm.team-weekly-planning', TeamWeeklyPlanning::class);
        Livewire::component('wfm.employee-weekly-planning', EmployeeWeeklyPlanning::class);
        Livewire::component('wfm.my-schedule', MySchedule::class);
        Livewire::component('wfm.my-day', MyDay::class);
        Livewire::component('wfm.my-metrics', MyMetrics::class);

        // Registro de Observadores
        LeaveRequest::observe(LeaveRequestObserver::class);

        // Registro de Eventos
        Event::listen(
            ShiftSwapApproved::class,
            [ApplyShiftSwapToSchedule::class, 'handle']
        );
        Event::listen(
            ShiftSwapApproved::class,
            [NotifyShiftSwapApproved::class, 'handle']
        );

        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanExpiredTemporalAssignments::class,
            ]);
        }
    }

    /**
     * Register the module services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ScheduleServiceInterface::class,
            ScheduleService::class
        );

        $this->app->singleton(
            LeaveRequestServiceInterface::class,
            LeaveRequestService::class
        );

        $this->app->singleton(
            ShiftSwapServiceInterface::class,
            ShiftSwapService::class
        );

        $this->app->singleton(
            ScheduleValidationService::class
        );

        $this->app->singleton(
            ScheduleRepositoryInterface::class,
            EloquentScheduleRepository::class
        );

        $this->app->singleton(
            GetExpectedAgentStateAction::class
        );

        $this->app->singleton(
            DashboardScheduleQueriesInterface::class,
            EloquentDashboardScheduleQueries::class
        );
    }
}
