<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Actions\StoreEvaluationAction;
use App\Modules\QualityModule\DTOs\CreateEvaluationDTO;
use App\Modules\QualityModule\Events\EvaluationCreated;
use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

it('creates an evaluation and dispatches event', function () {
    Event::fake();

    Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $employee = Employee::factory()->create();

    $queue = Queue::create([
        'code' => 'TEST',
        'name' => 'Test Queue',
        'is_active' => true,
    ]);

    $criteria = Criteria::create(['code' => 'C01']);
    $version = CriteriaVersion::create([
        'criteria_id' => $criteria->id,
        'version' => 1,
        'criterio_text' => 'Test',
        'puntaje' => 10,
        'valid_from' => now()->toDateString(),
    ]);

    $dto = new CreateEvaluationDTO(
        queue_id: $queue->id,
        employee_id: $employee->id,
        evaluator_id: $user->id,
        clip_id: null,
        dtcall: now()->toDateString(),
        tmcall: now()->toTimeString(),
        dteval: now()->toDateString(),
        tmeval: now()->toTimeString(),
        scores: [
            ['criteria_version_id' => $version->id, 'puntaje' => 10],
        ],
        red_flags: [],
        callobs: 'Everything was good'
    );

    $action = app(StoreEvaluationAction::class);
    $evaluation = $action->execute($dto);

    expect($evaluation)
        ->toBeInstanceOf(Evaluation::class)
        ->score->toBe(10)
        ->has_redflag->toBeFalse()
        ->callobs->toBe('Everything was good');

    $this->assertDatabaseHas('quality_evaluations', [
        'id' => $evaluation->id,
        'score' => 10,
    ]);

    Event::assertDispatched(EvaluationCreated::class);
});
