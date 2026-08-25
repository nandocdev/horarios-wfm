<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\HelpdeskModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\HelpdeskModule\Policies\HelpdeskTicketPolicy;
use App\Modules\PersonnelModule\Models\Employee;

beforeEach(function () {
    $this->policy = app(HelpdeskTicketPolicy::class);

    $this->employee = Employee::factory()->create();
    $this->user = User::factory()->create();
    $this->employee->user()->associate($this->user)->save();

    $this->otherEmployee = Employee::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->otherEmployee->user()->associate($this->otherUser)->save();

    $this->supportEmployee = Employee::factory()->create();
    $this->supportUser = User::factory()->create();
    $this->supportEmployee->user()->associate($this->supportUser)->save();

    $this->ticket = HelpdeskTicket::factory()->create([
        'creator_id' => $this->user->id,
    ]);
});

it('allows any user with employee to create a ticket', function () {
    expect($this->policy->create($this->user))->toBeTrue();
});

it('denies users without employee to create a ticket', function () {
    $userWithoutEmployee = User::factory()->create();

    expect($this->policy->create($userWithoutEmployee))->toBeFalse();
});

it('allows the creator to view their own ticket', function () {
    expect($this->policy->view($this->user, $this->ticket))->toBeTrue();
});

it('denies other regular users to view tickets they did not create', function () {
    expect($this->policy->view($this->otherUser, $this->ticket))->toBeFalse();
});

it('allows support users to view any ticket', function () {
    $this->supportUser->givePermissionTo('helpdesk.manage');

    expect($this->policy->view($this->supportUser, $this->ticket))->toBeTrue();
});

it('allows users with helpdesk.view to view other tickets', function () {
    $this->otherUser->givePermissionTo('helpdesk.view');
    $otherTicket = HelpdeskTicket::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    expect($this->policy->viewAny($this->otherUser))->toBeTrue()
        ->and($this->policy->view($this->otherUser, $otherTicket))->toBeTrue();
});
