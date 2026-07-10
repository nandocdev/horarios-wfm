<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\GeoModule\Models\District;
use App\Modules\GeoModule\Models\Province;
use App\Modules\GeoModule\Models\Township;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Actions\CreateEmployeeAction;
use App\Modules\PersonnelModule\DTOs\CreateEmployeeDTO;
use App\Modules\PersonnelModule\Http\Requests\StoreEmployeeRequest;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Livewire\Component;

/**
 * Componente Livewire para crear empleados.
 *
 * @module EmployeesModule
 *
 * @type Livewire
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class CreateEmployee extends Component
{
    // Datos del formulario
    public string $employee_number = '';

    public string $username = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $birth_date = '';

    public ?string $gender = null;

    public ?string $blood_type = null;

    public ?string $phone = '';

    public ?string $mobile_phone = '';

    public ?string $address = '';

    public ?int $province_id = null;

    public ?int $district_id = null;

    public int $township_id = 0;

    public ?int $department_id = null;

    public int $position_id = 0;

    public int $employment_status_id = 0;

    public ?int $parent_id = null;

    public int $user_id = 0;

    public string $hire_date = '';

    public ?float $salary = null;

    public bool $is_active = true;

    public bool $is_manager = false;

    public ?array $metadata = null;

    // Validación
    protected function rules(): array
    {
        return (new StoreEmployeeRequest)->rules();
    }

    /**
     * Carga distritos cuando cambia la provincia.
     */
    public function updatedProvinceId(): void
    {
        $this->district_id = null;
        $this->township_id = 0;
    }

    /**
     * Carga townships cuando cambia el distrito.
     */
    public function updatedDistrictId(): void
    {
        $this->township_id = 0;
    }

    /**
     * Obtiene las opciones para los selects dependientes.
     */
    public function getSelectOptionsProperty(): array
    {
        return [
            'provinces' => Province::orderBy('name')->pluck('name', 'id'),
            'districts' => $this->province_id
                ? District::where('province_id', $this->province_id)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'townships' => $this->district_id
                ? Township::where('district_id', $this->district_id)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'positions' => Position::orderBy('name')->pluck('name', 'id'),
            'employment_statuses' => EmploymentStatus::orderBy('name')->pluck('name', 'id'),
            'employees' => Employee::where('is_manager', true)->orderBy('last_name')->orderBy('first_name')
                ->get()
                ->pluck('full_name', 'id'),
            'users' => User::doesntHave('employee')->orderBy('name')->pluck('name', 'id'),
        ];
    }

    /**
     * Obtiene el nombre completo del empleado.
     */
    public function getFullNameProperty(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Crea el empleado.
     */
    public function save(): void
    {
        $this->validate();

        $dto = new CreateEmployeeDTO(
            employee_number: $this->employee_number,
            username: $this->username,
            first_name: $this->first_name,
            last_name: $this->last_name,
            email: $this->email,
            birth_date: $this->birth_date,
            gender: $this->gender,
            blood_type: $this->blood_type,
            phone: $this->phone,
            mobile_phone: $this->mobile_phone,
            address: $this->address,
            township_id: $this->township_id,
            department_id: $this->department_id,
            position_id: $this->position_id,
            employment_status_id: $this->employment_status_id,
            parent_id: $this->parent_id,
            user_id: $this->user_id,
            hire_date: $this->hire_date,
            salary: $this->salary,
            is_active: $this->is_active,
            is_manager: $this->is_manager,
            metadata: null,
        );

        $action = app(CreateEmployeeAction::class);
        $employee = $action->execute($dto);

        toast('Empleado creado correctamente.');
        $this->redirect(route('employees.show', $employee), navigate: true);
    }

    public function render()
    {
        return view('personnel::livewire.create-employee', [
            'selectOptions' => $this->selectOptions,
        ]);
    }
}
