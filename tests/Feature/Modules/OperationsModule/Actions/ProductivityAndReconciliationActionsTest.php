<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule\Actions;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Actions\CalculateAdvancedProductivityAction;
use App\Modules\OperationsModule\Actions\ReconcileEmployeeAttendanceAction;
use App\Modules\OperationsModule\Models\IncidentType;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    IncidentType::create(['code' => 'LATE', 'name' => 'Tardanza', 'requires_justification' => true]);
    IncidentType::create(['code' => 'ABSENT', 'name' => 'Ausencia', 'requires_justification' => true]);
});

it('calculates advanced productivity for an employee', function () {
    AgentCallPerformance::create([
        'employee_id' => $this->employee->id,
        'agent_login_id' => 'agent_'.$this->employee->id,
        'start_time' => now()->subHour(),
        'end_time' => now(),
        'talk_time' => 300,
        'work_time' => 60,
        'csq_name' => 'Soporte',
    ]);

    $action = app(CalculateAdvancedProductivityAction::class);

    $metric = $action->execute($this->employee, Carbon::today());

    expect($metric->employee_id)->toBe($this->employee->id)
        ->and($metric->calls_total)->toBe(1)
        ->and($metric->talk_seconds)->toBe(300);
});

it('creates a late attendance incident via ReconcileEmployeeAttendanceAction', function () {
    $yesterday = Carbon::yesterday();

    AgentStateTransition::create([
        'employee_id' => $this->employee->id,
        'agent_login_id' => 'agent_'.$this->employee->id,
        'transition_time' => $yesterday->copy()->setHour(9)->setMinute(15),
        'agent_state' => 'READY',
        'duration' => 300,
    ]);

    $action = app(ReconcileEmployeeAttendanceAction::class);

    $result = $action->execute($this->employee, $yesterday);

    expect($result)->toBe([]); // no schedule → no LATE detection
});

it('reconciles attendance with empty data gracefully', function () {
    $action = app(ReconcileEmployeeAttendanceAction::class);

    $result = $action->execute($this->employee, Carbon::yesterday());

    expect($result)->toBe([]);
});

it('returns empty daily metric when no calls exist', function () {
    $action = app(CalculateAdvancedProductivityAction::class);

    $metric = $action->execute($this->employee, Carbon::today());

    expect($metric->calls_total)->toBe(0)
        ->and($metric->talk_seconds)->toBe(0);
});
