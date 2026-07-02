<?php

declare(strict_types=1);

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('exports filtered audit logs as csv', function () {
    $admin = User::factory()->create([
        'password' => Hash::make('password'),
        'force_password_change' => false,
    ]);

    $admin->assignRole('admin');

    AuditLogModel::factory()->create([
        'entity_type' => Team::class,
        'entity_id' => 1,
        'action' => 'created',
        'ip_address' => '127.0.0.1',
        'user_id' => $admin->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('audit.export', ['format' => 'csv', 'action' => 'created']));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    $response->assertHeader('Content-Disposition');
});

it('exports filtered audit logs as json', function () {
    $admin = User::factory()->create([
        'password' => Hash::make('password'),
        'force_password_change' => false,
    ]);

    $admin->assignRole('admin');

    AuditLogModel::factory()->create([
        'entity_type' => Team::class,
        'entity_id' => 1,
        'action' => 'updated',
        'ip_address' => '127.0.0.1',
        'user_id' => $admin->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('audit.export', ['format' => 'json', 'action' => 'updated']));

    $response->assertStatus(200);
    $response->assertJsonStructure([['id', 'action', 'entity_type', 'entity_id']]);
});
