<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\QualityModule\Actions\StoreFeedbackAction;
use App\Modules\QualityModule\DTOs\CreateFeedbackDTO;
use App\Modules\QualityModule\Events\FeedbackAdded;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('adds feedback to an evaluation', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $employee = Employee::factory()->create();
    $queue = Queue::create(['code' => 'FDB', 'name' => 'FDB', 'is_active' => true]);
    
    $evaluation = Evaluation::create([
        'queue_id' => $queue->id,
        'employee_id' => $employee->id,
        'evaluator_id' => $user->id,
        'dtcall' => now()->toDateString(),
        'tmcall' => now()->toTimeString(),
        'dteval' => now()->toDateString(),
        'tmeval' => now()->toTimeString(),
        'score' => 100,
        'status' => 'activa',
    ]);
    
    $dto = new CreateFeedbackDTO(
        evaluation_id: $evaluation->id,
        obsfeed: 'Good job',
        created_by: $user->id
    );
    
    $action = app(StoreFeedbackAction::class);
    $feedback = $action->execute($dto);
    
    expect($feedback->obsfeed)->toBe('Good job');
    
    $this->assertDatabaseHas('quality_feedback', [
        'id' => $feedback->id,
        'evaluation_id' => $evaluation->id,
    ]);
    
    Event::assertDispatched(FeedbackAdded::class);
});
