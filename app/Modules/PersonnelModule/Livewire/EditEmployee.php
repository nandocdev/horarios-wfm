<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\GeoModule\Models\District;
use App\Modules\GeoModule\Models\Province;
use App\Modules\GeoModule\Models\Township;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Actions\UpdateEmployeeAction;
use App\Modules\PersonnelModule\DTOs\UpdateEmployeeDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Livewire\Component;

class EditEmployee extends Component
{
    public Employee $employee;

    // Campos del formulario
    public string $employee_number = '';

    public string $username = '';

    public ?int $user_id = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $mobile_phone = null;

    public ?string $birth_date = null;

    public string $gender = '';

    public ?string $blood_type = null;

    public int $department_id = 0;

    public int $position_id = 0;

    public int $employment_status_id = 0;

    public ?int $parent_id = null;

    public string $hire_date = '';

    public float $salary = 0;

    public bool $is_active = true;

    public bool $is_manager = false;

    public string $address = '';

    public int $township_id = 0;

    public ?int $district_id = null;

    public ?int $province_id = null;

    // Opciones para selects
    public array $selectOptions = [];

    /**
     * Inicializa el componente con los datos del empleado.
     */
    public function mount(Employee $employee): void
    {
        $this->employee = $employee;

        // Cargar datos del empleado
        $this->employee_number = $employee->employee_number;
        $this->username = $employee->username ?? '';
        $this->user_id = $employee->user_id;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->mobile_phone = $employee->mobile_phone;
        $this->birth_date = $employee->birth_date?->format('Y-m-d');
        $this->gender = $employee->gender;
        $this->blood_type = $employee->blood_type;
        $this->department_id = $employee->department_id;
        $this->position_id = $employee->position_id;
        $this->employment_status_id = $employee->employment_status_id;
        $this->parent_id = $employee->parent_id;
        $this->hire_date = $employee->hire_date->format('Y-m-d');
        $this->salary = (float) $employee->salary;
        $this->is_active = $employee->is_active;
        $this->is_manager = $employee->is_manager;
        $this->address = $employee->address ?? '';
        $this->township_id = $employee->township_id;

        // Cargar jerarquía de ubicación
        if ($this->township_id) {
            $township = Township::find($this->township_id);
            if ($township) {
                $this->district_id = $township->district_id;
                $this->province_id = District::find($this->district_id)?->province_id;
            }
        }

        $this->loadOptions();
    }

    /**
     * Carga las opciones para los selects.
     */
    protected function loadOptions(): void
    {
        $this->selectOptions = [
            'users' => User::orderBy('name')->pluck('name', 'id')->toArray(),
            'departments' => Department::orderBy('name')->pluck('name', 'id')->toArray(),
            'positions' => Position::orderBy('name')->pluck('name', 'id')->toArray(),
            'employment_statuses' => EmploymentStatus::orderBy('name')->pluck('name', 'id')->toArray(),
            'employees' => Employee::where('id', '!=', $this->employee->id)
                ->orderBy('first_name')
                ->get()
                ->pluck('full_name', 'id')
                ->toArray(),
            'provinces' => Province::orderBy('name')->pluck('name', 'id')->toArray(),
            'districts' => $this->province_id
                ? District::where('province_id', $this->province_id)->orderBy('name')->pluck('name', 'id')->toArray()
                : [],
            'townships' => $this->district_id
                ? Township::where('district_id', $this->district_id)->orderBy('name')->pluck('name', 'id')->toArray()
                : [],
        ];
    }

    /**
     * Actualiza distritos cuando cambia la provincia.
     */
    public function updatedProvinceId($value): void
    {
        $this->district_id = null;
        $this->township_id = 0;
        $this->loadOptions();
    }

    /**
     * Actualiza comunas cuando cambia el distrito.
     */
    public function updatedDistrictId($value): void
    {
        $this->township_id = 0;
        $this->loadOptions();
    }

    /**
     * Valida y actualiza el empleado.
     */
    public function update(): void
    {
        $validatedData = $this->validate([
            'employee_number' => 'required|string|unique:employees,employee_number,'.$this->employee->id,
            'username' => 'required|string|unique:employees,username,'.$this->employee->id,
            'user_id' => 'required|exists:users,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,'.$this->employee->id,
            'phone' => 'nullable|string',
            'mobile_phone' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'required|string|in:M,F,O',
            'blood_type' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'employment_status_id' => 'required|exists:employment_statuses,id',
            'parent_id' => 'nullable|exists:employees,id',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_manager' => 'boolean',
            'address' => 'required|string',
            'township_id' => 'required|exists:townships,id',
        ]);

        $dto = UpdateEmployeeDTO::fromArray($validatedData);
        $action = new UpdateEmployeeAction;
        $updatedEmployee = $action->execute($this->employee, $dto);

        toast('Empleado actualizado correctamente.');
        $this->redirect(route('employees.show', $updatedEmployee), navigate: true);
    }

    public function render()
    {
        return view('personnel::livewire.edit-employee');
    }
}
