<?php

declare(strict_types=1);

use App\Modules\CoreModule\Actions\DeleteUserAction;
use App\Modules\CoreModule\Livewire\Users\CreateUser;
use App\Modules\CoreModule\Livewire\Users\ListUsers;
use App\Modules\CoreModule\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
});

it('soft deletes a user via DeleteUserAction', function () {
    $user = User::factory()->create();

    app(DeleteUserAction::class)->execute($user);

    expect(User::find($user->id))->toBeNull();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();

    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
});

it('deletes a user through the list users livewire component', function () {
    $target = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->call('delete', $target->id)
        ->assertHasNoErrors();

    expect(User::find($target->id))->toBeNull();
    expect(User::withTrashed()->find($target->id))->not->toBeNull();
});

it('validates unique email in create user form', function () {
    $existing = User::factory()->create(['email' => 'duplicate@example.com']);

    Livewire::test(CreateUser::class)
        ->set('form.name', 'New User')
        ->set('form.email', $existing->email)
        ->set('form.password', 'password')
        ->call('save')
        ->assertHasErrors(['form.email']);
});
