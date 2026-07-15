<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can associate an evaluation with a clip id from videoparser', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create();
    $queue = Queue::create(['code' => 'VID', 'name' => 'VID', 'is_active' => true]);

    $evaluation = Evaluation::create([
        'queue_id' => $queue->id,
        'employee_id' => $employee->id,
        'evaluator_id' => $user->id,
        'clip_id' => 'clip-12345-abcde',
        'dtcall' => now()->toDateString(),
        'tmcall' => now()->toTimeString(),
        'dteval' => now()->toDateString(),
        'tmeval' => now()->toTimeString(),
        'score' => 100,
        'status' => 'activa',
    ]);

    $this->assertDatabaseHas('quality_evaluations', [
        'id' => $evaluation->id,
        'clip_id' => 'clip-12345-abcde',
    ]);

    expect($evaluation->clip_id)->toBe('clip-12345-abcde');
});
