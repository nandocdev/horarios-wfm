<?php

declare(strict_types=1);

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Team;
use Illuminate\Support\Facades\Hash;

it('shows audit list to admin users', function () {
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create([
        'password' => Hash::make('password'),
        'force_password_change' => false,
    ]);

    $admin->assignRole($role);

    AuditLog::factory()->create([
        'entity_type' => Team::class,
        'entity_id' => 1,
        'action' => 'created',
        'ip_address' => '127.0.0.1',
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('audit.index'))
        ->assertOk()
        ->assertSee('Audit Logs')
        ->assertSee('created');
});

it('applies search filters to audit logs', function () {
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create([
        'password' => Hash::make('password'),
        'force_password_change' => false,
    ]);
    $admin->assignRole($role);

    AuditLog::factory()->createMany([
        [
            'entity_type' => Team::class,
            'entity_id' => 1,
            'action' => 'created',
            'ip_address' => '127.0.0.1',
            'user_id' => $admin->id,
        ],
        [
            'entity_type' => Team::class,
            'entity_id' => 2,
            'action' => 'deleted',
            'ip_address' => '127.0.0.2',
            'user_id' => $admin->id,
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('audit.index', ['action' => 'created']))
        ->assertOk()
        ->assertSee('created');
});
