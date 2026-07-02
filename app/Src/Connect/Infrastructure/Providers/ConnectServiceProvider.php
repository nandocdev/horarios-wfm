<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Providers;

use App\Src\Connect\Domain\Events\CallEventReceived;
use App\Src\Connect\Domain\Events\CallRecordClosed;
use App\Src\Connect\Domain\Events\CallRecordCompleted;
use App\Src\Connect\Domain\Events\CallRecordStarted;
use App\Src\Connect\Domain\Events\EmployeeProvisioned;
use App\Src\Connect\Domain\Ports\CiscoAprovisioningInterface;
use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use App\Src\Connect\Domain\Repositories\AgentCallPerformanceRepositoryInterface;
use App\Src\Connect\Domain\Repositories\AgentStateTransitionRepositoryInterface;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use App\Src\Connect\Domain\Repositories\CallQueueRepositoryInterface;
use App\Src\Connect\Domain\Repositories\CallRecordRepositoryInterface;
use App\Src\Connect\Domain\Repositories\CaseSubtypeRepositoryInterface;
use App\Src\Connect\Domain\Repositories\ChannelRepositoryInterface;
use App\Src\Connect\Domain\Repositories\ChatRecordRepositoryInterface;
use App\Src\Connect\Domain\Repositories\CsqRealtimeStatRepositoryInterface;
use App\Src\Connect\Infrastructure\Integrations\CiscoFinesseAdapter;
use App\Src\Connect\Infrastructure\Integrations\CuicIntegrationService;
use App\Src\Connect\Infrastructure\Listeners\HandleCallEventReceivedListener;
use App\Src\Connect\Infrastructure\Listeners\HandleEmployeeProvisionedListener;
use App\Src\Connect\Infrastructure\Persistence\EloquentAgentCallPerformanceRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentAgentStateTransitionRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueueRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecordRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentCaseSubtypeRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentChannelRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentChatRecordRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentConnectRepository;
use App\Src\Connect\Infrastructure\Persistence\EloquentCsqRealtimeStatRepository;
use App\Src\Connect\Presentation\Livewire\AgentDashboard;
use App\Src\Connect\Presentation\Livewire\CreateCallRecord;
use App\Src\Connect\Presentation\Livewire\EditCallRecord;
use App\Src\Connect\Presentation\Livewire\GeneralDashboard;
use App\Src\Connect\Presentation\Livewire\ListCallQueues;
use App\Src\Connect\Presentation\Livewire\ListCallRecords;
use App\Src\Connect\Presentation\Livewire\ListCaseSubtypes;
use App\Src\Connect\Presentation\Livewire\ListChannels;
use App\Src\Connect\Presentation\Policies\CallQueuePolicy;
use App\Src\Connect\Presentation\Policies\CallRecordPolicy;
use App\Src\Connect\Presentation\Policies\CaseSubtypePolicy;
use App\Src\Connect\Presentation\Policies\ChannelPolicy;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class ConnectServiceProvider extends ServiceProvider {
    public function register(): void {
        // Ports
        $this->app->singleton(CiscoAprovisioningInterface::class, CiscoFinesseAdapter::class);
        $this->app->singleton(CuicIntegrationInterface::class, CuicIntegrationService::class);

        // Repositories
        $this->app->bind(CallEventRepositoryInterface::class, EloquentConnectRepository::class);
        $this->app->bind(CallRecordRepositoryInterface::class, EloquentCallRecordRepository::class);
        $this->app->bind(CallQueueRepositoryInterface::class, EloquentCallQueueRepository::class);
        $this->app->bind(ChannelRepositoryInterface::class, EloquentChannelRepository::class);
        $this->app->bind(CaseSubtypeRepositoryInterface::class, EloquentCaseSubtypeRepository::class);
        $this->app->bind(AgentStateTransitionRepositoryInterface::class, EloquentAgentStateTransitionRepository::class);
        $this->app->bind(ChatRecordRepositoryInterface::class, EloquentChatRecordRepository::class);
        $this->app->bind(AgentCallPerformanceRepositoryInterface::class, EloquentAgentCallPerformanceRepository::class);
        $this->app->bind(CsqRealtimeStatRepositoryInterface::class, EloquentCsqRealtimeStatRepository::class);
    }

    public function boot(): void {
        $this->loadRoutes();
        $this->loadViews();
        $this->registerPolicies();
        $this->registerLivewireComponents();
        $this->registerEventListeners();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    private function loadRoutes(): void {
        $routesPath = __DIR__ . '/../../Presentation/Routes/web.php';
        if (file_exists($routesPath)) {
            Route::middleware('web')->group($routesPath);
        }
    }

    private function loadViews(): void {
        $viewsPath = __DIR__ . '/../../Presentation/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'connect');
        }
    }

    private function registerPolicies(): void {
        Gate::policy(CallRecordPolicy::class, CallRecordPolicy::class);
        Gate::policy(CallQueuePolicy::class, CallQueuePolicy::class);
        Gate::policy(ChannelPolicy::class, ChannelPolicy::class);
        Gate::policy(CaseSubtypePolicy::class, CaseSubtypePolicy::class);
    }

    private function registerLivewireComponents(): void {
        Livewire::component('connect.agent-dashboard', AgentDashboard::class);
        Livewire::component('connect.general-dashboard', GeneralDashboard::class);
        Livewire::component('connect.list-call-records', ListCallRecords::class);
        Livewire::component('connect.create-call-record', CreateCallRecord::class);
        Livewire::component('connect.edit-call-record', EditCallRecord::class);
        Livewire::component('connect.list-call-queues', ListCallQueues::class);
        Livewire::component('connect.list-channels', ListChannels::class);
        Livewire::component('connect.list-case-subtypes', ListCaseSubtypes::class);
    }

    private function registerEventListeners(): void {
        Event::listen(CallEventReceived::class, HandleCallEventReceivedListener::class);
        Event::listen(EmployeeProvisioned::class, HandleEmployeeProvisionedListener::class);
        Event::listen(CallRecordStarted::class, \App\Src\Platform\Infrastructure\Listeners\AuditLogBridgeListener::class);
        Event::listen(CallRecordCompleted::class, \App\Src\Platform\Infrastructure\Listeners\AuditLogBridgeListener::class);
        Event::listen(CallRecordClosed::class, \App\Src\Platform\Infrastructure\Listeners\AuditLogBridgeListener::class);
    }

    private function registerCommands(): void {
        $this->commands([
            \App\Src\Connect\Infrastructure\Console\CuicSyncCommand::class,
            \App\Src\Connect\Infrastructure\Console\CuicBackfillCommand::class,
            \App\Src\Connect\Infrastructure\Console\CuicRealtimeSyncCommand::class,
            \App\Src\Connect\Infrastructure\Console\FinesseSyncCommand::class,
            \App\Src\Connect\Infrastructure\Console\ImportUccxDataCommand::class,
            \App\Src\Connect\Infrastructure\Console\AutoImportUccxCommand::class,
            \App\Src\Connect\Infrastructure\Console\TestCuicAgentDetailCommand::class,
        ]);
    }
}
