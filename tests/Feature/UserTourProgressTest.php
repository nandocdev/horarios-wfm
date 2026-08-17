<?php

declare(strict_types=1);

use App\Modules\CoreModule\Livewire\Shared\UserTourProgress;
use App\Modules\CoreModule\Models\User;
use App\Modules\CoreModule\Models\UserTourProgress as UserTourProgressModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('el progreso de tours es por usuario y no se comparte entre usuarios', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    UserTourProgressModel::record($userA, 'wfm-planning', 1, 'completed');
    UserTourProgressModel::record($userA, 'my-schedule', 2, 'skipped');

    expect(UserTourProgressModel::mapFor($userA))->toHaveKeys(['wfm-planning', 'my-schedule']);
    expect(UserTourProgressModel::mapFor($userA)['my-schedule']['state'])->toBe('skipped');
    expect(UserTourProgressModel::mapFor($userB))->toBe([]);
});

test('registrar el mismo tour actualiza su version (upsert)', function () {
    $user = User::factory()->create();

    UserTourProgressModel::record($user, 'wfm-planning', 1, 'completed');
    UserTourProgressModel::record($user, 'wfm-planning', 2, 'completed');

    expect(UserTourProgressModel::where('user_id', $user->id)->count())->toBe(1);
    expect(UserTourProgressModel::mapFor($user)['wfm-planning']['version'])->toBe(2);
});

test('el componente expone el progreso del usuario autenticado', function () {
    $user = User::factory()->create();
    UserTourProgressModel::record($user, 'wfm-planning', 1, 'completed');

    Livewire::actingAs($user)
        ->test(UserTourProgress::class)
        ->assertSee('data-user-tour-progress')
        ->assertSee('wfm-planning');
});

test('el componente no expone progreso para invitados', function () {
    Livewire::test(UserTourProgress::class)
        ->assertSee('data-user-tour-progress')
        ->assertDontSee('wfm-planning');
});

test('el layout de la aplicacion renderiza el componente de progreso de tours', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('data-user-tour-progress');
});

test('el evento tour:record persiste el progreso del usuario autenticado', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(UserTourProgress::class)
        ->dispatch('tour:record', tour: 'wfm-planning', version: 1, state: 'completed');

    $this->assertDatabaseHas('user_tour_progress', [
        'user_id' => $user->id,
        'tour_key' => 'wfm-planning',
        'version' => 1,
        'state' => 'completed',
    ]);
});

test('purge elimina solo los tour_key indicados del usuario', function () {
    $user = User::factory()->create();

    UserTourProgressModel::record($user, 'wfm-planning', 1, 'completed');
    UserTourProgressModel::record($user, 'quality.feedback', 1, 'completed');

    $deleted = UserTourProgressModel::purge($user, ['quality.feedback']);

    expect($deleted)->toBe(1);
    expect(UserTourProgressModel::mapFor($user))->toHaveKeys(['wfm-planning']);
    expect(UserTourProgressModel::mapFor($user))->not->toHaveKey('quality.feedback');
});

test('el evento tour:purge elimina los tours obsoletos del usuario autenticado', function () {
    $user = User::factory()->create();
    UserTourProgressModel::record($user, 'quality.feedback', 1, 'completed');

    Livewire::actingAs($user)
        ->test(UserTourProgress::class)
        ->dispatch('tour:purge', tours: ['quality.feedback']);

    $this->assertDatabaseMissing('user_tour_progress', [
        'user_id' => $user->id,
        'tour_key' => 'quality.feedback',
    ]);
});

test('purge no elimina registros de otros usuarios', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    UserTourProgressModel::record($userA, 'quality.feedback', 1, 'completed');
    UserTourProgressModel::record($userB, 'quality.feedback', 1, 'completed');

    UserTourProgressModel::purge($userA, ['quality.feedback']);

    expect(UserTourProgressModel::mapFor($userA))->toBe([]);
    expect(UserTourProgressModel::mapFor($userB))->toHaveKey('quality.feedback');
});

test('purge con lista vacia no elimina nada', function () {
    $user = User::factory()->create();
    UserTourProgressModel::record($user, 'wfm-planning', 1, 'completed');

    expect(UserTourProgressModel::purge($user, []))->toBe(0);
    expect(UserTourProgressModel::mapFor($user))->toHaveKey('wfm-planning');
});
