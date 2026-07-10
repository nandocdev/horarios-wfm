<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\WfmModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Livewire\ManageSchedules;
use App\Modules\WfmModule\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('schedules.manage');
    $this->actingAs($this->user);
});

it('renders the manage schedules page', function () {
    Livewire::test(ManageSchedules::class)
        ->assertStatus(200);
});

it('lists existing schedules', function () {
    Schedule::factory()->create(['name' => 'Matutino']);
    Schedule::factory()->create(['name' => 'Vespertino']);

    Livewire::test(ManageSchedules::class)
        ->assertSee('Matutino')
        ->assertSee('Vespertino');
});

it('opens create modal', function () {
    Livewire::test(ManageSchedules::class)
        ->call('create')
        ->assertSet('showModal', true);
});

it('validates required fields on save', function () {
    Livewire::test(ManageSchedules::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors(['form.name', 'form.start_time', 'form.end_time']);
});

it('creates a new schedule', function () {
    Livewire::test(ManageSchedules::class)
        ->call('create')
        ->set('form.name', 'Turno Nocturno')
        ->set('form.start_time', '22:00')
        ->set('form.end_time', '06:00')
        ->set('form.total_minutes', 480)
        ->set('form.break_minutes', 30)
        ->set('form.lunch_minutes', 60)
        ->set('form.allowed_days', [1, 2, 3, 4, 5, 6, 7])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $this->assertDatabaseHas('schedules', ['name' => 'Turno Nocturno']);
});

it('edits an existing schedule', function () {
    $schedule = Schedule::factory()->create(['name' => 'Original']);

    Livewire::test(ManageSchedules::class)
        ->call('edit', $schedule->id)
        ->assertSet('showModal', true)
        ->assertSet('form.name', 'Original')
        ->set('form.name', 'Modificado')
        ->call('save')
        ->assertHasNoErrors();

    expect($schedule->fresh()->name)->toBe('Modificado');
});

it('deletes a schedule', function () {
    $schedule = Schedule::factory()->create(['name' => 'Eliminar']);

    Livewire::test(ManageSchedules::class)
        ->call('delete', $schedule->id);

    expect(Schedule::find($schedule->id))->toBeNull();
    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
});
