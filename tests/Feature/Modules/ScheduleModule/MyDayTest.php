<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Livewire\MyDay;
use Livewire\Livewire;

// MyDay es hoy un dashboard de widgets lazy: el componente resuelve el
// employee del usuario autenticado y renderiza la página con la fecha del día.
test('my day component shows todays assignments', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'User']);

    $this->actingAs($user);

    Livewire::test(MyDay::class)
        ->assertStatus(200)
        ->assertSee('Mi Jornada')
        ->assertSet('selectedDate', now()->toDateString());
});

test('my day renders empty view when user has no employee profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(MyDay::class)
        ->assertStatus(200)
        ->assertSee('Mi Jornada');
});

test('my day navigation updates selected date', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(MyDay::class)
        ->call('previousDay')
        ->assertSet('selectedDate', now()->subDay()->toDateString());
});
