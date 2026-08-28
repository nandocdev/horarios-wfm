<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\SyncFinesseAgentStatesAction;
use App\Modules\ConnectModule\Jobs\CiscoSync;
use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\PersonnelModule\Actions\SyncEmployeeDataWithCiscoAction;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;

uses(DatabaseTransactions::class);

it('updates agent realtime state and records state transition without fatal errors', function () {
    $employee = Employee::factory()->create([
        'username' => 'jdoe',
        'cisco_username' => 'jdoe',
        'is_active' => true,
    ]);

    $client = mock(CiscoFinesseClient::class);
    $cachePolicy = app(CachePolicyService::class);

    $action = new SyncFinesseAgentStatesAction($client, $cachePolicy);

    $action->updateAgentRealtimeState(
        $employee->id,
        'jdoe',
        [
            'state' => 'READY',
            'reasonCode' => null,
            'stateChangeTime' => now()->toIso8601String(),
        ]
    );

    $realtime = AgentRealtimeState::where('employee_id', $employee->id)->first();
    expect($realtime)->not->toBeNull();
    expect($realtime->current_state)->toBe('READY');

    $transition = AgentStateTransition::where('employee_id', $employee->id)->first();
    expect($transition)->not->toBeNull();
    expect($transition->agent_state)->toBe('READY');
});

it('handles invalid state transitions gracefully without crashing', function () {
    $employee = Employee::factory()->create([
        'username' => 'asmith',
        'cisco_username' => 'asmith',
        'is_active' => true,
    ]);

    $client = mock(CiscoFinesseClient::class);
    $cachePolicy = app(CachePolicyService::class);

    $action = new SyncFinesseAgentStatesAction($client, $cachePolicy);

    // Initial state
    $action->updateAgentRealtimeState(
        $employee->id,
        'asmith',
        ['state' => 'TALKING']
    );

    // Transition TALKING -> OUTBOUND (not in standard matrix)
    $action->updateAgentRealtimeState(
        $employee->id,
        'asmith',
        ['state' => 'OUTBOUND']
    );

    $realtime = AgentRealtimeState::where('employee_id', $employee->id)->first();
    expect($realtime)->not->toBeNull();
    expect($realtime->current_state)->toBe('OUTBOUND');
});

it('CiscoSync job re-dispatches itself even if syncStatesAction fails', function () {
    Queue::fake();

    $syncStatesAction = mock(SyncFinesseAgentStatesAction::class);
    $syncStatesAction->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('Error de conexión con Finesse'));

    $syncDataAction = mock(SyncEmployeeDataWithCiscoAction::class);
    $client = mock(CiscoFinesseClient::class);

    $job = new CiscoSync(false);
    $job->handle($client, $syncStatesAction, $syncDataAction);

    Queue::assertPushed(CiscoSync::class);
});
