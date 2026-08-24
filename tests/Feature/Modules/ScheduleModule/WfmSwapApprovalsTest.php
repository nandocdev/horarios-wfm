<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\WfmSwapApprovals;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('wfm approver can approve an accepted shift swap request', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');
    $approver = Employee::factory()->create(['user_id' => $user->id]);

    $requesterUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $requester = Employee::factory()->create(['user_id' => $requesterUser->id]);
    $recipient = Employee::factory()->create(['user_id' => $recipientUser->id]);

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requesterUser->id,
        'recipient_id' => $recipientUser->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'accepted',
    ]);

    // Sin asignaciones de turno el procesamiento físico del swap lanzaría excepción
    // (correcto): se aísla para probar solo la aprobación administrativa.
    Event::fake([ShiftSwapApproved::class]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->call('approveSwap', $swap->id)
        ->assertHasNoErrors();

    expect($swap->fresh()->status)->toBe('approved');
});

test('wfm approval throws error if user has no employee profile', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');

    $requesterUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $requester = Employee::factory()->create(['user_id' => $requesterUser->id]);
    $recipient = Employee::factory()->create(['user_id' => $recipientUser->id]);

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requesterUser->id,
        'recipient_id' => $recipientUser->id,
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

    $requesterUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $requester = Employee::factory()->create(['user_id' => $requesterUser->id]);
    $recipient = Employee::factory()->create(['user_id' => $recipientUser->id]);

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requesterUser->id,
        'recipient_id' => $recipientUser->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->assertSet('currentTab', 'pending')
        ->assertDontSee('NOMBRE-OCULTO')
        ->set('currentTab', 'processed')
        ->assertSet('currentTab', 'processed')
        ->assertSee($requesterUser->name);
});

test('wfm approvals showDetails method sets property and dispatches show event', function () {
    $user = User::factory()->create();
    $user->assignRole('wfm');
    Employee::factory()->create(['user_id' => $user->id]);

    $requesterUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $requester = Employee::factory()->create(['user_id' => $requesterUser->id]);
    $recipient = Employee::factory()->create(['user_id' => $recipientUser->id]);

    $swap = ShiftSwapRequest::create([
        'requester_id' => $requesterUser->id,
        'recipient_id' => $recipientUser->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'status' => 'accepted',
    ]);

    $this->actingAs($user);

    Livewire::test(WfmSwapApprovals::class)
        ->call('showDetails', $swap->id)
        ->assertSet('selectedRequest.id', $swap->id)
        ->assertDispatched('modal-show', name: 'swap-details');
});
