<?php

declare(strict_types=1);

use App\Modules\CoreModule\Actions\SyncRolePermissionsAction;
use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a usuario de mayor jerarquia actualizar roles de menor jerarquia', function () {
    $adminRole = Role::findByName('admin', 'web');
    $userRole = Role::create(['name' => 'user-test', 'code' => 'USR-T', 'guard_name' => 'web', 'hierarchy_level' => 50]);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    expect(Gate::forUser($admin)->allows('update', $userRole))->toBeTrue();
});

it('deniega a usuario de menor jerarquia actualizar role de mayor jerarquia', function () {
    $supervisorRole = Role::create(['name' => 'supervisor-test', 'code' => 'SUP-T', 'guard_name' => 'web', 'hierarchy_level' => 20]);
    $directorRole = Role::create(['name' => 'director-test', 'code' => 'DIR-T', 'guard_name' => 'web', 'hierarchy_level' => 5]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole($supervisorRole);

    expect(Gate::forUser($supervisor)->denies('update', $directorRole))->toBeTrue();
});

it('sincroniza permisos de rol y actualiza el cache de permisos', function () {
    $role = Role::create(['name' => 'cache-role-test', 'code' => 'CRT', 'guard_name' => 'web', 'hierarchy_level' => 20]);
    $permission = Permission::firstOrCreate(['name' => 'roles.view', 'guard_name' => 'web']);

    /** @var SyncRolePermissionsAction */
    $action = app(SyncRolePermissionsAction::class);

    $action->execute($role, ['roles.view']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($role->fresh()->hasPermissionTo('roles.view'))->toBeTrue();
});
