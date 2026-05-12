<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Livewire\ListCallQueues;
use App\Modules\ConnectModule\Livewire\ListCaseSubtypes;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'call_queues.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'case_subtypes.manage', 'guard_name' => 'web']);
});

it('allows a user with catalog permissions to manage call queues', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'wfm', 'guard_name' => 'web'], ['code' => 'WFM', 'hierarchy_level' => 5]);
    $user->assignRole($role);
    $user->givePermissionTo('call_queues.manage');

    Livewire::actingAs($user)
        ->test(ListCallQueues::class)
        ->set('form.name', 'Cola de Prueba')
        ->set('form.description', 'Cola dedicada a incidencias')
        ->set('form.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('call_queues', [
        'name' => 'Cola de Prueba',
        'description' => 'Cola dedicada a incidencias',
        'is_active' => true,
    ]);
});

it('allows a user with catalog permissions to manage case subtypes', function () {
    $queue = CallQueue::firstOrCreate(
        ['name' => 'Servicios de Salud'],
        ['description' => null, 'is_active' => true]
    );
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'wfm', 'guard_name' => 'web'], ['code' => 'WFM', 'hierarchy_level' => 5]);
    $user->assignRole($role);
    $user->givePermissionTo('case_subtypes.manage');

    Livewire::actingAs($user)
        ->test(ListCaseSubtypes::class)
        ->set('form.queue_id', $queue->id)
        ->set('form.code', 'MED_CONSULTA')
        ->set('form.name', 'Consulta médica')
        ->set('form.description', 'Tipo de consulta médica general')
        ->set('form.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('case_subtypes', [
        'queue_id' => $queue->id,
        'code' => 'MED_CONSULTA',
        'name' => 'Consulta médica',
        'is_active' => true,
    ]);
});
