<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;

it('can associate an evaluation with a clip id from videoparser', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create();
    $queue = Queue::create(['code' => 'VID', 'name' => 'VID', 'is_active' => true]);

    // clip_id es bigint en el esquema actual: el identificador numérico del
    // clip en el sistema externo de videoparser.
    $clipId = 12345;

    $evaluation = Evaluation::create([
        'queue_id' => $queue->id,
        'employee_id' => $employee->id,
        'evaluator_id' => $user->id,
        'clip_id' => $clipId,
        'dtcall' => now()->toDateString(),
        'tmcall' => now()->toTimeString(),
        'dteval' => now()->toDateString(),
        'tmeval' => now()->toTimeString(),
        'score' => 100,
        'status' => 'activa',
    ]);

    $this->assertDatabaseHas('quality_evaluations', [
        'id' => $evaluation->id,
        'clip_id' => $clipId,
    ]);

    expect((int) $evaluation->clip_id)->toBe($clipId);
});
