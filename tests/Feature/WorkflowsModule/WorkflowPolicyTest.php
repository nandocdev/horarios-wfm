<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite viewAny en WorkflowRequest a admin y supervisor', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'web'));

    $supervisor = User::factory()->create();
    $supervisor->assignRole(Role::findByName('supervisor', 'web'));

    expect(Gate::forUser($admin)->allows('viewAny', WorkflowRequest::class))->toBeTrue();
    expect(Gate::forUser($supervisor)->allows('viewAny', WorkflowRequest::class))->toBeTrue();
});

it('deniega viewAny en WorkflowRequest a operator', function () {
    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'web'));

    expect(Gate::forUser($operator)->allows('viewAny', WorkflowRequest::class))->toBeFalse();
});
