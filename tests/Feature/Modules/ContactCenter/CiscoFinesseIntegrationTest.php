<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('returns a cisco finesse agent snapshot for an authorized user', function () {
    config()->set('contact-center.cisco', [
        'base_url' => 'https://10.11.24.85:8445/finesse/api',
        'username' => 'ferncastillo',
        'password' => 'secret',
        'timeout' => 10,
        'verify_ssl' => false,
    ]);

    Http::fake([
        'https://10.11.24.85:8445/finesse/api/User/ferncastillo' => Http::response(
            <<<'XML'
<User>
    <loginId>ferncastillo</loginId>
    <state>READY</state>
    <extension>4001</extension>
</User>
XML,
            200,
            ['Content-Type' => 'application/xml']
        ),
        'https://10.11.24.85:8445/finesse/api/User/ferncastillo/Dialogs' => Http::response(
            <<<'XML'
<Dialogs>
    <Dialog>
        <id>12345</id>
        <state>ACTIVE</state>
        <fromAddress>60000000</fromAddress>
    </Dialog>
</Dialogs>
XML,
            200,
            ['Content-Type' => 'application/xml']
        ),
    ]);

    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    $role = Role::firstOrCreate(
        ['name' => 'operator', 'guard_name' => 'web'],
        ['code' => 'OP', 'hierarchy_level' => 1],
    );

    $user->assignRole($role);

    Permission::firstOrCreate(['name' => 'call_records.viewAny', 'guard_name' => 'web']);
    $user->givePermissionTo('call_records.viewAny');

    $this->actingAs($user)
        ->getJson(route('contact-center.cisco.agent-snapshot'))
        ->assertOk()
        ->assertJsonPath('username', 'ferncastillo')
        ->assertJsonPath('agent.loginId', 'ferncastillo')
        ->assertJsonPath('agent.state', 'READY')
        ->assertJsonPath('dialogs.0.id', '12345')
        ->assertJsonPath('dialogs.0.state', 'ACTIVE');

    Http::assertSentCount(2);
});
