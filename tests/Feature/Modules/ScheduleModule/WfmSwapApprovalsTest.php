<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\WfmSwapApprovals;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use Livewire\Livewire;

test('wfm approver can approve an accepted shift swap request', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');
    $approver = Employee::factory()->create(['user_id' => $user->id]);

    $requester = Employee::factory()->create();
    $recipient = Employee::factory()->create();

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'accepted',
    ]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->call('approveSwap', $swap->id)
        ->assertHasNoErrors();

    expect($swap->fresh()->status)->toBe('approved');
});

test('wfm approval throws error if user has no employee profile', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');

    $requester = Employee::factory()->create();
    $recipient = Employee::factory()->create();

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'accepted',
    ]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->call('approveSwap', $swap->id);

    expect($swap->fresh()->status)->toBe('accepted');
});

test('wfm approvals can switch tabs to see processed requests', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');
    Employee::factory()->create(['user_id' => $user->id]);

    $requester = Employee::factory()->create();
    $recipient = Employee::factory()->create();

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->assertSet('currentTab', 'pending')
        ->assertDontSee($requester->first_name)
        ->set('currentTab', 'processed')
        ->assertSet('currentTab', 'processed')
        ->assertSee($requester->first_name);
});

test('wfm approvals showDetails method sets property and dispatches show event', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');
    Employee::factory()->create(['user_id' => $user->id]);

    $requester = Employee::factory()->create();
    $recipient = Employee::factory()->create();

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requester->id,
        'recipient_id' => $recipient->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'accepted',
    ]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->call('showDetails', $swap->id)
        ->assertSet('selectedRequest.id', $swap->id)
        ->assertDispatched('modal-show', name: 'swap-details');
});
