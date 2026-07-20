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
use App\Modules\PersonnelModule\Livewire\Forms\EmployeeForm;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Livewire\Component;

class CreateEmployee extends Component
{
    public EmployeeForm $form;

    public ?int $province_id = null;

    public ?int $district_id = null;

    public function updatedProvinceId(): void
    {
        $this->district_id = null;
        $this->form->township_id = null;
    }

    public function updatedDistrictId(): void
    {
        $this->form->township_id = null;
    }

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

    public function save(): void
    {
        $this->authorize('create', Employee::class);

        $this->form->validate();

        $dto = new CreateEmployeeDTO(
            employee_number: $this->form->employee_number,
            username: $this->form->username,
            first_name: $this->form->first_name,
            last_name: $this->form->last_name,
            email: $this->form->email,
            birth_date: $this->form->birth_date,
            gender: $this->form->gender,
            blood_type: $this->form->blood_type,
            phone: $this->form->phone,
            mobile_phone: $this->form->mobile_phone,
            address: $this->form->address,
            township_id: $this->form->township_id ?? 0,
            department_id: $this->form->department_id,
            position_id: $this->form->position_id ?? 0,
            employment_status_id: $this->form->employment_status_id ?? 0,
            parent_id: $this->form->parent_id,
            user_id: $this->form->user_id ?? 0,
            hire_date: $this->form->hire_date,
            salary: $this->form->salary,
            is_active: $this->form->is_active,
            is_manager: $this->form->is_manager,
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
