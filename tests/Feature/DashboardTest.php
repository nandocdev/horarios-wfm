<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users see the operator dashboard sections', function () {
    $user = User::factory()->create(['name' => 'Fernando López']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Fernando López');
});

test('dashboard renders an employee with a manager without throwing a LogicException', function () {
    $managerUser = User::factory()->create();

    $manager = Employee::factory()->create([
        'first_name' => 'Laura',
        'last_name' => 'García',
        'user_id' => $managerUser->id,
    ]);

    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'parent_id' => $manager->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    expect($employee->manager)->not->toBeNull()
        ->and($employee->manager->full_name)->toBe('Laura García');
});
