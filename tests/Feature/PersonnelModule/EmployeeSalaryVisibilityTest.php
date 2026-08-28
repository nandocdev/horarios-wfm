<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Create permissions manually
    $permissions = [
        'employees.view',
        'employees.view.others',
        'employees.view.all',
        'employees.create',
        'employees.edit',
        'employees.edit.others',
        'employees.edit.all',
        'employees.delete',
        'employees.delete.others',
        'employees.delete.all',
        'employees.force_delete',
        'employees.force_delete.others',
        'employees.force_delete.all',
        'employees.manageTeamAssignments',
        'employees.export',
        'employees.import',
        'employees.salary.view',
        'employees.salary.edit',
        'employees.salary.view.all',
        'employees.salary.edit.all',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create admin role with all permissions
    $adminRole = Role::firstOrCreate(
        ['name' => 'admin', 'guard_name' => 'web'],
        ['code' => 'ADM', 'hierarchy_level' => 99]
    );
    $adminRole->syncPermissions(Permission::all());

    $this->directorate = Directorate::create(['name' => 'Dir Test']);
    $this->department = Department::create(['directorate_id' => $this->directorate->id, 'name' => 'Dept Test']);
    $this->position = Position::create(['department_id' => $this->department->id, 'name' => 'Pos Test', 'position_code' => 'PT-001']);
    $this->status = EmploymentStatus::create(['name' => 'Activo', 'code' => 'ACT']);
    $this->team = Team::create(['name' => 'Team A', 'is_active' => true]);

    $this->user = User::factory()->create();
    $this->hrUser = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->employee = Employee::create([
        'employee_number' => 'EMP001',
        'username' => 'emp001',
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'jperez@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->status->id,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'hire_date' => '2024-01-01',
        'salary' => 50000.00,
        'is_active' => true,
    ]);
});

test('usuario SIN employees.salary.view NO ve salario en show', function () {
    $this->user->givePermissionTo('employees.view');

    $response = $this->actingAs($this->user)->get(route('employees.show', $this->employee));
    $response->assertOk();
    $response->assertDontSee('50000.00');
    $response->assertSee('****');
});

test('usuario CON employees.salary.view VE salario en show', function () {
    $this->user->givePermissionTo(['employees.view', 'employees.salary.view']);

    $response = $this->actingAs($this->user)->get(route('employees.show', $this->employee));
    $response->assertOk();
    $response->assertSee('50,000.00');
});

test('usuario CON employees.salary.view.all VE salario de cualquier empleado', function () {
    $this->user->givePermissionTo(['employees.view.all', 'employees.salary.view.all']);

    $response = $this->actingAs($this->user)->get(route('employees.show', $this->employee));
    $response->assertOk();
    $response->assertSee('50,000.00');
});

test('HR user con employees.salary.edit VE y PUEDE EDITAR salario', function () {
    $hrRole = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
    $hrRole->syncPermissions(['employees.view.all', 'employees.edit.all', 'employees.salary.view', 'employees.salary.view.all', 'employees.salary.edit', 'employees.salary.edit.all']);
    $this->hrUser->assignRole('hr');

    // Debug: check if user has permission
    $this->assertTrue($this->hrUser->hasPermissionTo('employees.salary.edit'), 'User should have salary.edit permission');

    $response = $this->actingAs($this->hrUser)->get(route('employees.edit', $this->employee));
    $response->assertOk();
    $response->assertSee('50000');
});

test('admin VE y PUEDE EDITAR salario', function () {
    $response = $this->actingAs($this->admin)->get(route('employees.edit', $this->employee));
    $response->assertOk();
    $response->assertSee('50000');
});

test('usuario SIN permiso salary.edit ve campo oculto en edit', function () {
    $this->user->givePermissionTo(['employees.view', 'employees.edit']);

    $response = $this->actingAs($this->user)->get(route('employees.edit', $this->employee));
    $response->assertOk();
    $response->assertDontSee('Salario');
});
