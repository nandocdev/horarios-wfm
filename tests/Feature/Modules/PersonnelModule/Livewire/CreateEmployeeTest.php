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
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('employees.create');
    $this->actingAs($this->user);

    $this->employmentStatus = EmploymentStatus::create(['name' => 'Activo']);
    $this->directorate = Directorate::create(['name' => 'Dirección General']);
    $this->department = Department::create(['name' => 'Operaciones', 'directorate_id' => $this->directorate->id]);
    $this->position = Position::create(['name' => 'Agente', 'position_code' => 'AGT', 'department_id' => $this->department->id]);
    $this->province = Province::create(['name' => 'Panamá']);
    $this->district = District::create(['name' => 'Distrito Central', 'province_id' => $this->province->id]);
    $this->township = Township::create(['name' => 'Ciudad', 'district_id' => $this->district->id]);
});

it('renders the create employee form', function () {
    Livewire::test(CreateEmployee::class)
        ->assertStatus(200);
});

it('validates required fields', function () {
    Livewire::test(CreateEmployee::class)
        ->call('save')
        ->assertHasErrors([
            'form.employee_number' => 'required',
            'form.first_name' => 'required',
            'form.last_name' => 'required',
        ]);
});

it('creates an employee with valid data', function () {
    Livewire::test(CreateEmployee::class)
        ->set('form.employee_number', 'EMP001')
        ->set('form.username', 'jdoe')
        ->set('form.first_name', 'John')
        ->set('form.last_name', 'Doe')
        ->set('form.email', 'john@example.com')
        ->set('form.birth_date', '1990-01-01')
        ->set('form.gender', 'M')
        ->set('form.user_id', $this->user->id)
        ->set('form.hire_date', now()->toDateString())
        ->set('form.department_id', $this->department->id)
        ->set('form.position_id', $this->position->id)
        ->set('form.employment_status_id', $this->employmentStatus->id)
        ->set('form.township_id', $this->township->id)
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
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'employment_status_id' => $this->employmentStatus->id,
        'township_id' => $this->township->id,
    ]);

    Livewire::test(CreateEmployee::class)
        ->set('form.employee_number', 'EMP001')
        ->set('form.username', 'jdoe2')
        ->set('form.first_name', 'John')
        ->set('form.last_name', 'Doe')
        ->set('form.email', 'john2@example.com')
        ->set('form.birth_date', '1990-01-01')
        ->set('form.gender', 'M')
        ->set('form.user_id', $this->user->id)
        ->set('form.hire_date', now()->toDateString())
        ->set('form.department_id', $this->department->id)
        ->set('form.position_id', $this->position->id)
        ->set('form.employment_status_id', $this->employmentStatus->id)
        ->set('form.township_id', $this->township->id)
        ->call('save')
        ->assertHasErrors(['form.employee_number']);
});
