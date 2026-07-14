<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Actions\StoreCalibrationAction;
use App\Modules\QualityModule\DTOs\CreateCalibrationDTO;
use App\Modules\QualityModule\Events\CalibrationCreated;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('adds calibration to an evaluation and updates the score', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $employee = Employee::factory()->create();
    $queue = Queue::create(['code' => 'CAL', 'name' => 'CAL', 'is_active' => true]);
    
    $evaluation = Evaluation::create([
        'queue_id' => $queue->id,
        'employee_id' => $employee->id,
        'evaluator_id' => $user->id,
        'dtcall' => now()->toDateString(),
        'tmcall' => now()->toTimeString(),
        'dteval' => now()->toDateString(),
        'tmeval' => now()->toTimeString(),
        'score' => 80,
        'status' => 'activa',
    ]);
    
    $dto = new CreateCalibrationDTO(
        evaluation_id: $evaluation->id,
        score_nuevo: 95,
        created_by: $user->id,
        obs: 'Recalibrated'
    );
    
    $action = app(StoreCalibrationAction::class);
    $calibration = $action->execute($dto);
    
    $evaluation->refresh();
    
    expect($evaluation->score)->toBe(95)
        ->and($calibration->score_anterior)->toBe(80)
        ->and($calibration->score_nuevo)->toBe(95)
        ->and($calibration->obs)->toBe('Recalibrated');
    
    Event::assertDispatched(CalibrationCreated::class);
});
