<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Actions\UpdateEmployeeAction;
use App\Modules\PersonnelModule\DTOs\UpdateEmployeeDTO;
use App\Modules\PersonnelModule\Livewire\Forms\EmployeeForm;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Livewire\Component;
use Src\Location\Application\Contracts\LocationCatalogInterface;

class EditEmployee extends Component
{
    public EmployeeForm $form;

    public Employee $employee;

    public ?int $province_id = null;

    public ?int $district_id = null;

    public array $selectOptions = [];

    public function mount(Employee $employee): void
    {
        $this->authorize('update', $employee);

        $this->employee = $employee;

        $location = app(LocationCatalogInterface::class);
        $township = $employee->township_id ? $location->findTownship((int) $employee->township_id) : null;
        $district = $township ? $location->findDistrict((int) $township->districtId) : null;

        $this->province_id = $district?->provinceId;
        $this->district_id = $township?->districtId;

        $this->form->fill([
            'employee_number' => $employee->employee_number,
            'username' => $employee->username ?? '',
            'user_id' => $employee->user_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'mobile_phone' => $employee->mobile_phone,
            'birth_date' => $employee->birth_date?->format('Y-m-d'),
            'gender' => $employee->gender,
            'blood_type' => $employee->blood_type,
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
            'employment_status_id' => $employee->employment_status_id,
            'parent_id' => $employee->parent_id,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'salary' => (float) ($employee->salary ?? 0),
            'is_active' => $employee->is_active,
            'is_manager' => $employee->is_manager,
            'address' => $employee->address ?? '',
            'township_id' => $employee->township_id,
        ]);

        $this->loadOptions();
    }

    protected function loadOptions(): void
    {
        $location = app(LocationCatalogInterface::class);

        $this->selectOptions = [
            'users' => User::orderBy('name')->limit(200)->pluck('name', 'id')->toArray(),
            'departments' => Department::orderBy('name')->pluck('name', 'id')->toArray(),
            'positions' => Position::orderBy('name')->pluck('name', 'id')->toArray(),
            'employment_statuses' => EmploymentStatus::orderBy('name')->pluck('name', 'id')->toArray(),
            'employees' => Employee::where('id', '!=', $this->employee->id)
                ->orderBy('first_name')
                ->limit(200)
                ->get(['id', 'first_name', 'last_name'])
                ->pluck(fn ($e) => "{$e->first_name} {$e->last_name}", 'id')
                ->toArray(),
            'provinces' => $location->listProvinces()->pluck('name', 'id')->toArray(),
            'districts' => $this->province_id
                ? $location->listDistricts((int) $this->province_id)->pluck('name', 'id')->toArray()
                : [],
            'townships' => $this->district_id
                ? $location->listTownships((int) $this->district_id)->pluck('name', 'id')->toArray()
                : [],
        ];
    }

    public function updatedProvinceId($value): void
    {
        $this->district_id = null;
        $this->form->township_id = null;
        $this->loadOptions();
    }

    public function updatedDistrictId($value): void
    {
        $this->form->township_id = null;
        $this->loadOptions();
    }

    public function update(): void
    {
        $this->authorize('update', $this->employee);

        $this->form->validate();

        $canEditSalary = auth()->user()?->can('salary.edit', $this->employee) ?? false;

        $validated = [
            'employee_number' => $this->form->employee_number,
            'username' => $this->form->username,
            'first_name' => $this->form->first_name,
            'last_name' => $this->form->last_name,
            'email' => $this->form->email,
            'birth_date' => $this->form->birth_date,
            'gender' => $this->form->gender,
            'blood_type' => $this->form->blood_type,
            'phone' => $this->form->phone,
            'mobile_phone' => $this->form->mobile_phone,
            'address' => $this->form->address,
            'township_id' => $this->form->township_id,
            'department_id' => $this->form->department_id,
            'position_id' => $this->form->position_id,
            'employment_status_id' => $this->form->employment_status_id,
            'parent_id' => $this->form->parent_id,
            'user_id' => $this->form->user_id,
            'hire_date' => $this->form->hire_date,
            'salary' => $canEditSalary ? $this->form->salary : null,
            'is_active' => $this->form->is_active,
            'is_manager' => $this->form->is_manager,
            'metadata' => $this->form->metadata,
        ];

        $dto = UpdateEmployeeDTO::fromArray($validated);

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
