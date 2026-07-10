<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\PersonnelModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\GeoModule\Models\District;
use App\Modules\GeoModule\Models\Province;
use App\Modules\GeoModule\Models\Township;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Livewire\CreateEmployee;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('employees.create');
    $this->actingAs($this->user);

    EmploymentStatus::create(['id' => 1, 'name' => 'Activo']);
    Directorate::create(['id' => 1, 'name' => 'Dirección General']);
    Department::create(['id' => 1, 'name' => 'Operaciones', 'directorate_id' => 1]);
    Position::create(['id' => 1, 'name' => 'Agente', 'position_code' => 'AGT', 'department_id' => 1]);
    Province::create(['id' => 1, 'name' => 'Panamá']);
    District::create(['id' => 1, 'name' => 'Distrito Central', 'province_id' => 1]);
    Township::create(['id' => 1, 'name' => 'Ciudad', 'district_id' => 1]);
});

it('renders the create employee form', function () {
    Livewire::test(CreateEmployee::class)
        ->assertStatus(200);
});

it('validates required fields', function () {
    Livewire::test(CreateEmployee::class)
        ->call('save')
        ->assertHasErrors([
            'employee_number' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ]);
});

it('creates an employee with valid data', function () {
    Livewire::test(CreateEmployee::class)
        ->set('employee_number', 'EMP001')
        ->set('username', 'jdoe')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('birth_date', '1990-01-01')
        ->set('gender', 'male')
        ->set('user_id', $this->user->id)
        ->set('hire_date', now()->toDateString())
        ->set('department_id', 1)
        ->set('position_id', 1)
        ->set('employment_status_id', 1)
        ->set('township_id', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('employees', [
        'employee_number' => 'EMP001',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});

it('validates unique employee_number', function () {
    Employee::create([
        'employee_number' => 'EMP001',
        'username' => 'existing',
        'first_name' => 'Existing',
        'last_name' => 'User',
        'email' => 'existing@example.com',
        'birth_date' => '1990-01-01',
        'hire_date' => now()->toDateString(),
        'department_id' => 1,
        'position_id' => 1,
        'employment_status_id' => 1,
        'township_id' => 1,
    ]);

    Livewire::test(CreateEmployee::class)
        ->set('employee_number', 'EMP001')
        ->set('username', 'jdoe2')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john2@example.com')
        ->set('birth_date', '1990-01-01')
        ->set('gender', 'male')
        ->set('user_id', $this->user->id)
        ->set('hire_date', now()->toDateString())
        ->set('department_id', 1)
        ->set('position_id', 1)
        ->set('employment_status_id', 1)
        ->set('township_id', 1)
        ->call('save')
        ->assertHasErrors(['employee_number']);
});
