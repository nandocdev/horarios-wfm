<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\SwapRequestHistory;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Livewire\Livewire;

test('recipient can accept a shift swap request', function () {
    $user = User::factory()->create();
    $user->assignRole('operator');

    $recipient = Employee::factory()->create(['user_id' => $user->id]);
    $requester = Employee::factory()->create();

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'requested_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(SwapRequestHistory::class)
        ->call('acceptSwap', $swap->id);

    expect($swap->fresh()->status)->toBe('accepted');
});

test('recipient can reject a shift swap request', function () {
    $user = User::factory()->create();
    $user->assignRole('operator');

    $recipient = Employee::factory()->create(['user_id' => $user->id]);
    $requester = Employee::factory()->create();

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'requested_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(SwapRequestHistory::class)
        ->call('rejectSwap', $swap->id);

    expect($swap->fresh()->status)->toBe('rejected');
});
